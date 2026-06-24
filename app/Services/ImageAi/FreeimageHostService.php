<?php

namespace App\Services\ImageAi;

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

class FreeimageHostService
{
    public const API_KEY_NAME = 'freeimage_host';

    private const ENDPOINT = '/api/1/upload';

    private const BASE_URL = 'https://freeimage.host';

    private const MIN_TIMEOUT_SECONDS = 30;

    private const MAX_FILE_SIZE_KB = 5120;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array{url: string, displayUrl: string, size: int, mime: string}
     */
    public function uploadFromBase64(string $base64, string $mimeType = 'image/jpeg'): array
    {
        $this->validateBase64($base64);

        try {
            $response = $this->request()
                ->asMultipart()
                ->post(self::ENDPOINT, [
                    [
                        'name' => 'key',
                        'contents' => $this->apiKey(),
                    ],
                    [
                        'name' => 'action',
                        'contents' => 'upload',
                    ],
                    [
                        'name' => 'source',
                        'contents' => $base64,
                    ],
                    [
                        'name' => 'format',
                        'contents' => 'json',
                    ],
                ]);

            if ($response->failed()) {
                throw new RequestException($response);
            }
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke Freeimage.host. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapResponse($response);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
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
                'API key Freeimage.host belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function validateBase64(string $base64): void
    {
        if (blank($base64)) {
            throw new InvalidArgumentException('Base64 data tidak boleh kosong.');
        }

        $decodedSize = strlen(base64_decode($base64, true) ?: '');
        $maxBytes = self::MAX_FILE_SIZE_KB * 1024;

        if ($decodedSize > $maxBytes) {
            throw new InvalidArgumentException('Ukuran file melebihi batas maksimum '.self::MAX_FILE_SIZE_KB.' KB.');
        }
    }

    /**
     * @return array{url: string, displayUrl: string, size: int, mime: string}
     */
    private function mapResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Freeimage.host mengembalikan response yang tidak valid (bukan JSON).');
        }

        $statusCode = (int) Arr::get($payload, 'status_code', 0);

        if ($statusCode >= 400 || Arr::get($payload, 'status_txt') !== 'OK') {
            $errorMsg = Arr::get($payload, 'error.message')
                ?: Arr::get($payload, 'error')
                ?: Arr::get($payload, 'success.message')
                ?: 'Upload ke Freeimage.host gagal.';

            throw new RuntimeException((string) $errorMsg);
        }

        $imageUrl = Arr::get($payload, 'image.url');
        $displayUrl = Arr::get($payload, 'image.display_url') ?: Arr::get($payload, 'image.url_viewer');

        if (! is_string($imageUrl) || blank($imageUrl)) {
            throw new RuntimeException('Freeimage.host tidak mengembalikan URL gambar yang valid.');
        }

        return [
            'url' => $imageUrl,
            'displayUrl' => (string) ($displayUrl ?: $imageUrl),
            'size' => (int) Arr::get($payload, 'image.size', 0),
            'mime' => (string) Arr::get($payload, 'image.mime', ''),
        ];
    }

    private function extractErrorMessage(?Response $response): string
    {
        $payload = $response?->json();

        if (is_array($payload)) {
            $message = Arr::get($payload, 'error.message')
                ?: Arr::get($payload, 'error')
                ?: Arr::get($payload, 'message')
                ?: Arr::get($payload, 'detail');

            if (is_string($message) && filled($message)) {
                return 'Freeimage.host error: '.$message;
            }
        }

        return 'Freeimage.host error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
