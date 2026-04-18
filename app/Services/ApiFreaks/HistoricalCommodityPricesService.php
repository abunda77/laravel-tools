<?php

namespace App\Services\ApiFreaks;

use Illuminate\Support\Arr;

class HistoricalCommodityPricesService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/commodity/rates/historical';

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $symbols, string $date): array
    {
        $normalizedSymbols = $this->normalizeSymbols($symbols);
        $date = trim($date);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new \InvalidArgumentException('Date harus memakai format YYYY-MM-DD.');
        }

        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT, ['symbols' => implode(',', $normalizedSymbols), 'date' => $date]),
            'API Historical Commodity Prices mengembalikan response yang tidak valid.',
        );

        $this->ensureSuccess($payload, 'API Historical Commodity Prices mengembalikan status gagal.');

        $rows = collect(Arr::get($payload, 'rates', []))
            ->filter(fn (mixed $item, mixed $symbol): bool => is_array($item) && is_scalar($symbol))
            ->map(fn (array $item, string $symbol): array => [
                'symbol' => $symbol,
                'date' => Arr::get($item, 'date'),
                'open' => Arr::get($item, 'open'),
                'high' => Arr::get($item, 'high'),
                'low' => Arr::get($item, 'low'),
                'close' => Arr::get($item, 'close'),
            ])
            ->values()
            ->all();

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'symbols' => implode(',', $normalizedSymbols),
            'date' => Arr::get($payload, 'date', $date),
            'rows' => $rows,
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Historical Commodity Prices';
    }
}
