<?php

namespace App\Services\Internet;

use App\Models\ApiKey;
use App\Support\Settings\SystemSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;

class WhoisService
{
    public const API_KEY_NAME = 'downloader_provider';

    public const ENDPOINT = '/internet/whois';

    private const BASE_URL = 'https://api.ferdev.my.id';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function lookup(string $domain): array
    {
        $domain = $this->validateDomain($domain);

        try {
            $response = $this->request()
                ->get(self::ENDPOINT, [
                    'domain' => $domain,
                    'apikey' => $this->apiKey(),
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API Whois. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapResponse($domain, $response);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->timeout($this->timeoutSeconds())
            ->retry($this->retryTimes(), $this->retrySleepMilliseconds());
    }

    private function apiKey(): string
    {
        $apiKey = ApiKey::query()
            ->active()
            ->where('name', self::API_KEY_NAME)
            ->first()
            ?->value;

        if (blank($apiKey)) {
            throw new RuntimeException(
                'API key Whois belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
            );
        }

        return $apiKey;
    }

    private function timeoutSeconds(): int
    {
        return max(self::MIN_TIMEOUT_SECONDS, (int) $this->settings->get('request_timeout_seconds'));
    }

    private function retryTimes(): int
    {
        return max(0, (int) $this->settings->get('request_retry_times'));
    }

    private function retrySleepMilliseconds(): int
    {
        return max(0, (int) $this->settings->get('request_retry_sleep_ms'));
    }

    private function validateDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain) ?? $domain;
        $domain = strtok($domain, '/?#') ?: $domain;

        if (! preg_match('/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/', $domain)) {
            throw new InvalidArgumentException('Domain tidak valid. Gunakan format seperti produkmastah.com.');
        }

        return $domain;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapResponse(string $domain, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API Whois mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'success') !== true || (int) Arr::get($payload, 'status', 0) >= 400) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API Whois mengembalikan status gagal.'),
            );
        }

        $data = Arr::get($payload, 'data');

        if (! is_array($data)) {
            throw new RuntimeException('API Whois tidak mengembalikan data domain yang valid.');
        }

        $result = (string) Arr::get($data, 'result', '');

        return [
            'domain' => (string) (Arr::get($data, 'domain') ?: $domain),
            'result' => $result,
            'summary' => $this->extractSummary($result),
            'endpoint' => self::ENDPOINT,
            'responseData' => $data,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function extractSummary(string $result): array
    {
        $summary = [];

        foreach ([
            'Registrar' => 'Registrar',
            'Creation Date' => 'Creation Date',
            'Registrar Registration Expiration Date' => 'Expiration Date',
            'Updated Date' => 'Updated Date',
            'DNSSEC' => 'DNSSEC',
        ] as $whoisKey => $label) {
            if (preg_match('/^'.preg_quote($whoisKey, '/').':\s*(.+)$/mi', $result, $matches)) {
                $summary[$label] = trim($matches[1]);
            }
        }

        preg_match_all('/^Name Server:\s*(.+)$/mi', $result, $nameServerMatches);

        if (! empty($nameServerMatches[1])) {
            $summary['Name Servers'] = implode(', ', array_map('trim', $nameServerMatches[1]));
        }

        return $summary;
    }

    private function extractErrorMessage(?Response $response): string
    {
        $payload = $response?->json();

        if (is_array($payload)) {
            $message = Arr::get($payload, 'message')
                ?: Arr::get($payload, 'error.message')
                ?: Arr::get($payload, 'error')
                ?: Arr::get($payload, 'detail');

            if (is_string($message) && filled($message)) {
                return 'API Whois error: '.$message;
            }
        }

        return 'API Whois error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
