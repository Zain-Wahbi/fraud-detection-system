<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\FraudDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SimulationController extends Controller
{
    /**
     * صفحة رفع ملف المحاكاة
     */
    public function uploadForm()
    {
        return view('admin.simulation-upload');
    }

    /**
     * استقبال ملف CSV وتخزين المعاملات بحالة "queued" فقط — بدون تحليلها
     */
    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:20480',
        ]);

        $path  = $request->file('csv_file')->getRealPath();
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!$lines || count($lines) < 2) {
            return back()->with('error', 'The file is empty or incorrectly formatted.');
        }

        $headers = str_getcsv(array_shift($lines));

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

        // batch id فريد لهذه الجلسة من المحاكاة
        $batchId = (string) Str::uuid();

        foreach ($rows as $row) {

            // نفس التطبيع المستخدم بصفحة upload العادية — يبقى متّسقاً مع Python API
            $payload = [
                'timestamp'                   => $row['timestamp'] ?? null,
                'amount'                       => floatval($row['amount'] ?? 0),
                'transaction_type'             => $row['transaction_type'] ?? null,
                'merchant_category'            => $row['merchant_category'] ?? ($row['merchant_location'] ?? null),
                'location'                     => $row['location'] ?? ($row['merchant_location'] ?? null),
                'payment_channel'              => $row['payment_channel'] ?? null,
                'sender_account'               => $row['sender_account'] ?? ($row['sender_ac'] ?? null),
                'receiver_account'             => $row['receiver_account'] ?? ($row['receiver_ac'] ?? null),
                'spending_deviation_score'     => floatval($row['spending_deviation_score'] ?? ($row['spending_deviation'] ?? 0)),
                'velocity_score'               => floatval($row['velocity_score'] ?? 0),
                'geo_anomaly_score'            => floatval($row['geo_anomaly_score'] ?? 0),
                'time_since_last_transaction'  => floatval($row['time_since_last_transaction'] ?? 0),
            ];

            Transaction::updateOrCreate(
                ['transaction_id' => ($row['transaction_id'] ?? null) ?: (string) Str::uuid()],
                [
                    'amount'               => $payload['amount'],
                    'transaction_type'     => $payload['transaction_type'],
                    'merchant_category'    => $payload['merchant_category'],
                    'location'             => $payload['location'],
                    'payment_channel'      => $payload['payment_channel'],
                    'sender_account'       => $payload['sender_account'],
                    'receiver_account'     => $payload['receiver_account'],
                    'transaction_at'       => $payload['timestamp'] ?? now(),
                    'raw_features'         => $row,
                    'queued_payload'       => $payload,
                    'simulation_status'    => 'queued',
                    'simulation_batch_id'  => $batchId,
                    // قيم مؤقتة لحين التحليل أثناء المحاكاة
                    'is_fraud'             => false,
                    'fraud_probability'    => 0,
                    'model_used'           => null,
                    'threshold_used'       => null,
                    'is_blocked'           => false,
                ]
            );
        }

        return redirect()->route('simulation.show', ['batch' => $batchId]);
    }

    /**
     * صفحة عرض المحاكاة الحيّة لجلسة معيّنة
     */
    public function show($batch)
    {
        $total    = Transaction::where('simulation_batch_id', $batch)->count();
        $analyzed = Transaction::where('simulation_batch_id', $batch)
                                ->where('simulation_status', 'analyzed')
                                ->count();

        if ($total === 0) {
            abort(404);
        }

        $fraudCount = Transaction::where('simulation_batch_id', $batch)
                                  ->where('simulation_status', 'analyzed')
                                  ->where('is_fraud', true)
                                  ->count();

        $legitCount = $analyzed - $fraudCount;

        return view('admin.simulation-show', [
            'batchId'    => $batch,
            'total'      => $total,
            'analyzed'   => $analyzed,
            'fraudCount' => $fraudCount,
            'legitCount' => $legitCount,
        ]);
    }

    /**
     * يسحب أقدم معاملة "queued" بهذا الـ batch، يحللها، ويرجّع النتيجة
     * تستدعيها الواجهة بشكل متكرر (polling) كل X ثانية
     */
    public function next(Request $request, $batch)
    {
        $transaction = Transaction::where('simulation_batch_id', $batch)
                                   ->where('simulation_status', 'queued')
                                   ->orderBy('id', 'asc')
                                   ->first();

        if (!$transaction) {
            $total    = Transaction::where('simulation_batch_id', $batch)->count();
            $analyzed = Transaction::where('simulation_batch_id', $batch)
                                    ->where('simulation_status', 'analyzed')
                                    ->count();

            return response()->json([
                'done'     => true,
                'total'    => $total,
                'analyzed' => $analyzed,
            ]);
        }

        $service = app(FraudDetectionService::class);
        $result  = $service->predictBatch([$transaction->queued_payload]);

        if (isset($result['error']) || empty($result['results'])) {
            return response()->json([
                'done'  => false,
                'error' => $result['error'] ?? 'Prediction failed',
            ], 500);
        }

        $pred = $result['results'][0];

        $transaction->update([
            'is_fraud'          => $pred['is_fraud'],
            'fraud_probability' => $pred['probability'],
            'model_used'        => $pred['model_used'],
            'threshold_used'    => $pred['threshold'],
            'is_blocked'        => $pred['is_fraud'] == 1,
            'simulation_status' => 'analyzed',
        ]);

        $total    = Transaction::where('simulation_batch_id', $batch)->count();
        $analyzed = Transaction::where('simulation_batch_id', $batch)
                                ->where('simulation_status', 'analyzed')
                                ->count();

        return response()->json([
            'done'      => false,
            'total'     => $total,
            'analyzed'  => $analyzed,
            'transaction' => [
                'id'                => $transaction->id,
                'amount'            => $transaction->amount,
                'transaction_type'  => $transaction->transaction_type,
                'sender_account'    => $transaction->sender_account,
                'receiver_account'  => $transaction->receiver_account,
                'is_fraud'          => (bool) $transaction->is_fraud,
                'fraud_probability' => round($transaction->fraud_probability * 100, 4),
                'model_used'        => $transaction->model_used,
            ],
        ]);
    }
}