<?php

namespace App\Services\ApiFreaks;

use Illuminate\Support\Arr;

class DomainWhoisLookupService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/domain/whois/live';

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT, ['domainName' => $domain]),
            'API Domain WHOIS Lookup mengembalikan response yang tidak valid.',
        );

        $this->ensureSuccess($payload, 'API Domain WHOIS Lookup mengembalikan status gagal.');

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'summary' => [
                'domain_name' => Arr::get($payload, 'domain_name'),
                'query_time' => Arr::get($payload, 'query_time'),
                'whois_server' => Arr::get($payload, 'whois_server'),
                'domain_registered' => Arr::get($payload, 'domain_registered'),
                'create_date' => Arr::get($payload, 'create_date'),
                'update_date' => Arr::get($payload, 'update_date'),
                'expiry_date' => Arr::get($payload, 'expiry_date'),
            ],
            'registrar' => Arr::get($payload, 'domain_registrar', []),
            'contacts' => [
                'Registrant' => Arr::get($payload, 'registrant_contact', []),
                'Administrative' => Arr::get($payload, 'administrative_contact', []),
                'Technical' => Arr::get($payload, 'technical_contact', []),
                'Billing' => Arr::get($payload, 'billing_contact', []),
            ],
            'name_servers' => Arr::get($payload, 'name_servers', []),
            'domain_statuses' => Arr::get($payload, 'domain_status', []),
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Domain WHOIS Lookup';
    }
}
