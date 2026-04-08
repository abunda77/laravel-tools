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

class CurrencyExchangeRateService
{
    public const API_KEY_NAME = 'apicoid_provider';

    private const BASE_URL = 'https://use.api.co.id';

    private const ENDPOINT = '/currency/exchange-rate';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function fetch(string $pair): array
    {
        $pair = $this->validatePair($pair);

        try {
            $response = $this->request()
                ->get(self::ENDPOINT, [
                    'pair' => $pair,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API kurs mata uang. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapResponse($pair, $response);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->withHeaders([
                'x-api-co-id' => $this->apiKey(),
            ])
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
                'API key API.co.id belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function validatePair(string $pair): string
    {
        $pair = strtoupper(trim($pair));

        if (! preg_match('/^[A-Z]{6}$/', $pair)) {
            throw new InvalidArgumentException('Pair harus terdiri dari 6 huruf kode mata uang, contoh USDIDR.');
        }

        return $pair;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapResponse(string $pair, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API kurs mata uang mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'is_success') !== true) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API kurs mata uang mengembalikan status gagal.'),
            );
        }

        return [
            'pair' => (string) (Arr::get($payload, 'data.pair') ?: $pair),
            'rate' => Arr::get($payload, 'data.rate'),
            'updatedAt' => Arr::get($payload, 'data.updated_at'),
            'lastDataUpdatedAt' => Arr::get($payload, 'last_data_updated_at'),
            'message' => (string) Arr::get($payload, 'message', ''),
            'isSuccess' => true,
            'endpoint' => self::ENDPOINT,
            'response' => $payload,
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
                return 'API kurs mata uang error: '.$message;
            }
        }

        return 'API kurs mata uang error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
