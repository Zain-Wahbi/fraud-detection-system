@extends('admin.layout')
@section('title', 'Fraud Bank — Blocklist')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white" data-ar="قائمة الحظر" data-en="Blocklist">قائمة الحظر</h1>
    <p class="text-gray-400 mt-1"
       data-ar="المعاملات المصنّفة كاحتيال والمحظورة تلقائياً"
       data-en="Transactions flagged as fraud and auto-blocked">
       المعاملات المصنّفة كاحتيال والمحظورة تلقائياً
    </p>
</div>

<div class="bg-gray-900 border border-red-900/50 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-red-900/20 text-gray-400">
                <th class="text-start px-4 py-3">#</th>
                <th class="text-start px-4 py-3" data-ar="المبلغ"       data-en="Amount">المبلغ</th>
                <th class="text-start px-4 py-3" data-ar="المرسل"       data-en="Sender">المرسل</th>
                <th class="text-start px-4 py-3" data-ar="المستقبل"     data-en="Receiver">المستقبل</th>
                <th class="text-start px-4 py-3" data-ar="الاحتمالية"   data-en="Probability">الاحتمالية</th>
                <th class="text-start px-4 py-3" data-ar="النوع"        data-en="Type">النوع</th>
                <th class="text-start px-4 py-3" data-ar="التفاصيل"     data-en="Details">التفاصيل</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
            <tr class="border-t border-gray-800 hover:bg-red-900/10 transition">
                <td class="px-4 py-3 text-gray-500">{{ $tx->id }}</td>
                <td class="px-4 py-3 text-white font-medium">${{ number_format($tx->amount, 2) }}</td>
                <td class="px-4 py-3 text-gray-300 font-mono text-xs">{{ $tx->sender_account }}</td>
                <td class="px-4 py-3 text-gray-300 font-mono text-xs">{{ $tx->receiver_account }}</td>
                <td class="px-4 py-3 text-red-400 font-bold">{{ number_format($tx->fraud_probability * 100, ($tx->fraud_probability * 100) < 1 ? 4 : 1) }}%</td>
                <td class="px-4 py-3 text-gray-300">{{ $tx->transaction_type ?? '—' }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('transactions.show', $tx->id) }}" class="text-blue-400 hover:text-blue-300 text-xs"
                       data-ar="عرض" data-en="View">عرض</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-4 py-8 text-center text-gray-500"
                    data-ar="لا توجد معاملات محظورة" data-en="No blocked transactions">
                    لا توجد معاملات محظورة
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="p-4 border-t border-gray-800 flex items-center justify-between">
        <p class="text-sm text-gray-400">
            <span data-ar="عرض" data-en="Showing">عرض</span>
            {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }}
            <span data-ar="من" data-en="of">من</span>
            {{ $transactions->total() }}
        </p>
        <div class="flex gap-2">
            @if($transactions->onFirstPage())
                <span class="px-4 py-2 bg-gray-800 text-gray-600 rounded-lg text-sm cursor-not-allowed"
                      data-ar="السابق" data-en="Prev">السابق</span>
            @else
                <a href="{{ $transactions->previousPageUrl() }}"
                   class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition"
                   data-ar="السابق" data-en="Prev">السابق</a>
            @endif
            <span class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
                {{ $transactions->currentPage() }} / {{ $transactions->lastPage() }}
            </span>
            @if($transactions->hasMorePages())
                <a href="{{ $transactions->nextPageUrl() }}"
                   class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition"
                   data-ar="التالي" data-en="Next">التالي</a>
            @else
                <span class="px-4 py-2 bg-gray-800 text-gray-600 rounded-lg text-sm cursor-not-allowed"
                      data-ar="التالي" data-en="Next">التالي</span>
            @endif
        </div>
    </div>
</div>
@endsection