<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\FraudDetectionService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $total      = Transaction::count();
        $fraudCount = Transaction::where('is_fraud', true)->count();
        $legitCount = $total - $fraudCount;
        $fraudRate  = $total > 0 ? round($fraudCount / $total * 100, 2) : 0;
        $blocked    = Transaction::where('is_blocked', true)->count();
        $avgProb    = Transaction::where('is_fraud', 1)->avg('fraud_probability') ?? 0;

        $daily = Transaction::whereNotNull('created_at')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as total, SUM(is_fraud) as fraud')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        $byType = Transaction::selectRaw('transaction_type, COUNT(*) as total, SUM(is_fraud) as fraud')
                             ->groupBy('transaction_type')
                             ->get();

        // ✅ 1 — إجمالي مبالغ الإيداعات بكل شهر
        $depositsByMonth = Transaction::where('transaction_type', 'deposit')
            ->whereNotNull('transaction_at')
            ->selectRaw("DATE_FORMAT(transaction_at, '%Y-%m') as month, SUM(amount) as total_amount")
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(12)
            ->get();

        // ✅ 2 — إجمالي مبالغ السحوبات بكل شهر
        $withdrawalsByMonth = Transaction::where('transaction_type', 'withdrawal')
            ->whereNotNull('transaction_at')
            ->selectRaw("DATE_FORMAT(transaction_at, '%Y-%m') as month, SUM(amount) as total_amount")
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(12)
            ->get();

        // ✅ 3 — عدد المعاملات بكل ساعة من اليوم (0-23)
        $byHour = Transaction::whereNotNull('transaction_at')
            ->selectRaw('HOUR(transaction_at) as hour, COUNT(*) as total')
            ->groupBy('hour')
            ->orderBy('hour', 'asc')
            ->get()
            ->keyBy('hour');

        // نبني مصفوفة كاملة 24 ساعة (0→23) حتى الساعات الفارغة تظهر بقيمة 0
        $hourlyData = collect(range(0, 23))->map(fn($h) => [
            'hour'  => $h,
            'total' => $byHour->get($h)?->total ?? 0,
        ]);

        try {
            $apiStatus = (new FraudDetectionService())->health();
        } catch (\Exception $e) {
            $apiStatus = ['status' => 'offline'];
        }

        return view('admin.dashboard', compact(
            'total', 'fraudCount', 'legitCount', 'fraudRate',
            'blocked', 'avgProb', 'daily', 'byType', 'apiStatus',
            'depositsByMonth', 'withdrawalsByMonth', 'hourlyData'
        ));
    }
}