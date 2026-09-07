<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_id',
        'amount',
        'transaction_type',
        'merchant_category',
        'location',
        'payment_channel',
        'sender_account',
        'receiver_account',
        'is_fraud',
        'fraud_probability',
        'model_used',
        'threshold_used',
        'is_blocked',
        'raw_features',
        'transaction_at',
        'ignored',
        'ignore_reason',
        'simulation_status',
        'simulation_batch_id',
        'queued_payload',
    ];

    protected $casts = [
        'is_fraud'        => 'boolean',
        'is_blocked'      => 'boolean',
        'ignored'         => 'boolean',
        'raw_features'    => 'array',
        'queued_payload'  => 'array',
        'transaction_at'  => 'datetime',
    ];
}