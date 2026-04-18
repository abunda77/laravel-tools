<?php

namespace App\Services\ApiFreaks;

class CreditUsageService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/credits/usage/info';

    /**
     * @return array<string, mixed>
     */
    public function fetch(): array
    {
        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT),
            'API Credit Usage mengembalikan response yang tidak valid.',
        );

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'rows' => collect($payload)
                ->map(fn (mixed $value, string $key): array => [
                    'field' => $key,
                    'value' => is_bool($value) ? ($value ? 'true' : 'false') : (is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_SLASHES)),
                ])
                ->values()
                ->all(),
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Credit Usage';
    }
}
