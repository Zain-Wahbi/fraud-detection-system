<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\FraudDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::orderBy('transaction_at', 'desc');

        // ✅ إصلاح: تجميع شرط البحث (OR) بقوسين منفصلين
        // قبل الإصلاح كان شرط "type" بينضم غلط لشرط الـ OR بسبب أسبقية العمليات بالـ SQL
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sender_account', 'like', "%{$search}%")
                  ->orWhere('receiver_account', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('is_fraud', $request->type === 'fraud' ? 1 : 0);
        }

        // ✅ withQueryString عشان تضل البحث والفلتر محفوظين عند التنقل بين الصفحات
        $transactions = $query->paginate(20)->withQueryString();
        return view('admin.transactions', compact('transactions'));
    }

    public function show($id)
    {
        $transaction = Transaction::findOrFail($id);
        $explanation = [];

        if ($transaction->is_fraud && !$transaction->ignored) {
            try {
                $rawFeatures = is_array($transaction->raw_features)
                    ? $transaction->raw_features
                    : json_decode($transaction->raw_features, true) ?? [];

                $payload = [
                    'fraud_probability'           => floatval($transaction->fraud_probability),
                    'model_used'                  => $transaction->model_used, // ✅ إضافة: عشان /explain يستخدم نفس الـ threshold الصحيح
                    'amount'                      => floatval($transaction->amount),
                    'transaction_type'            => $transaction->transaction_type,
                    'merchant_category'           => $transaction->merchant_category,
                    'location'                    => $transaction->location,
                    'payment_channel'              => $transaction->payment_channel,
                    'sender_account'               => $transaction->sender_account,
                    'receiver_account'             => $transaction->receiver_account,
                    'spending_deviation_score'    => floatval($rawFeatures['spending_deviation_score'] ?? 0),
                    'velocity_score'               => floatval($rawFeatures['velocity_score'] ?? 0),
                    'geo_anomaly_score'            => floatval($rawFeatures['geo_anomaly_score'] ?? 0),
                    'time_since_last_transaction'  => floatval($rawFeatures['time_since_last_transaction'] ?? 0),
                ];

                $explanation = app(FraudDetectionService::class)->explain($payload);
            } catch (\Exception $e) {
                $explanation = ['reasons' => ['تعذّر تحميل التفسير'], 'risk_level' => '—'];
            }
        }

        return view('admin.transaction-show', compact('transaction', 'explanation'));
    }

    public function blocklist()
    {
        $transactions = Transaction::where('is_blocked', true)
                                   ->orderBy('created_at', 'desc')
                                   ->paginate(20);
        return view('admin.blocklist', compact('transactions'));
    }

    public function uploadForm()
    {
        return view('admin.upload');
    }

    public function uploadCsv(Request $request)
    {
        // التحقق من الملف
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        // قراءة الملف
        $path    = $request->file('csv_file')->getRealPath();
        $lines   = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!$lines || count($lines) < 2) {
            return back()->with('error', 'The file is empty or incorrectly formatted.');
        }

        // قراءة رؤوس الأعمدة
        $headers = str_getcsv(array_shift($lines));

        // تحويل الصفوف إلى مصفوفات
        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            if (count($values) === count($headers)) {
                $rows[] = array_combine($headers, $values);
            }
        }

        if (empty($rows)) {
            return back()->with('error', 'The file is empty or incorrectly formatted.');
        }

        // 🔥 تحويل الأعمدة لتطابق FastAPI schema
        $normalized = [];

        foreach ($rows as $row) {
            $normalized[] = [
                'timestamp' => $row['timestamp'] ?? null,
                'amount' => floatval($row['amount'] ?? 0),

                'transaction_type' => $row['transaction_type'] ?? null,

                // دعم merchant_location
                'merchant_category' => $row['merchant_category'] ?? ($row['merchant_location'] ?? null),
                'location' => $row['location'] ?? ($row['merchant_location'] ?? null),

                'payment_channel' => $row['payment_channel'] ?? null,

                // دعم sender_ac و receiver_ac
                'sender_account' => $row['sender_account'] ?? ($row['sender_ac'] ?? null),
                'receiver_account' => $row['receiver_account'] ?? ($row['receiver_ac'] ?? null),

                // دعم spending_deviation
                'spending_deviation_score' => floatval($row['spending_deviation_score'] ?? ($row['spending_deviation'] ?? 0)),

                'velocity_score' => floatval($row['velocity_score'] ?? 0),
                'geo_anomaly_score' => floatval($row['geo_anomaly_score'] ?? 0),
                'time_since_last_transaction' => floatval($row['time_since_last_transaction'] ?? 0),
            ];
        }

        // إرسال للـ API
        $service = new FraudDetectionService();
        $result  = $service->predictBatch($normalized);

        if (isset($result['error'])) {
            return back()->with('error', 'API Error: ' . $result['error']);
        }

        $results = $result['results'];

        // حفظ في قاعدة البيانات
        foreach ($rows as $i => $row) {

            $pred = $results[$i] ?? [
                'is_fraud' => 0,
                'probability' => 0,
                'model_used' => 'LightGBM',
                'threshold' => 0.455
            ];

            Transaction::updateOrCreate(
                // ✅ إصلاح: لو ما في transaction_id بالـ CSV، منولّد UUID فريد
                // قبل الإصلاح: كل الصفوف بدون transaction_id كانت تتراكب فوق بعضها وتنمسح
                ['transaction_id' => ($row['transaction_id'] ?? null) ?: (string) Str::uuid()],
                [
                    'amount'           => floatval($row['amount'] ?? 0),
                    'transaction_type' => $row['transaction_type'] ?? null,
                    'merchant_category'=> $row['merchant_category'] ?? ($row['merchant_location'] ?? null),
                    'location'         => $row['location'] ?? ($row['merchant_location'] ?? null),
                    'payment_channel'  => $row['payment_channel'] ?? null,
                    'sender_account'   => $row['sender_account'] ?? ($row['sender_ac'] ?? null),
                    'receiver_account' => $row['receiver_account'] ?? ($row['receiver_ac'] ?? null),
                    'is_fraud'         => $pred['is_fraud'],
                    'fraud_probability'=> $pred['probability'],
                    'model_used'       => $pred['model_used'],
                    'threshold_used'   => $pred['threshold'],
                    'is_blocked'       => $pred['is_fraud'] == 1,
                    'transaction_at'   => $row['timestamp'] ?? now(),
                    // ✅ إصلاح: شيلنا json_encode() لأن الموديل أصلاً معرف raw_features كـ cast('array')
                    // كان في تشفير مضاعف (double encoding) وقت الحفظ
                    'raw_features'     => $row,
                ]
            );
        }

        return redirect()->route('transactions.index')
            ->with('success', 'تم تحليل ' . count($rows) . ' معاملة — تم اكتشاف ' . $result['fraud_count'] . ' عملية احتيال.')
            ->with('success', 'Processed ' . count($rows) . ' transactions — detected ' . $result['fraud_count'] . ' fraudulent cases.');
    }

    public function ignore(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->update([
            'ignored'      => true,
            'is_fraud'     => false,
            'is_blocked'   => false,
            'ignore_reason'=> 'تم التجاهل يدوياً من قِبل الأدمن',
        ]);

        return response()->json(['success' => true, 'message' => 'تم تجاهل المعاملة بنجاح']);
    }
}