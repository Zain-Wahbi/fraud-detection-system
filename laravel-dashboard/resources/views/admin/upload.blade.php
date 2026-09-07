@extends('admin.layout')
@section('title', 'Fraud Bank — Upload')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white"
        data-ar="رفع معاملات للتحليل" data-en="Upload Transactions for Analysis">رفع معاملات للتحليل</h1>
    <p class="text-gray-400 mt-1"
       data-ar="ارفع ملف CSV يحتوي على المعاملات لتحليلها بالنموذج"
       data-en="Upload a CSV file containing transactions to analyze with the model">
       ارفع ملف CSV يحتوي على المعاملات لتحليلها بالنموذج
    </p>
</div>

<div class="max-w-xl">
    <form action="{{ route('upload.csv') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
            <label class="block mb-4">
                <span class="text-gray-300 text-sm font-medium mb-2 block"
                      data-ar="اختر ملف CSV" data-en="Choose CSV File">اختر ملف CSV</span>
                <input type="file" name="csv_file" accept=".csv"
                       class="block w-full text-sm text-gray-400
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-lg file:border-0
                              file:text-sm file:font-medium
                              file:bg-blue-600 file:text-white
                              hover:file:bg-blue-700 cursor-pointer">
            </label>

            @error('csv_file')
                <p class="text-red-400 text-sm mb-4">{{ $message }}</p>
            @enderror

            <div class="bg-gray-800 rounded-lg p-4 mb-6 text-xs text-gray-400 space-y-1">
                <p class="text-gray-300 font-medium mb-2"
                   data-ar="الأعمدة المطلوبة في الـ CSV:" data-en="Required CSV columns:">الأعمدة المطلوبة في الـ CSV:</p>
                <p>timestamp, amount, transaction_type, merchant_category</p>
                <p>location, payment_channel, sender_account, receiver_account</p>
                <p>spending_deviation_score, velocity_score, geo_anomaly_score</p>
                <p>time_since_last_transaction</p>
            </div>

            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg font-medium transition"
                    data-ar="تحليل المعاملات" data-en="Analyze Transactions">
                تحليل المعاملات
            </button>
        </div>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-400 text-sm"
           data-ar="العودة للوحة التحكم" data-en="Back to Dashboard">العودة للوحة التحكم</a>
    </div>
</div>
@endsection