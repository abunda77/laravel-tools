<?php

namespace App\Services\Freepik;

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

class ImageToPromptService
{
    private const API_KEY_NAME = 'freepik_provider';

    private const API_URL = 'https://api.freepik.com/v1/ai/image-to-prompt';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * Generate descriptive prompts from an input image URL or base64 string.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function generate(string $image, ?string $webhookUrl = null): array
    {
        $payload = [
            'image' => $this->validateImage($image),
        ];

        if (filled($webhookUrl)) {
            $payload['webhook_url'] = $this->validateWebhookUrl($webhookUrl);
        }

        try {
            $response = $this->request()
                ->post(self::API_URL, $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke Freepik API. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->decodeResponse($response);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function checkStatus(string $taskId): array
    {
        $taskId = $this->validateTaskId($taskId);

        try {
            $response = $this->request()
                ->get(self::API_URL.'/'.rawurlencode($taskId))
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke Freepik API. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->decodeResponse($response);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-freepik-api-key' => $this->apiKey(),
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
                'Freepik API key belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Freepik API mengembalikan response yang tidak valid (bukan JSON).');
        }

        return $payload;
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
                return 'Freepik API Error: '.$message;
            }
        }

        return 'Freepik API Error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }

    private function validateImage(string $image): string
    {
        $image = trim($image);

        if ($image === '') {
            throw new InvalidArgumentException('Image tidak boleh kosong.');
        }

        return $image;
    }

    private function validateWebhookUrl(string $webhookUrl): string
    {
        $webhookUrl = trim($webhookUrl);

        if (! filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Webhook URL tidak valid.');
        }

        return $webhookUrl;
    }

    private function validateTaskId(string $taskId): string
    {
        $taskId = trim($taskId);

        if ($taskId === '') {
            throw new InvalidArgumentException('Task ID tidak boleh kosong.');
        }

        return $taskId;
    }
}
