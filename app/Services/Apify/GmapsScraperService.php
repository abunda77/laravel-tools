<?php

namespace App\Services\Apify;

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

class GmapsScraperService
{
    public const API_KEY_NAME = 'apify_provider';

    public const ACTOR_ID = 'sbEjxxfeFlEBHijJS';

    private const BASE_URL = 'https://api.apify.com';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<array<string, mixed>>
     */
    public function scrape(array $input): array
    {
        $payload = $this->normalizeInput($input);

        try {
            $response = $this->request()
                ->post(
                    '/v2/acts/'.self::ACTOR_ID.'/run-sync-get-dataset-items?token='.$this->apiKey().'&format=json&clean=true',
                    $payload,
                )
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API Apify GMaps. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapResponse($response);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->connectTimeout(10)
            ->timeout($this->timeoutSeconds())
            ->retry($this->retryTimes(), $this->retrySleepMilliseconds());
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

    private function apiKey(): string
    {
        $apiKey = ApiKey::query()
            ->active()
            ->where('name', self::API_KEY_NAME)
            ->first()
            ?->value;

        if (blank($apiKey)) {
            throw new RuntimeException(
                'API key Apify belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
            );
        }

        return $apiKey;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function normalizeInput(array $input): array
    {
        $searchQuery = trim((string) ($input['search_query'] ?? ''));
        $gmapsUrl = trim((string) ($input['gmaps_url'] ?? ''));

        if ($searchQuery === '') {
            throw new InvalidArgumentException('Search query tidak boleh kosong.');
        }

        return [
            'search_query' => $searchQuery,
            'gmaps_url' => $gmapsUrl,
            'latitude' => trim((string) ($input['latitude'] ?? '')),
            'longitude' => trim((string) ($input['longitude'] ?? '')),
            'area_width' => (int) ($input['area_width'] ?? 20),
            'area_height' => (int) ($input['area_height'] ?? 20),
            'max_results' => (int) ($input['max_results'] ?? 100),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function mapResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API Apify GMaps mengembalikan response yang tidak valid (bukan JSON array).');
        }

        return collect($payload)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->map(function (array $row): array {
                $normalized = [];

                foreach ($row as $key => $value) {
                    $normalized[(string) $key] = match (true) {
                        is_bool($value) => $value ? 'true' : 'false',
                        is_scalar($value), $value === null => $value,
                        default => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    };
                }

                return $normalized;
            })
            ->values()
            ->all();
    }

    private function extractErrorMessage(?Response $response): string
    {
        $payload = $response?->json();

        if (is_array($payload)) {
            $message = Arr::get($payload, 'error.message')
                ?: Arr::get($payload, 'message')
                ?: Arr::get($payload, 'error')
                ?: Arr::get($payload, 'detail');

            if (is_string($message) && filled($message)) {
                return 'API Apify GMaps error: '.$message;
            }
        }

        return 'API Apify GMaps error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
