<?php

namespace App\Services\ApiFreaks;

use Illuminate\Support\Arr;

class DomainWhoisHistoryLookupService extends ApiFreaksService
{
    public const ENDPOINT = '/v1.0/domain/whois/history';

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $domain): array
    {
        $domain = $this->normalizeDomain($domain);

        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT, ['domainName' => $domain]),
            'API Domain WHOIS History mengembalikan response yang tidak valid.',
        );

        $this->ensureSuccess($payload, 'API Domain WHOIS History mengembalikan status gagal.');

        $records = collect(Arr::get($payload, 'whois_domains_historical', []))
            ->filter(fn (mixed $record): bool => is_array($record))
            ->map(fn (array $record): array => [
                'num' => Arr::get($record, 'num'),
                'domain_name' => Arr::get($record, 'domain_name'),
                'query_time' => Arr::get($record, 'query_time'),
                'create_date' => Arr::get($record, 'create_date'),
                'update_date' => Arr::get($record, 'update_date'),
                'expiry_date' => Arr::get($record, 'expiry_date'),
                'registrar_name' => Arr::get($record, 'domain_registrar.registrar_name'),
                'registrant_name' => Arr::get($record, 'registrant_contact.name'),
                'name_servers' => implode(', ', Arr::get($record, 'name_servers', [])),
                'domain_statuses' => implode(', ', Arr::get($record, 'domain_status', [])),
            ])
            ->values()
            ->all();

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'domain' => $domain,
            'total_records' => Arr::get($payload, 'total_records'),
            'records' => $records,
        ];
    }

    protected function serviceLabel(): string
    {
        return 'Domain WHOIS History';
    }
}
