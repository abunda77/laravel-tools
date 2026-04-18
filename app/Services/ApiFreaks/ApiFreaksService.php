<?php

namespace App\Services\ApiFreaks;

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

abstract class ApiFreaksService
{
    public const API_KEY_NAME = 'apifreaks_provider';

    private const BASE_URL = 'https://api.apifreaks.com';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        protected readonly SystemSettings $settings,
        protected readonly HttpFactory $http,
    ) {}

    protected function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->withHeaders([
                'X-apiKey' => $this->apiKey(),
            ])
            ->timeout($this->timeoutSeconds())
            ->retry($this->retryTimes(), $this->retrySleepMilliseconds());
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function authorizedGet(string $endpoint, array $query = []): Response
    {
        $query['apiKey'] = $this->apiKey();

        try {
            return $this->request()->get($endpoint, $query)->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API '.$this->serviceLabel().'. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }
    }

    protected function apiKey(): string
    {
        $apiKey = ApiKey::query()
            ->active()
            ->where('name', self::API_KEY_NAME)
            ->first()
            ?->value;

        if (blank($apiKey)) {
            throw new RuntimeException(
                'API key ApiFreaks belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
            );
        }

        return $apiKey;
    }

    protected function timeoutSeconds(): int
    {
        return max(self::MIN_TIMEOUT_SECONDS, (int) $this->settings->get('request_timeout_seconds'));
    }

    protected function retryTimes(): int
    {
        return max(0, (int) $this->settings->get('request_retry_times'));
    }

    protected function retrySleepMilliseconds(): int
    {
        return max(0, (int) $this->settings->get('request_retry_sleep_ms'));
    }

    protected function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('/^https?:\/\//', '', $domain) ?? $domain;
        $domain = strtok($domain, '/?#') ?: $domain;

        if (! preg_match('/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z]{2,63}$/', $domain)) {
            throw new InvalidArgumentException('Domain tidak valid. Gunakan format seperti apifreaks.com.');
        }

        return $domain;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizeSymbols(string $symbols): array
    {
        $items = collect(explode(',', strtoupper($symbols)))
            ->map(fn (string $symbol): string => trim($symbol))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($items === []) {
            throw new InvalidArgumentException('Symbols wajib diisi, pisahkan dengan koma jika lebih dari satu.');
        }

        foreach ($items as $symbol) {
            if (! preg_match('/^[A-Z0-9-]+$/', $symbol)) {
                throw new InvalidArgumentException('Format symbols tidak valid. Contoh yang benar: XAU,WTIOIL-SPOT.');
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function ensureSuccess(array $payload, string $fallbackMessage): void
    {
        $status = Arr::get($payload, 'status');
        $success = Arr::get($payload, 'success');

        if ($status === true || $success === true) {
            return;
        }

        throw new RuntimeException($this->payloadMessage($payload, $fallbackMessage));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function payloadMessage(array $payload, string $fallback): string
    {
        $message = Arr::get($payload, 'message')
            ?: Arr::get($payload, 'error.message')
            ?: Arr::get($payload, 'error')
            ?: Arr::get($payload, 'detail');

        return is_string($message) && filled($message) ? $message : $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseJson(Response $response, string $fallbackMessage): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException($fallbackMessage);
        }

        return $payload;
    }

    protected function extractErrorMessage(?Response $response): string
    {
        $payload = $response?->json();

        if (is_array($payload)) {
            return 'API '.$this->serviceLabel().' error: '.$this->payloadMessage(
                $payload,
                'request gagal dengan status '.($response?->status() ?? 'unknown').'.',
            );
        }

        return 'API '.$this->serviceLabel().' error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }

    abstract protected function serviceLabel(): string;
}
