<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CurrencyConverter
{
    private const API_URL = 'https://api.frankfurter.app';
    private const CACHE_TTL = 3600; // 1 hour

    public function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $rate = $this->getRate($from, $to);

        return round($amount * $rate, 2);
    }

    public function getRate(string $from, string $to): float
    {
        $cacheKey = "exchange_rate_{$from}_{$to}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($from, $to) {
            try {
                $response = Http::get(self::API_URL . '/latest', [
                    'from' => $from,
                    'to' => $to,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['rates'][$to] ?? 1.0;
                }
            } catch (\Exception $e) {
                \Log::warning("Currency conversion failed: {$e->getMessage()}");
            }

            // Fallback rates (approximate)
            return $this->getFallbackRate($from, $to);
        });
    }

    private function getFallbackRate(string $from, string $to): float
    {
        $fallbackRates = [
            'CAD_EUR' => 0.68,
            'EUR_CAD' => 1.47,
            'USD_EUR' => 0.92,
            'EUR_USD' => 1.09,
            'CAD_USD' => 0.74,
            'USD_CAD' => 1.35,
        ];

        return $fallbackRates["{$from}_{$to}"] ?? 1.0;
    }

    public function convertToEur(float $amount, string $from): float
    {
        return $this->convert($amount, $from, 'EUR');
    }

    public function convertToCad(float $amount, string $from): float
    {
        return $this->convert($amount, $from, 'CAD');
    }
}
