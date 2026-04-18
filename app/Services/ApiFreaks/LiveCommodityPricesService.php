<?php

namespace App\Services\ApiFreaks;

use Illuminate\Support\Arr;

class LiveCommodityPricesService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/commodity/rates/latest';

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $symbols, string $updates, ?string $quote = null): array
    {
        $normalizedSymbols = $this->normalizeSymbols($symbols);
        $updates = strtolower(trim($updates));

        if (! in_array($updates, ['1m', '10m'], true)) {
            throw new \InvalidArgumentException('Updates harus bernilai 1m atau 10m.');
        }

        $query = [
            'symbols' => implode(',', $normalizedSymbols),
            'updates' => $updates,
        ];

        if (filled($quote)) {
            $query['quote'] = strtoupper(trim($quote));
        }

        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT, $query),
            'API Live Commodity Prices mengembalikan response yang tidak valid.',
        );

        $this->ensureSuccess($payload, 'API Live Commodity Prices mengembalikan status gagal.');

        $rates = Arr::get($payload, 'rates', []);
        $metadata = Arr::get($payload, 'metadata', []);

        $rows = collect($rates)
            ->filter(fn (mixed $rate, mixed $symbol): bool => is_scalar($symbol))
            ->map(fn (mixed $rate, string $symbol): array => [
                'symbol' => $symbol,
                'rate' => $rate,
                'unit' => Arr::get($metadata, $symbol.'.unit'),
                'quote' => Arr::get($metadata, $symbol.'.quote'),
            ])
            ->values()
            ->all();

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'symbols' => implode(',', $normalizedSymbols),
            'updates' => $updates,
            'rows' => $rows,
            'timestamp' => Arr::get($payload, 'timestamp'),
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Live Commodity Prices';
    }
}
