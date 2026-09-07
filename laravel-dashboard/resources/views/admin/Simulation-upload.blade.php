@extends('admin.layout')
@section('title', 'Fraud Bank — Simulation')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold text-white" data-ar="محاكاة حيّة" data-en="Live Simulation">محاكاة حيّة</h1>
    <p class="text-gray-400 mt-1"
       data-ar="ارفع ملف CSV لمحاكاة استقبال المعاملات بشكل حي وتحليلها واحدة تلو الأخرى"
       data-en="Upload a CSV file to simulate live transaction processing, analyzed one at a time">
       ارفع ملف CSV لمحاكاة استقبال المعاملات بشكل حي وتحليلها واحدة تلو الأخرى
    </p>
</div>

<div class="bg-gray-900 border border-gray-800 rounded-xl p-8 max-w-2xl">
    <form method="POST" action="{{ route('simulation.upload') }}" enctype="multipart/form-data">
        @csrf

        <label class="block mb-4">
            <span class="block text-sm text-gray-400 mb-2"
                  data-ar="ملف المعاملات (CSV)" data-en="Transactions File (CSV)">ملف المعاملات (CSV)</span>
            <input type="file" name="csv_file" accept=".csv,.txt" required
                   class="w-full bg-gray-800 border border-gray-700 text-gray-300 px-4 py-3 rounded-lg text-sm
                          file:bg-blue-600 file:text-white file:border-0 file:rounded file:px-3 file:py-1.5 file:me-3 file:cursor-pointer">
        </label>

        <div class="bg-gray-800/50 border border-gray-700 rounded-lg p-4 mb-6 text-sm text-gray-400">
            <p class="font-medium text-gray-300 mb-2" data-ar="الأعمدة المطلوبة:" data-en="Required columns:">الأعمدة المطلوبة:</p>
            <p class="font-mono text-xs leading-relaxed">
                transaction_id, timestamp, amount, transaction_type, merchant_category,
                location, payment_channel, sender_account, receiver_account,
                spending_deviation_score, velocity_score, geo_anomaly_score, time_since_last_transaction
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-4 bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg text-sm">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg text-sm font-medium transition"
                data-ar="رفع وبدء جلسة المحاكاة" data-en="Upload & Start Simulation Session">
            رفع وبدء جلسة المحاكاة
        </button>
    </form>
</div>
@endsection