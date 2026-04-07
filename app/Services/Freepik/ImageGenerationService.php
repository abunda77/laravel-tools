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

class ImageGenerationService
{
    private const API_KEY_NAME = 'freepik_provider';

    private const API_URL = 'https://api.freepik.com/v1/ai/text-to-image/z-image';

    private const MIN_TIMEOUT_SECONDS = 5;

    private const MAX_PROMPT_LENGTH = 4096;

    private const IMAGE_SIZES = [
        'square',
        'square_hd',
        'portrait_3_4',
        'portrait_9_16',
        'landscape_4_3',
        'landscape_16_9',
    ];

    private const OUTPUT_FORMATS = [
        'jpeg',
        'png',
    ];

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * Generate an image from text using Freepik Z-Image Turbo model
     *
     * @param  string  $prompt  Text description of the image to generate
     * @param  string  $imageSize  Result image size (e.g., 'square_hd', 'portrait_3_4', 'landscape_16_9')
     * @param  string  $format  Output format ('png' or 'jpeg')
     * @return array Response data containing task_id
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function generate(string $prompt, string $imageSize = 'square_hd', string $format = 'jpeg'): array
    {
        $prompt = $this->validatePrompt($prompt);
        $imageSize = $this->validateImageSize($imageSize);
        $format = $this->validateOutputFormat($format);

        try {
            $response = $this->request()
                ->post(self::API_URL, [
                    'prompt' => $prompt,
                    'image_size' => $imageSize,
                    'output_format' => $format,
                ])
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
     * Check status of generated task
     *
     * @param  string  $taskId  The ID of the task to check
     * @return array Response data containing status and images if complete
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

    /**
     * Get the history/list of all tasks
     *
     * @throws RuntimeException
     */
    public function getTasksHistory(): array
    {
        try {
            $response = $this->request()
                ->get(self::API_URL)
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

    private function validatePrompt(string $prompt): string
    {
        $prompt = trim($prompt);

        if ($prompt === '') {
            throw new InvalidArgumentException('Prompt tidak boleh kosong.');
        }

        if (mb_strlen($prompt) > self::MAX_PROMPT_LENGTH) {
            throw new InvalidArgumentException('Prompt tidak boleh lebih dari '.self::MAX_PROMPT_LENGTH.' karakter.');
        }

        return $prompt;
    }

    private function validateImageSize(string $imageSize): string
    {
        if (! in_array($imageSize, self::IMAGE_SIZES, true)) {
            throw new InvalidArgumentException('Image size tidak didukung: '.$imageSize.'.');
        }

        return $imageSize;
    }

    private function validateOutputFormat(string $format): string
    {
        $format = strtolower(trim($format));

        if (! in_array($format, self::OUTPUT_FORMATS, true)) {
            throw new InvalidArgumentException('Output format tidak didukung: '.$format.'.');
        }

        return $format;
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
