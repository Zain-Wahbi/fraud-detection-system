<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FraudDetectionService
{
    private string $apiUrl = 'http://127.0.0.1:8001';

    public function predictBatch(array $transactions): array
    {
        $response = Http::timeout(120)
            ->asJson()
            ->post("{$this->apiUrl}/predict-batch", $transactions);

        if ($response->failed()) {
            return [
                'results'     => [],
                'fraud_count' => 0,
                'error'       => 'Fraud API unreachable: ' . $response->status(),
            ];
        }
        return $response->json();
    }

    public function explain(array $transaction): array
    {
        $response = Http::timeout(15)
            ->asJson()
            ->post("{$this->apiUrl}/explain", $transaction);

        if ($response->failed()) {
            return ['reasons' => ['تعذّر الحصول على التفسير'], 'risk_level' => '—'];
        }
        return $response->json();
    }

    public function health(): array
    {
        $response = Http::timeout(5)->get("{$this->apiUrl}/health");
        return $response->failed() ? ['status' => 'offline'] : $response->json();
    }
}