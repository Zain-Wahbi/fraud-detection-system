@extends('admin.layout')
@section('title', 'Fraud Bank — Live Simulation')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-white" data-ar="محاكاة حيّة" data-en="Live Simulation">محاكاة حيّة</h1>
        <p class="text-gray-400 mt-1"
           data-ar="استقبال وتحليل المعاملات بشكل حي، معاملة تلو الأخرى"
           data-en="Receiving and analyzing transactions live, one at a time">
           استقبال وتحليل المعاملات بشكل حي، معاملة تلو الأخرى
        </p>
    </div>
    <div class="flex items-center gap-3">
        <select id="speed-select"
                class="bg-gray-900 border border-gray-700 text-white px-3 py-2 rounded-lg text-sm focus:outline-none">
            <option value="1000"  data-ar="سرعة: ثانية واحدة" data-en="Speed: 1s">سرعة: ثانية واحدة</option>
            <option value="5000"  data-ar="سرعة: 5 ثواني"  data-en="Speed: 5s">سرعة: 5 ثواني</option>
            <option value="15000" selected data-ar="سرعة: 15 ثانية" data-en="Speed: 15s">سرعة: 15 ثانية</option>
            <option value="30000" data-ar="سرعة: 30 ثانية" data-en="Speed: 30s">سرعة: 30 ثانية</option>
        </select>
        <button id="toggle-btn" onclick="toggleSimulation()"
                class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
            <svg id="toggle-icon" class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path d="M6.3 2.84A1.5 1.5 0 004 4.11v11.78a1.5 1.5 0 002.3 1.27l9.34-5.89a1.5 1.5 0 000-2.54L6.3 2.84z"/>
            </svg>
            <span id="toggle-label" data-ar="بدء المحاكاة" data-en="Start Simulation">بدء المحاكاة</span>
        </button>
        <button id="end-btn" onclick="confirmEndSimulation()"
                class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 5a1 1 0 011-1h8a1 1 0 011 1v8a1 1 0 01-1 1H6a1 1 0 01-1-1V5z" clip-rule="evenodd"/>
            </svg>
            <span data-ar="إنهاء المحاكاة" data-en="End Simulation">إنهاء المحاكاة</span>
        </button>
    </div>
</div>

<!-- Progress -->
<div class="bg-gray-900 border border-gray-800 rounded-xl p-5 mb-6">
    <div class="flex items-center justify-between mb-2 text-sm">
        <span class="text-gray-400">
            <span data-ar="التقدّم" data-en="Progress">التقدّم</span>:
            <span id="progress-text" class="text-white font-medium">{{ $analyzed }} / {{ $total }}</span>
        </span>
        <span id="status-badge" class="px-2 py-1 rounded text-xs bg-gray-700 text-gray-300"
              data-ar="متوقفة" data-en="Idle">متوقفة</span>
    </div>
    <div class="w-full bg-gray-800 rounded-full h-2.5">
        <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-500"
             style="width: {{ $total > 0 ? round($analyzed / $total * 100, 1) : 0 }}%"></div>
    </div>
</div>

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <p class="text-gray-400 text-xs mb-1" data-ar="معاملات سليمة" data-en="Legitimate">معاملات سليمة</p>
        <p id="live-legit" class="text-2xl font-bold text-green-400">0</p>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <p class="text-gray-400 text-xs mb-1" data-ar="عمليات احتيال مكتشفة" data-en="Fraud Detected">عمليات احتيال مكتشفة</p>
        <p id="live-fraud" class="text-2xl font-bold text-red-400">0</p>
    </div>
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5">
        <p class="text-gray-400 text-xs mb-1" data-ar="إجمالي المعاملات" data-en="Total Transactions">إجمالي المعاملات</p>
        <p id="live-total" class="text-2xl font-bold text-white">{{ $total }}</p>
    </div>
</div>

<!-- Live feed table -->
<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <div class="px-4 py-3 bg-gray-800 text-gray-400 text-sm font-medium"
         data-ar="آخر المعاملات المكتشَفة" data-en="Latest Detected Transactions">
        آخر المعاملات المكتشَفة
    </div>
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-800/50 text-gray-500 text-xs">
                <th class="text-start px-4 py-2">#</th>
                <th class="text-start px-4 py-2" data-ar="المبلغ"     data-en="Amount">المبلغ</th>
                <th class="text-start px-4 py-2" data-ar="النوع"      data-en="Type">النوع</th>
                <th class="text-start px-4 py-2" data-ar="المرسل"     data-en="Sender">المرسل</th>
                <th class="text-start px-4 py-2" data-ar="المستقبل"   data-en="Receiver">المستقبل</th>
                <th class="text-start px-4 py-2" data-ar="الاحتمالية" data-en="Probability">الاحتمالية</th>
                <th class="text-start px-4 py-2" data-ar="الحالة"     data-en="Status">الحالة</th>
            </tr>
        </thead>
        <tbody id="feed-body">
            <tr id="empty-row">
                <td colspan="7" class="px-4 py-10 text-center text-gray-500"
                    data-ar="اضغط 'بدء المحاكاة' لاستقبال أول معاملة"
                    data-en="Click 'Start Simulation' to receive the first transaction">
                    اضغط 'بدء المحاكاة' لاستقبال أول معاملة
                </td>
            </tr>
        </tbody>
    </table>
</div>

<style>
@keyframes slideIn {
    from { opacity: 0; transform: translateY(-10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.feed-row-new { animation: slideIn 0.4s ease-out; }
.fraud-flash {
    animation: flashRed 1.2s ease-out;
}
@keyframes flashRed {
    0%   { background-color: rgba(220, 38, 38, 0.35); }
    100% { background-color: transparent; }
}
</style>

<!-- Confirm End Modal -->
<div id="end-modal" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-gray-900 border border-gray-700 rounded-2xl max-w-sm w-full p-6 text-center">
        <div class="w-14 h-14 bg-red-900/40 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-white mb-2" data-ar="تأكيد إنهاء المحاكاة" data-en="Confirm End Simulation">تأكيد إنهاء المحاكاة</h3>
        <p class="text-gray-400 text-sm mb-6"
           data-ar="سيتم إيقاف المحاكاة فوراً والانتقال إلى صفحة المعاملات. المعاملات المتبقية غير المحلَّلة ستبقى بانتظار التحليل."
           data-en="The simulation will stop immediately and you'll be redirected to the transactions page. Remaining unanalyzed transactions will stay pending.">
           سيتم إيقاف المحاكاة فوراً والانتقال إلى صفحة المعاملات. المعاملات المتبقية غير المحلَّلة ستبقى بانتظار التحليل.
        </p>
        <div class="flex gap-3">
            <button onclick="closeEndModal()"
                    class="flex-1 bg-gray-800 hover:bg-gray-700 text-gray-300 px-4 py-2.5 rounded-lg text-sm transition"
                    data-ar="تراجع" data-en="Cancel">تراجع</button>
            <button onclick="endSimulation()"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg text-sm font-medium transition"
                    data-ar="نعم، إنهاء" data-en="Yes, End">نعم، إنهاء</button>
        </div>
    </div>
</div>

<script>
const batchId       = "{{ $batchId }}";
const nextUrl       = "{{ route('simulation.next', $batchId) }}";
let total            = {{ $total }};
let analyzed         = {{ $analyzed }};
let fraudCount       = {{ $fraudCount ?? 0 }};
let legitCount       = {{ $legitCount ?? 0 }};
let isRunning         = false;
let intervalHandle    = null;

function getLang() {
    return localStorage.getItem('lang') || 'ar';
}

function toggleSimulation() {
    if (isRunning) {
        stopSimulation();
    } else {
        startSimulation();
    }
}

function startSimulation() {
    if (analyzed >= total) return; // انتهت أصلاً

    isRunning = true;
    updateToggleButton();
    document.getElementById('status-badge').textContent = getLang() === 'en' ? 'Running' : 'قيد التشغيل';
    document.getElementById('status-badge').className   = 'px-2 py-1 rounded text-xs bg-blue-900/60 text-blue-300';

    const speed = parseInt(document.getElementById('speed-select').value);

    fetchNext(); // أول استدعاء فوري
    intervalHandle = setInterval(fetchNext, speed);
}

function stopSimulation() {
    isRunning = false;
    clearInterval(intervalHandle);
    updateToggleButton();
    document.getElementById('status-badge').textContent = getLang() === 'en' ? 'Paused' : 'متوقفة مؤقتاً';
    document.getElementById('status-badge').className   = 'px-2 py-1 rounded text-xs bg-yellow-900/60 text-yellow-300';
}

function updateToggleButton() {
    const label = document.getElementById('toggle-label');
    const btn   = document.getElementById('toggle-btn');
    const lang  = getLang();

    if (isRunning) {
        label.textContent = lang === 'en' ? 'Pause Simulation' : 'إيقاف المحاكاة';
        btn.classList.remove('bg-green-600', 'hover:bg-green-700');
        btn.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
    } else {
        label.textContent = lang === 'en' ? 'Start Simulation' : 'بدء المحاكاة';
        btn.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');
    }
}

function confirmEndSimulation() {
    document.getElementById('end-modal').classList.remove('hidden');
}

function closeEndModal() {
    document.getElementById('end-modal').classList.add('hidden');
}

function endSimulation() {
    isRunning = false;
    clearInterval(intervalHandle);
    window.location.href = "{{ route('transactions.index') }}";
}

async function fetchNext() {
    try {
        const res  = await fetch(nextUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });
        const data = await res.json();

        if (data.done) {
            finishSimulation(data.total, data.analyzed);
            return;
        }

        if (data.error) {
            console.error('Simulation error:', data.error);
            return;
        }

        analyzed = data.analyzed;
        total    = data.total;
        updateProgress();
        prependRow(data.transaction);
        updateCounters(data.transaction.is_fraud);

        if (analyzed >= total) {
            finishSimulation(total, analyzed);
        }
    } catch (e) {
        console.error('Network error during simulation:', e);
    }
}

function updateProgress() {
    document.getElementById('progress-text').textContent = `${analyzed} / ${total}`;
    const pct = total > 0 ? (analyzed / total * 100) : 0;
    document.getElementById('progress-bar').style.width = pct + '%';
    document.getElementById('live-total').textContent = total;
}

function updateCounters(isFraud) {
    if (isFraud) {
        fraudCount++;
    } else {
        legitCount++;
    }
    document.getElementById('live-fraud').textContent = fraudCount;
    document.getElementById('live-legit').textContent = legitCount;
}
function prependRow(tx) {
    const body = document.getElementById('feed-body');
    const emptyRow = document.getElementById('empty-row');
    if (emptyRow) emptyRow.remove();

    const lang    = getLang();
    const isFraud = tx.is_fraud;

    const statusAr = isFraud ? 'احتيال' : 'سليمة';
    const statusEn = isFraud ? 'Fraud'  : 'Legit';
    const statusBg = isFraud
        ? 'bg-red-900/60 text-red-300'
        : 'bg-green-900/60 text-green-300';

    const probColor = tx.fraud_probability > 70
        ? 'bg-red-500'
        : (tx.fraud_probability > 40 ? 'bg-yellow-500' : 'bg-green-500');

    // Show more decimals for very small probabilities so they don't visually
    // collapse to "0%" (the model rarely outputs an exact 0 — this is a
    // display precision fix, not a model change).
    const probDisplay = tx.fraud_probability < 1
        ? tx.fraud_probability.toFixed(4)
        : tx.fraud_probability.toFixed(1);
    const probBarWidth = Math.max(tx.fraud_probability, 1); // keep a visible sliver in the bar

    const tr = document.createElement('tr');
    tr.className = 'border-t border-gray-800 feed-row-new' + (isFraud ? ' fraud-flash' : '');

    tr.innerHTML = `
        <td class="px-4 py-3 text-gray-500">${tx.id}</td>
        <td class="px-4 py-3 text-white font-medium">$${Number(tx.amount).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}</td>
        <td class="px-4 py-3 text-gray-300">${tx.transaction_type ?? '—'}</td>
        <td class="px-4 py-3 text-gray-300 font-mono text-xs">${tx.sender_account ?? '—'}</td>
        <td class="px-4 py-3 text-gray-300 font-mono text-xs">${tx.receiver_account ?? '—'}</td>
        <td class="px-4 py-3">
            <div class="flex items-center gap-2">
                <div class="w-16 bg-gray-700 rounded-full h-1.5">
                    <div class="h-1.5 rounded-full ${probColor}" style="width: ${probBarWidth}%"></div>
                </div>
                <span class="text-xs text-gray-400">${probDisplay}%</span>
            </div>
        </td>
        <td class="px-4 py-3">
            <span class="px-2 py-1 ${statusBg} rounded text-xs"
                  data-ar="${statusAr}" data-en="${statusEn}">
                ${lang === 'en' ? statusEn : statusAr}
            </span>
        </td>
    `;

    body.prepend(tr);

    const rows = body.querySelectorAll('tr');
    if (rows.length > 25) {
        rows[rows.length - 1].remove();
    }
}

function finishSimulation(finalTotal, finalAnalyzed) {
    isRunning = false;
    clearInterval(intervalHandle);
    updateToggleButton();

    const lang = getLang();
    document.getElementById('status-badge').textContent = lang === 'en' ? 'Completed' : 'انتهت';
    document.getElementById('status-badge').className   = 'px-2 py-1 rounded text-xs bg-green-900/60 text-green-300';

    document.getElementById('toggle-btn').disabled = true;
    document.getElementById('toggle-btn').classList.add('opacity-50', 'cursor-not-allowed');
}

document.addEventListener('DOMContentLoaded', () => {
    updateToggleButton();
    if (analyzed >= total && total > 0) {
        document.getElementById('status-badge').textContent = getLang() === 'en' ? 'Completed' : 'انتهت';
        document.getElementById('status-badge').className   = 'px-2 py-1 rounded text-xs bg-green-900/60 text-green-300';
        document.getElementById('toggle-btn').disabled = true;
        document.getElementById('toggle-btn').classList.add('opacity-50', 'cursor-not-allowed');
    }
});
</script>
@endsection