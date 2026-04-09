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

class VideoGenerationService
{
    private const API_KEY_NAME = 'freepik_provider';

    private const GENERATE_URL = 'https://api.freepik.com/v1/ai/video/kling-v3-std';

    private const TASKS_URL = 'https://api.freepik.com/v1/ai/video/kling-v3';

    private const MIN_TIMEOUT_SECONDS = 5;

    private const MAX_PROMPT_LENGTH = 2500;

    private const ASPECT_RATIOS = ['16:9', '9:16', '1:1'];

    private const DURATIONS = ['3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15'];

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generate(
        string $prompt,
        string $aspectRatio = '16:9',
        string $duration = '5',
        string $negativePrompt = 'blur, distort, and low quality',
        bool $generateAudio = true,
        float $cfgScale = 0.5,
    ): array {
        $prompt = $this->validatePrompt($prompt);
        $aspectRatio = $this->validateAspectRatio($aspectRatio);
        $duration = $this->validateDuration($duration);
        $negativePrompt = $this->validateNegativePrompt($negativePrompt);
        $cfgScale = $this->validateCfgScale($cfgScale);

        try {
            $response = $this->request()
                ->post(self::GENERATE_URL, [
                    'prompt' => $prompt,
                    'aspect_ratio' => $aspectRatio,
                    'duration' => $duration,
                    'negative_prompt' => $negativePrompt,
                    'generate_audio' => $generateAudio,
                    'multi_shot' => false,
                    'cfg_scale' => $cfgScale,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke Freepik Video API. Periksa koneksi internet atau coba beberapa saat lagi.',
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
     */
    public function checkStatus(string $taskId): array
    {
        $taskId = $this->validateTaskId($taskId);

        try {
            $response = $this->request()
                ->get(self::TASKS_URL.'/'.rawurlencode($taskId))
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke Freepik Video API. Periksa koneksi internet atau coba beberapa saat lagi.',
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
     */
    public function getTasksHistory(): array
    {
        try {
            $response = $this->request()
                ->get(self::TASKS_URL)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke Freepik Video API. Periksa koneksi internet atau coba beberapa saat lagi.',
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
     * @return list<string>
     */
    public function aspectRatioOptions(): array
    {
        return self::ASPECT_RATIOS;
    }

    /**
     * @return list<string>
     */
    public function durationOptions(): array
    {
        return self::DURATIONS;
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
            throw new RuntimeException('Freepik Video API mengembalikan response yang tidak valid (bukan JSON).');
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
                return 'Freepik Video API Error: '.$message;
            }
        }

        return 'Freepik Video API Error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
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

    private function validateNegativePrompt(string $negativePrompt): string
    {
        $negativePrompt = trim($negativePrompt);

        if ($negativePrompt === '') {
            return 'blur, distort, and low quality';
        }

        if (mb_strlen($negativePrompt) > self::MAX_PROMPT_LENGTH) {
            throw new InvalidArgumentException('Negative prompt tidak boleh lebih dari '.self::MAX_PROMPT_LENGTH.' karakter.');
        }

        return $negativePrompt;
    }

    private function validateAspectRatio(string $aspectRatio): string
    {
        if (! in_array($aspectRatio, self::ASPECT_RATIOS, true)) {
            throw new InvalidArgumentException('Aspect ratio tidak didukung: '.$aspectRatio.'.');
        }

        return $aspectRatio;
    }

    private function validateDuration(string $duration): string
    {
        $duration = trim($duration);

        if (! in_array($duration, self::DURATIONS, true)) {
            throw new InvalidArgumentException('Duration tidak didukung: '.$duration.'.');
        }

        return $duration;
    }

    private function validateCfgScale(float $cfgScale): float
    {
        if ($cfgScale < 0 || $cfgScale > 1) {
            throw new InvalidArgumentException('CFG scale harus berada di antara 0 dan 1.');
        }

        return $cfgScale;
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
