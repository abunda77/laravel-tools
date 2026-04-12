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

class YoutubeSearchService
{
    public const API_KEY_NAME = 'downloader_provider';

    public const ENDPOINT = '/search/youtube';

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
                'Tidak dapat terhubung ke API search Youtube. Periksa koneksi internet atau coba beberapa saat lagi.',
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
                'API key Youtube search belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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
            throw new InvalidArgumentException('Query pencarian Youtube tidak boleh kosong.');
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
            throw new RuntimeException('API Youtube search mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'success') !== true || (int) Arr::get($payload, 'status', 0) >= 400) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API Youtube search mengembalikan status gagal.'),
            );
        }

        $videos = collect(Arr::wrap(Arr::get($payload, 'result', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item, int $index): array => [
                'id' => (string) (Arr::get($item, 'url') ?: 'youtube-'.$index),
                'index' => $index + 1,
                'title' => (string) Arr::get($item, 'title', ''),
                'duration' => (string) Arr::get($item, 'duration', ''),
                'views' => (int) Arr::get($item, 'views', 0),
                'url' => (string) Arr::get($item, 'url', ''),
                'thumbnail' => (string) Arr::get($item, 'thumbnail', ''),
                'uploadDate' => (string) Arr::get($item, 'uploadDate', ''),
                'author' => (string) Arr::get($item, 'author', ''),
            ])
            ->values()
            ->all();

        return [
            'query' => $query,
            'author' => (string) Arr::get($payload, 'author', ''),
            'total' => count($videos),
            'videos' => $videos,
            'responseData' => Arr::wrap(Arr::get($payload, 'result', [])),
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
                return 'API Youtube search error: '.$message;
            }
        }

        return 'API Youtube search error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
