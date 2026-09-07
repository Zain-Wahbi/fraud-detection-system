@extends('admin.layout')
@section('title', 'Fraud Bank — Dashboard')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white" data-ar="لوحة التحكم" data-en="Dashboard">لوحة التحكم</h1>
    <p class="text-gray-400 mt-1" data-ar="نظرة عامة على المعاملات المالية" data-en="Financial Transactions Overview">نظرة عامة على المعاملات المالية</p>
</div>

{{-- API Status --}}
<div class="mb-6 flex items-center gap-2">
    @if(($apiStatus['status'] ?? '') === 'ok')
        <span class="flex items-center gap-2 text-sm text-green-400">
            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
            <span data-ar="نموذج الذكاء الاصطناعي متصل" data-en="AI Model Connected">نموذج الذكاء الاصطناعي متصل</span>
            — {{ $apiStatus['best_model'] ?? 'LightGBM' }}
        </span>
    @else
        <span class="flex items-center gap-2 text-sm text-red-400">
            <span class="w-2 h-2 bg-red-400 rounded-full"></span>
            <span data-ar="نموذج الذكاء الاصطناعي غير متصل" data-en="AI Model Disconnected">نموذج الذكاء الاصطناعي غير متصل</span>
        </span>
    @endif
</div>

{{-- الكروت الإحصائية --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="text-gray-400 text-sm mb-1" data-ar="إجمالي المعاملات" data-en="Total Transactions">إجمالي المعاملات</div>
        <div class="text-3xl font-bold text-white">{{ number_format($total) }}</div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="text-gray-400 text-sm mb-1" data-ar="معاملات احتيالية" data-en="Fraudulent Transactions">معاملات احتيالية</div>
        <div class="text-3xl font-bold text-red-400">{{ number_format($fraudCount) }}</div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="text-gray-400 text-sm mb-1" data-ar="معاملات سليمة" data-en="Legitimate Transactions">معاملات سليمة</div>
        <div class="text-3xl font-bold text-green-400">{{ number_format($legitCount) }}</div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="text-gray-400 text-sm mb-1" data-ar="نسبة الاحتيال" data-en="Fraud Rate">نسبة الاحتيال</div>
        <div class="text-3xl font-bold text-yellow-400">{{ $fraudRate }}%</div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="text-gray-400 text-sm mb-1" data-ar="محظورة" data-en="Blocked">محظورة</div>
        <div class="text-3xl font-bold text-orange-400">{{ number_format($blocked) }}</div>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <div class="text-gray-400 text-sm mb-1" data-ar="متوسط احتمالية الاحتيال" data-en="Avg Fraud Probability">متوسط احتمالية الاحتيال</div>
        <div class="text-3xl font-bold text-purple-400">{{ $avgProb ? round($avgProb * 100, 1) . '%' : '—' }}</div>
    </div>
</div>

{{-- جدول توزيع الاحتيال حسب النوع --}}
@if($byType->count())
<div class="bg-gray-900 border border-gray-800 rounded-xl p-6 mb-8">
    <h2 class="text-lg font-semibold text-white mb-4"
        data-ar="الاحتيال حسب نوع المعاملة" data-en="Fraud by Transaction Type">
        الاحتيال حسب نوع المعاملة
    </h2>
    <table class="w-full text-sm">
        <thead>
            <tr class="text-gray-400 border-b border-gray-800">
                <th class="text-start pb-3" data-ar="النوع"     data-en="Type">النوع</th>
                <th class="text-start pb-3" data-ar="الإجمالي" data-en="Total">الإجمالي</th>
                <th class="text-start pb-3" data-ar="احتيالية" data-en="Fraud">احتيالية</th>
                <th class="text-start pb-3" data-ar="النسبة"   data-en="Rate">النسبة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($byType as $row)
            <tr class="border-b border-gray-800/50">
                <td class="py-3 text-white">{{ $row->transaction_type ?? '—' }}</td>
                <td class="py-3 text-gray-300">{{ $row->total }}</td>
                <td class="py-3 text-red-400">{{ $row->fraud }}</td>
                <td class="py-3">
                    @php $rate = $row->total > 0 ? round($row->fraud / $row->total * 100, 1) : 0; @endphp
                    <span class="px-2 py-1 rounded text-xs {{ $rate > 20 ? 'bg-red-900/50 text-red-300' : 'bg-gray-800 text-gray-400' }}">
                        {{ $rate }}%
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ✅ المخططات الثلاثة --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    {{-- مخطط 1: الإيداعات والسحوبات الشهرية --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 lg:col-span-2">
        <h2 class="text-lg font-semibold text-white mb-1"
            data-ar="إجمالي مبالغ الإيداعات والسحوبات الشهرية"
            data-en="Monthly Deposits & Withdrawals">
            إجمالي مبالغ الإيداعات والسحوبات الشهرية
        </h2>
        <p class="text-gray-500 text-xs mb-4"
           data-ar="بالدولار — آخر 12 شهر"
           data-en="In USD — Last 12 months">
           بالدولار — آخر 12 شهر
        </p>
        <div class="relative h-64">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    {{-- مخطط 2: توزيع المعاملات حسب النوع (Doughnut) --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-white mb-1"
            data-ar="توزيع المعاملات حسب النوع"
            data-en="Transactions by Type">
            توزيع المعاملات حسب النوع
        </h2>
        <p class="text-gray-500 text-xs mb-4"
           data-ar="سليمة واحتيالية"
           data-en="Legit & Fraud">
           سليمة واحتيالية
        </p>
        <div class="relative h-64">
            <canvas id="typeChart"></canvas>
        </div>
    </div>

    {{-- مخطط 3: عدد المعاملات بكل ساعة --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-white mb-1"
            data-ar="توزيع المعاملات خلال اليوم (بالساعة)"
            data-en="Transactions by Hour of Day">
            توزيع المعاملات خلال اليوم (بالساعة)
        </h2>
        <p class="text-gray-500 text-xs mb-4"
           data-ar="عدد المعاملات لكل ساعة (0-23)"
           data-en="Transaction count per hour (0–23)">
           عدد المعاملات لكل ساعة (0-23)
        </p>
        <div class="relative h-64">
            <canvas id="hourlyChart"></canvas>
        </div>
    </div>

</div>

{{-- أزرار التنقل --}}
<div class="flex gap-4">
    <a href="{{ route('upload.form') }}"
       class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-sm font-medium transition"
       data-ar="رفع معاملات جديدة" data-en="Upload Transactions">رفع معاملات جديدة</a>
    <a href="{{ route('transactions.index') }}"
       class="bg-gray-800 hover:bg-gray-700 text-white px-6 py-3 rounded-lg text-sm font-medium transition"
       data-ar="عرض كل المعاملات" data-en="View All Transactions">عرض كل المعاملات</a>
    <a href="{{ route('blocklist') }}"
       class="bg-red-900/50 hover:bg-red-900 text-red-300 px-6 py-3 rounded-lg text-sm font-medium transition"
       data-ar="قائمة الحظر" data-en="Blocklist">قائمة الحظر</a>
</div>

{{-- Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── بيانات من Laravel ─────────────────────────────────────
const depositLabels  = @json($depositsByMonth->pluck('month'));
const depositData    = @json($depositsByMonth->pluck('total_amount'));

const withdrawLabels = @json($withdrawalsByMonth->pluck('month'));
const withdrawData   = @json($withdrawalsByMonth->pluck('total_amount'));

const typeLabels = @json($byType->pluck('transaction_type'));
const typeTotals = @json($byType->pluck('total'));
const typeFraud  = @json($byType->pluck('fraud'));

const hourlyLabels = @json($hourlyData->pluck('hour')->map(fn($h) => $h . ':00'));
const hourlyTotals = @json($hourlyData->pluck('total'));

// ── إعدادات مشتركة ────────────────────────────────────────
Chart.defaults.color = '#9ca3af';
Chart.defaults.borderColor = '#1f2937';
Chart.defaults.font.family = 'inherit';

// ── مخطط 1: الإيداعات والسحوبات الشهرية ──────────────────
const allMonths = [...new Set([...depositLabels, ...withdrawLabels])].sort();

const depositByMonth  = allMonths.map(m => {
    const idx = depositLabels.indexOf(m);
    return idx >= 0 ? depositData[idx] : 0;
});
const withdrawByMonth = allMonths.map(m => {
    const idx = withdrawLabels.indexOf(m);
    return idx >= 0 ? withdrawData[idx] : 0;
});

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: allMonths,
        datasets: [
            {
                label: document.documentElement.lang === 'en' ? 'Deposits' : 'الإيداعات',
                data: depositByMonth,
                backgroundColor: 'rgba(34, 197, 94, 0.7)',
                borderColor: '#22c55e',
                borderWidth: 1,
                borderRadius: 4,
            },
            {
                label: document.documentElement.lang === 'en' ? 'Withdrawals' : 'السحوبات',
                data: withdrawByMonth,
                backgroundColor: 'rgba(239, 68, 68, 0.7)',
                borderColor: '#ef4444',
                borderWidth: 1,
                borderRadius: 4,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: ctx => ' $' + Number(ctx.raw).toLocaleString(undefined, {maximumFractionDigits: 0}),
                }
            }
        },
        scales: {
            x: { grid: { color: '#1f2937' } },
            y: {
                grid: { color: '#1f2937' },
                ticks: {
                    callback: v => '$' + Number(v).toLocaleString(undefined, {maximumFractionDigits: 0}),
                }
            },
        },
    },
});

// ── مخطط 2: توزيع المعاملات حسب النوع (Doughnut) ──────────
const typeColors = ['#3b82f6','#22c55e','#f59e0b','#ef4444','#a855f7','#06b6d4'];

new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [
            {
                label: document.documentElement.lang === 'en' ? 'Total' : 'الإجمالي',
                data: typeTotals,
                backgroundColor: typeColors.map(c => c + 'cc'),
                borderColor: typeColors,
                borderWidth: 2,
            },
        ],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const fraud = typeFraud[ctx.dataIndex];
                        const total = ctx.raw;
                        const rate  = total > 0 ? ((fraud / total) * 100).toFixed(1) : 0;
                        return [
                            ` ${document.documentElement.lang === 'en' ? 'Total' : 'الإجمالي'}: ${total}`,
                            ` ${document.documentElement.lang === 'en' ? 'Fraud' : 'احتيال'}: ${fraud} (${rate}%)`,
                        ];
                    }
                }
            }
        },
    },
});

// ── مخطط 3: عدد المعاملات بكل ساعة ───────────────────────
const maxHour = Math.max(...hourlyTotals);

new Chart(document.getElementById('hourlyChart'), {
    type: 'bar',
    data: {
        labels: hourlyLabels,
        datasets: [{
            label: document.documentElement.lang === 'en' ? 'Transactions' : 'المعاملات',
            data: hourlyTotals,
            backgroundColor: hourlyTotals.map(v =>
                v === maxHour ? 'rgba(239,68,68,0.85)' :
                v > maxHour * 0.7 ? 'rgba(251,146,60,0.75)' :
                'rgba(59,130,246,0.65)'
            ),
            borderRadius: 3,
        }],
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    title: ctx => ctx[0].label,
                    label: ctx => ` ${ctx.raw} ${document.documentElement.lang === 'en' ? 'transactions' : 'معاملة'}`,
                }
            }
        },
        scales: {
            x: { grid: { color: '#1f2937' } },
            y: {
                grid: { color: '#1f2937' },
                beginAtZero: true,
                ticks: { stepSize: 1 },
            },
        },
    },
});
</script>
@endsection