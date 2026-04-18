<?php

namespace App\Services\ApiFreaks;

use Illuminate\Support\Arr;

class CommoditySymbolsService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/commodity/symbols';

    /**
     * @return array<string, mixed>
     */
    public function fetch(): array
    {
        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT),
            'API Commodity Symbols mengembalikan response yang tidak valid.',
        );

        $symbols = collect(Arr::get($payload, 'symbols', []))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->values()
            ->all();

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'symbols' => $symbols,
            'total' => count($symbols),
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Commodity Symbols';
    }
}
