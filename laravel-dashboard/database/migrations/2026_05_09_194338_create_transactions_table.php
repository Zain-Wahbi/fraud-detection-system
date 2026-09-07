<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique()->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('transaction_type')->nullable();
            $table->string('merchant_category')->nullable();
            $table->string('location')->nullable();
            $table->string('payment_channel')->nullable();
            $table->string('sender_account')->nullable();
            $table->string('receiver_account')->nullable();
            $table->boolean('is_fraud')->default(false);
            $table->decimal('fraud_probability', 8, 6)->nullable();
            $table->string('model_used')->nullable();
            $table->decimal('threshold_used', 8, 6)->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->json('raw_features')->nullable();
            $table->timestamp('transaction_at')->nullable();
            $table->timestamps();

            $table->index(['is_fraud', 'created_at']);
            $table->index('sender_account');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};