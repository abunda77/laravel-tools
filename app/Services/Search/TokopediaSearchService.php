<?php

namespace App\Services\Search;

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

class TokopediaSearchService
{
    public const API_KEY_NAME = 'downloader_provider';

    public const ENDPOINT = '/search/tokopedia';

    private const BASE_URL = 'https://api.ferdev.my.id';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function search(string $query): array
    {
        $query = $this->validateQuery($query);

        try {
            $response = $this->request()
                ->get(self::ENDPOINT, [
                    'query' => $query,
                    'apikey' => $this->apiKey(),
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API search Tokopedia. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapResponse($query, $response);
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
                'API key Tokopedia search belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function validateQuery(string $query): string
    {
        $query = trim($query);

        if (blank($query)) {
            throw new InvalidArgumentException('Query pencarian tidak boleh kosong.');
        }

        return $query;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapResponse(string $query, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API Tokopedia search mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'success') !== true || (int) Arr::get($payload, 'status', 0) >= 400) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API Tokopedia search mengembalikan status gagal.'),
            );
        }

        $items = collect(Arr::wrap(Arr::get($payload, 'data', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'id' => (string) Arr::get($item, 'id', ''),
                'name' => (string) Arr::get($item, 'name', ''),
                'price' => (string) Arr::get($item, 'price', ''),
                'priceNumber' => (int) Arr::get($item, 'price_number', 0),
                'shopName' => (string) Arr::get($item, 'shop.name', ''),
                'shopCity' => (string) Arr::get($item, 'shop.city', ''),
                'url' => (string) Arr::get($item, 'url', ''),
                'thumbnail' => (string) Arr::get($item, 'thumbnail', ''),
            ])
            ->values()
            ->all();

        return [
            'query' => $query,
            'author' => (string) Arr::get($payload, 'author', ''),
            'total' => count($items),
            'items' => $items,
            'responseData' => Arr::wrap(Arr::get($payload, 'data', [])),
        ];
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
                return 'API Tokopedia search error: '.$message;
            }
        }

        return 'API Tokopedia search error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
