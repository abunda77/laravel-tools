<?php

namespace App\Services\ApiFreaks;

use InvalidArgumentException;

class IpGeolocationService extends ApiFreaksService
{
    public const ENDPOINT = '/v2.0/geolocation/lookup';

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $ip, string $lang, string $fields, string $include): array
    {
        $ip = trim($ip);
        if ($ip === '') {
            throw new InvalidArgumentException('IP address wajib diisi.');
        }

        $query = array_filter([
            'ip' => $ip,
            'lang' => $lang,
            'fields' => $fields,
            'include' => $include,
        ], fn (mixed $value): bool => filled($value));

        $payload = $this->responseJson(
            $this->authorizedGet(self::ENDPOINT, $query),
            'API IP Geolocation mengembalikan response yang tidak valid.',
        );

        return [
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
            'ip' => $payload['ip'] ?? $ip,
            'location' => $payload['location'] ?? [],
            'country_metadata' => $payload['country_metadata'] ?? [],
            'network' => $payload['network'] ?? [],
            'currency' => $payload['currency'] ?? [],
            'asn' => $payload['asn'] ?? [],
            'company' => $payload['company'] ?? [],
            'time_zone' => $payload['time_zone'] ?? [],
        ];
    }

    protected function serviceLabel(): string
    {
        return 'IP Geolocation';
    }
}
