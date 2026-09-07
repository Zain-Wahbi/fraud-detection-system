@extends('admin.layout')
@section('title', 'Fraud Bank — Transactions')

@section('content')
<div class="mb-8 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-white" data-ar="المعاملات" data-en="Transactions">المعاملات</h1>
        <p class="text-gray-400 mt-1" data-ar="جميع المعاملات المحللة" data-en="All Analyzed Transactions">جميع المعاملات المحللة</p>
    </div>
    <a href="{{ route('upload.form') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition"
       data-ar="رفع معاملات" data-en="Upload">رفع معاملات</a>
</div>

<form method="GET" class="mb-6 flex gap-3">
    <input type="text" name="search" value="{{ request('search') }}"
           data-placeholder-ar="بحث بالحساب..." data-placeholder-en="Search by account..."
           placeholder="بحث بالحساب..."
           class="bg-gray-900 border border-gray-700 text-white px-4 py-2 rounded-lg text-sm flex-1 focus:outline-none focus:border-blue-500">
    <select name="type" class="bg-gray-900 border border-gray-700 text-white px-4 py-2 rounded-lg text-sm focus:outline-none">
        <option value="" data-ar="الكل" data-en="All">الكل</option>
        <option value="fraud" {{ request('type') === 'fraud' ? 'selected' : '' }} data-ar="احتيالية فقط" data-en="Fraud Only">احتيالية فقط</option>
        <option value="legit"  {{ request('type') === 'legit'  ? 'selected' : '' }} data-ar="سليمة فقط"    data-en="Legit Only">سليمة فقط</option>
    </select>
    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition"
            data-ar="بحث" data-en="Search">بحث</button>
    <a href="{{ route('transactions.index') }}" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm transition"
       data-ar="إعادة تعيين" data-en="Reset">إعادة تعيين</a>
</form>

<div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-800 text-gray-400">
                <th class="text-start px-4 py-3">#</th>
                <th class="text-start px-4 py-3" data-ar="المبلغ"       data-en="Amount">المبلغ</th>
                <th class="text-start px-4 py-3" data-ar="النوع"        data-en="Type">النوع</th>
                <th class="text-start px-4 py-3" data-ar="المرسل"       data-en="Sender">المرسل</th>
                <th class="text-start px-4 py-3" data-ar="المستقبل"     data-en="Receiver">المستقبل</th>
                <th class="text-start px-4 py-3" data-ar="الاحتمالية"   data-en="Probability">الاحتمالية</th>
                <th class="text-start px-4 py-3" data-ar="الحالة"       data-en="Status">الحالة</th>
                <th class="text-start px-4 py-3" data-ar="التفاصيل"     data-en="Details">التفاصيل</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $tx)
            <tr class="border-t border-gray-800 hover:bg-gray-800/50 transition {{ $tx->is_fraud ? 'border-s-2 border-s-red-500' : '' }}">
                <td class="px-4 py-3 text-gray-500">{{ $tx->id }}</td>
                <td class="px-4 py-3 text-white font-medium">${{ number_format($tx->amount, 2) }}</td>
                <td class="px-4 py-3 text-gray-300">{{ $tx->transaction_type ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-300 font-mono text-xs">{{ Str::limit($tx->sender_account, 15) }}</td>
                <td class="px-4 py-3 text-gray-300 font-mono text-xs">{{ Str::limit($tx->receiver_account, 15) }}</td>
                <td class="px-4 py-3">
                    @php
                        // Keep the raw percentage for bar width/color thresholds,
                        // but format the displayed text with more precision for
                        // very small values so they don't collapse visually to "0%".
                        $probRaw = $tx->fraud_probability * 100;
                        $prob    = round($probRaw, 1);
                        $probDisplay = $probRaw < 1
                            ? number_format($probRaw, 4) // e.g. 0.0018%
                            : number_format($probRaw, 1); // e.g. 94.4%
                    @endphp
                    <div class="flex items-center gap-2">
                        <div class="w-16 bg-gray-700 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $prob > 70 ? 'bg-red-500' : ($prob > 40 ? 'bg-yellow-500' : 'bg-green-500') }}"
                                 style="width: {{ max($prob, 1) }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400">{{ $probDisplay }}%</span>
                    </div>
                </td>
                <td class="px-4 py-3">
                    @if($tx->is_fraud)
                        <span class="px-2 py-1 bg-red-900/60 text-red-300 rounded text-xs" data-ar="احتيال" data-en="Fraud">احتيال</span>
                    @else
                        <span class="px-2 py-1 bg-green-900/60 text-green-300 rounded text-xs" data-ar="سليمة" data-en="Legit">سليمة</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('transactions.show', $tx->id) }}" class="text-blue-400 hover:text-blue-300 text-xs"
                       data-ar="عرض" data-en="View">عرض</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-500"
                    data-ar="لا توجد معاملات — ارفع ملف CSV أولاً" data-en="No transactions — upload a CSV file first">
                    لا توجد معاملات — ارفع ملف CSV أولاً
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
            <span data-ar="معاملة" data-en="transactions">معاملة</span>
        </p>
        <div class="flex gap-2">
            @if($transactions->onFirstPage())
                <span class="px-4 py-2 bg-gray-800 text-gray-600 rounded-lg text-sm cursor-not-allowed"
                      data-ar="السابق" data-en="Prev">السابق</span>
            @else
                <a href="{{ $transactions->withQueryString()->previousPageUrl() }}"
                   class="px-4 py-2 bg-gray-800 hover:bg-gray-700 text-gray-300 rounded-lg text-sm transition"
                   data-ar="السابق" data-en="Prev">السابق</a>
            @endif
            <span class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">
                {{ $transactions->currentPage() }} / {{ $transactions->lastPage() }}
            </span>
            @if($transactions->hasMorePages())
                <a href="{{ $transactions->withQueryString()->nextPageUrl() }}"
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