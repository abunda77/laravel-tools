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

class Image2PromptService
{
    public const API_KEY_NAME = 'downloader_provider';

    private const ENDPOINT = '/tools/img2prompt';

    private const BASE_URL = 'https://api.ferdev.my.id';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array{result: string, author: string}
     */
    public function generate(string $imageLink): array
    {
        $imageLink = $this->validateLink($imageLink);

        try {
            $response = $this->request()
                ->get(self::ENDPOINT, [
                    'link' => $imageLink,
                    'apikey' => $this->apiKey(),
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API Image2Prompt. Periksa koneksi internet atau coba beberapa saat lagi.',
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
                'API key Image2Prompt belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function validateLink(string $link): string
    {
        $link = trim($link);

        if (blank($link)) {
            throw new InvalidArgumentException('URL gambar tidak boleh kosong.');
        }

        if (! filter_var($link, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('URL gambar tidak valid.');
        }

        return $link;
    }

    /**
     * @return array{result: string, author: string}
     */
    private function mapResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API Image2Prompt mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'success') !== true || (int) Arr::get($payload, 'status', 0) >= 400) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API Image2Prompt mengembalikan status gagal.'),
            );
        }

        $result = Arr::get($payload, 'result');

        if (! is_string($result) || blank($result)) {
            throw new RuntimeException('API Image2Prompt tidak mengembalikan prompt yang valid.');
        }

        return [
            'result' => $result,
            'author' => (string) Arr::get($payload, 'author', ''),
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
                return 'API Image2Prompt error: '.$message;
            }
        }

        return 'API Image2Prompt error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
