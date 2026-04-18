<?php

namespace App\Services\ApiFreaks;

use Illuminate\Support\Arr;

class SubdomainLookupService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/subdomains/lookup';

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT, ['domain' => $domain]),
            'API Subdomain Lookup mengembalikan response yang tidak valid.',
        );

        $this->ensureSuccess($payload, 'API Subdomain Lookup mengembalikan status gagal.');

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'domain' => Arr::get($payload, 'domain', $domain),
            'query_time' => Arr::get($payload, 'query_time'),
            'current_page' => Arr::get($payload, 'current_page'),
            'total_pages' => Arr::get($payload, 'total_pages'),
            'total_records' => Arr::get($payload, 'total_records'),
            'subdomains' => collect(Arr::get($payload, 'subdomains', []))
                ->filter(fn (mixed $item): bool => is_array($item))
                ->values()
                ->all(),
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Subdomain Lookup';
    }
}
