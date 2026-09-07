@extends('admin.layout')
@section('title', 'Fraud Bank — Transaction Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('transactions.index') }}" class="text-gray-400 hover:text-white text-sm flex items-center gap-2 w-fit">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <span data-ar="العودة للمعاملات" data-en="Back to Transactions">العودة للمعاملات</span>
    </a>
</div>

<div class="max-w-3xl space-y-6">

    {{-- Header --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-white">
                <span data-ar="معاملة" data-en="Transaction #">معاملة</span> #{{ $transaction->id }}
            </h1>
            <p class="text-gray-400 text-sm mt-1">{{ $transaction->transaction_at ?? $transaction->created_at }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if($transaction->ignored)
                <span class="px-4 py-2 bg-gray-700 text-gray-300 rounded-lg font-medium text-sm"
                      data-ar="تم التجاهل" data-en="Ignored">تم التجاهل</span>
            @elseif($transaction->is_fraud)
                <span class="px-4 py-2 bg-red-900/60 text-red-300 rounded-lg font-medium"
                      data-ar="⚠ احتيال مكتشف" data-en="⚠ Fraud Detected">⚠ احتيال مكتشف</span>
            @else
                <span class="px-4 py-2 bg-green-900/60 text-green-300 rounded-lg font-medium"
                      data-ar="✓ معاملة سليمة" data-en="✓ Legitimate">✓ معاملة سليمة</span>
            @endif
        </div>
    </div>

    {{-- Fraud Explanation --}}
    @if($transaction->is_fraud && !$transaction->ignored && !empty($explanation))
    <div class="bg-red-950/40 border border-red-800/60 rounded-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-red-300 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                <span data-ar="أسباب التصنيف كاحتيال" data-en="Fraud Classification Reasons">أسباب التصنيف كاحتيال</span>
            </h2>
            <span class="px-3 py-1 rounded-full text-xs font-medium
                {{ in_array($explanation['risk_level'] ?? '', ['Very High','عالي جداً']) ? 'bg-red-900 text-red-200' :
                   (in_array($explanation['risk_level'] ?? '', ['High','عالٍ','عال']) ? 'bg-orange-900 text-orange-200' :
                    'bg-yellow-900 text-yellow-200') }}">
                <span data-ar="مستوى الخطر:" data-en="Risk Level:">مستوى الخطر:</span>
                {{ $explanation['risk_level'] ?? '—' }}
            </span>
        </div>

        <ul class="space-y-2">
            @foreach($explanation['reasons'] ?? [] as $reason)
            <li class="flex items-start gap-2 text-sm text-red-200">
                <span class="text-red-400 mt-0.5">•</span>
                {{ $reason }}
            </li>
            @endforeach
        </ul>

        @php
            $probRaw = ($explanation['probability'] ?? $transaction->fraud_probability) * 100;
            $prob    = round($probRaw, 1);
            $probDisplay = $probRaw < 1 ? number_format($probRaw, 4) : number_format($probRaw, 1);
        @endphp
        <div class="mt-4 pt-4 border-t border-red-800/40">
            <div class="flex justify-between text-xs text-gray-400 mb-1">
                <span data-ar="احتمالية الاحتيال" data-en="Fraud Probability">احتمالية الاحتيال</span>
                <span class="text-red-300 font-bold">{{ $probDisplay }}%</span>
            </div>
            <div class="w-full bg-gray-800 rounded-full h-2">
                <div class="h-2 rounded-full bg-gradient-to-r from-yellow-500 to-red-500 transition-all"
                     style="width: {{ max($prob, 1) }}%"></div>
            </div>
        </div>

        @if(!$transaction->ignored)
        <div class="mt-4 pt-4 border-t border-red-800/40">
            <button onclick="showIgnoreModal()"
                    class="flex items-center gap-2 px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 hover:text-white rounded-lg text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                <span data-ar="تجاهل هذا التصنيف" data-en="Ignore this Classification">تجاهل هذا التصنيف</span>
            </button>
        </div>
        @endif
    </div>
    @endif

    {{-- Ignored Notice --}}
    @if($transaction->ignored)
    <div class="bg-gray-800/50 border border-gray-700 rounded-xl p-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-gray-400 text-sm"
           data-ar="تم تجاهل هذه المعاملة يدوياً من قِبل الأدمن وإعادة تصنيفها كمعاملة سليمة."
           data-en="This transaction was manually ignored by the admin and reclassified as legitimate.">
            تم تجاهل هذه المعاملة يدوياً من قِبل الأدمن وإعادة تصنيفها كمعاملة سليمة.
        </p>
    </div>
    @endif

    {{-- Transaction Details --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-lg font-semibold text-white mb-4"
            data-ar="تفاصيل المعاملة" data-en="Transaction Details">تفاصيل المعاملة</h2>
        <div class="grid grid-cols-2 gap-4">
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="المبلغ" data-en="Amount">المبلغ</div>
                <div class="text-white font-bold text-lg">${{ number_format($transaction->amount, 2) }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="احتمالية الاحتيال" data-en="Fraud Probability">احتمالية الاحتيال</div>
                @php
                    $pRaw = $transaction->fraud_probability * 100;
                    $p    = round($pRaw, 2);
                    $pDisplay = $pRaw < 1 ? number_format($pRaw, 4) : number_format($pRaw, 2);
                @endphp
                <div class="font-bold text-lg {{ $p > 70 ? 'text-red-400' : ($p > 45 ? 'text-yellow-400' : 'text-green-400') }}">
                    {{ $pDisplay }}%
                </div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="حساب المرسل" data-en="Sender Account">حساب المرسل</div>
                <div class="text-white font-mono text-sm">{{ $transaction->sender_account ?? '—' }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="حساب المستقبل" data-en="Receiver Account">حساب المستقبل</div>
                <div class="text-white font-mono text-sm">{{ $transaction->receiver_account ?? '—' }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="نوع المعاملة" data-en="Transaction Type">نوع المعاملة</div>
                <div class="text-white">{{ $transaction->transaction_type ?? '—' }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="قناة الدفع" data-en="Payment Channel">قناة الدفع</div>
                <div class="text-white">{{ $transaction->payment_channel ?? '—' }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="الموقع" data-en="Location">الموقع</div>
                <div class="text-white">{{ $transaction->location ?? '—' }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="النموذج المستخدم" data-en="Model Used">النموذج المستخدم</div>
                <div class="text-white">{{ $transaction->model_used ?? '—' }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="الحد الفاصل (Threshold)" data-en="Decision Threshold">الحد الفاصل (Threshold)</div>
                <div class="text-white">{{ $transaction->threshold_used }}</div>
            </div>
            <div class="bg-gray-800 rounded-lg p-4">
                <div class="text-gray-400 text-xs mb-1" data-ar="وقت المعاملة" data-en="Transaction Time">وقت المعاملة</div>
                <div class="text-white text-sm">{{ $transaction->transaction_at ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Ignore Modal --}}
<div id="ignoreModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" onclick="hideIgnoreModal()"></div>
    <div class="relative bg-gray-900 border border-gray-700 rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-yellow-900/40 border border-yellow-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2"
                data-ar="تأكيد التجاهل" data-en="Confirm Ignore">تأكيد التجاهل</h3>
            <p class="text-gray-400 text-sm leading-relaxed"
               data-ar="هل أنت متأكد من تجاهل تصنيف هذه المعاملة؟ سيتم إعادة تصنيفها كمعاملة سليمة وإزالتها من قائمة الحظر."
               data-en="Are you sure you want to ignore this classification? It will be reclassified as legitimate and removed from the blocklist.">
                هل أنت متأكد من تجاهل تصنيف هذه المعاملة؟<br>
                <span data-ar="سيتم إعادة تصنيفها كـ" data-en="It will be reclassified as">سيتم إعادة تصنيفها كـ</span>
                <span class="text-green-400 font-medium"
                      data-ar="معاملة سليمة" data-en="Legitimate">معاملة سليمة</span>
                <span data-ar="وإزالتها من قائمة الحظر." data-en="and removed from the blocklist.">وإزالتها من قائمة الحظر.</span>
            </p>
        </div>
        <div class="flex gap-3">
            <button onclick="hideIgnoreModal()"
                    class="flex-1 px-4 py-3 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm font-medium transition"
                    data-ar="إلغاء" data-en="Cancel">إلغاء</button>
            <button onclick="confirmIgnore()"
                    class="flex-1 px-4 py-3 bg-yellow-600 hover:bg-yellow-500 text-white rounded-lg text-sm font-medium transition"
                    data-ar="نعم، تجاهل التصنيف" data-en="Yes, Ignore">نعم، تجاهل التصنيف</button>
        </div>
    </div>
</div>

<script>
function showIgnoreModal() {
    document.getElementById('ignoreModal').classList.remove('hidden');
    document.getElementById('ignoreModal').classList.add('flex');
}
function hideIgnoreModal() {
    document.getElementById('ignoreModal').classList.add('hidden');
    document.getElementById('ignoreModal').classList.remove('flex');
}
function confirmIgnore() {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = btn.dataset.ar === 'نعم، تجاهل التصنيف' ? 'جارٍ المعالجة...' : 'Processing...';

    fetch("{{ route('transactions.ignore', $transaction->id) }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            hideIgnoreModal();
            window.location.reload();
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.textContent = btn.dataset.ar || 'نعم، تجاهل التصنيف';
    });
}
</script>
@endsection