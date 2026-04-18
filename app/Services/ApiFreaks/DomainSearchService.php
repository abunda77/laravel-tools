<?php

namespace App\Services\ApiFreaks;

class DomainSearchService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/domain/availability';

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $domain, string $source = 'dns'): array
    {
        $domain = $this->normalizeDomain($domain);
        $source = strtolower(trim($source));

        if (! in_array($source, ['dns', 'whois'], true)) {
            throw new \InvalidArgumentException('Source harus salah satu dari dns atau whois.');
        }

        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT, ['domain' => $domain, 'source' => $source]),
            'API Domain Search mengembalikan response yang tidak valid.',
        );

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'domain' => $payload['domain'] ?? $domain,
            'domainAvailability' => $payload['domainAvailability'] ?? null,
            'message' => $payload['message'] ?? null,
            'source' => $source,
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Domain Search';
    }
}
