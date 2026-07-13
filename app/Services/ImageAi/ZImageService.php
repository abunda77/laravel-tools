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

class ZImageService
{
    public const API_KEY_NAME = 'kieai_provider';

    private const BASE_URL = 'https://api.kie.ai';

    private const CREATE_ENDPOINT = '/api/v1/jobs/createTask';

    private const RECORD_ENDPOINT = '/api/v1/jobs/recordInfo';

    private const MODEL = 'z-image';

    private const MAX_PROMPT_LENGTH = 1000;

    private const ASPECT_RATIOS = [
        '1:1',
        '4:3',
        '3:4',
        '16:9',
        '9:16',
    ];

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * Create a Z-Image generation task.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createTask(string $prompt, string $aspectRatio = '1:1', bool $nsfwChecker = true): array
    {
        $payload = [
            'model' => self::MODEL,
            'input' => [
                'prompt' => $this->validatePrompt($prompt),
                'aspect_ratio' => $this->validateAspectRatio($aspectRatio),
                'nsfw_checker' => $nsfwChecker,
            ],
        ];

        try {
            $response = $this->request()
                ->post(self::CREATE_ENDPOINT, $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API Z-Image. Periksa koneksi internet atau coba beberapa saat lagi.',
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
     * Query task status and results by taskId.
     *
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
                ->get(self::RECORD_ENDPOINT, ['taskId' => $taskId])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API Z-Image. Periksa koneksi internet atau coba beberapa saat lagi.',
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
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->withToken($this->apiKey())
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
                'Z-Image API key belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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
            throw new RuntimeException('Z-Image API mengembalikan response yang tidak valid (bukan JSON).');
        }

        return $payload;
    }

    private function extractErrorMessage(?Response $response): string
    {
        $payload = $response?->json();

        if (is_array($payload)) {
            $message = Arr::get($payload, 'msg')
                ?: Arr::get($payload, 'message')
                ?: Arr::get($payload, 'error.message')
                ?: Arr::get($payload, 'error')
                ?: Arr::get($payload, 'detail');

            if (is_string($message) && filled($message)) {
                return 'Z-Image API error: '.$message;
            }
        }

        return 'Z-Image API error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
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

    private function validateAspectRatio(string $aspectRatio): string
    {
        $aspectRatio = trim($aspectRatio);

        if (! in_array($aspectRatio, self::ASPECT_RATIOS, true)) {
            throw new InvalidArgumentException('Aspect ratio tidak didukung: '.$aspectRatio.'.');
        }

        return $aspectRatio;
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
