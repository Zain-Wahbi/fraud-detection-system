<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('simulation_status')->nullable()->default(null);
            $table->string('simulation_batch_id')->nullable();
            $table->json('queued_payload')->nullable();

            $table->index('simulation_status');
            $table->index('simulation_batch_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['simulation_status', 'simulation_batch_id', 'queued_payload']);
        });
    }
};