<?php

namespace App\Services\Tools;

use App\Support\Settings\SystemSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;

class SendWhatsappService
{
    public const ENDPOINT = '/send/message';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function send(
        string $phone,
        string $message,
        ?string $replyMessageId = null,
        bool $isForwarded = false,
        int $duration = 86400,
    ): array {
        $phone = $this->validatePhone($phone);
        $message = $this->validateMessage($message);
        $replyMessageId = $this->sanitizeReplyMessageId($replyMessageId);
        $duration = $this->validateDuration($duration);

        try {
            $response = $this->request()
                ->post(self::ENDPOINT, [
                    'phone' => $phone,
                    'message' => $message,
                    'reply_message_id' => $replyMessageId ?? '',
                    'is_forwarded' => $isForwarded,
                    'duration' => $duration,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API WhatsApp. Periksa koneksi atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapResponse($response, $phone, $message, $replyMessageId, $isForwarded, $duration);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl($this->baseUrl())
            ->acceptJson()
            ->withBasicAuth($this->username(), $this->password())
            ->timeout($this->timeoutSeconds())
            ->retry($this->retryTimes(), $this->retrySleepMilliseconds());
    }

    private function baseUrl(): string
    {
        $baseUrl = trim((string) config('tools.whatsapp.base_url'));

        if (blank($baseUrl)) {
            throw new RuntimeException('WHATSAPP_API_BASE_URL belum diatur di environment.');
        }

        return rtrim($baseUrl, '/');
    }

    private function username(): string
    {
        $username = trim((string) config('tools.whatsapp.username'));

        if (blank($username)) {
            throw new RuntimeException('WHATSAPP_API_USERNAME belum diatur di environment.');
        }

        return $username;
    }

    private function password(): string
    {
        $password = (string) config('tools.whatsapp.password');

        if (blank($password)) {
            throw new RuntimeException('WHATSAPP_API_PASSWORD belum diatur di environment.');
        }

        return $password;
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

    private function validatePhone(string $phone): string
    {
        $phone = trim($phone);

        if (! preg_match('/^[0-9]{8,20}@s\.whatsapp\.net$/', $phone)) {
            throw new InvalidArgumentException('Nomor tujuan harus memakai format WhatsApp JID, contoh 6281310307754@s.whatsapp.net.');
        }

        return $phone;
    }

    private function validateMessage(string $message): string
    {
        $message = trim($message);

        if (blank($message)) {
            throw new InvalidArgumentException('Pesan WhatsApp tidak boleh kosong.');
        }

        return $message;
    }

    private function sanitizeReplyMessageId(?string $replyMessageId): ?string
    {
        $replyMessageId = trim((string) $replyMessageId);

        return filled($replyMessageId) ? $replyMessageId : null;
    }

    private function validateDuration(int $duration): int
    {
        if ($duration < 1) {
            throw new InvalidArgumentException('Duration harus lebih besar dari 0 detik.');
        }

        return $duration;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapResponse(
        Response $response,
        string $phone,
        string $message,
        ?string $replyMessageId,
        bool $isForwarded,
        int $duration,
    ): array {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API WhatsApp mengembalikan response yang tidak valid (bukan JSON).');
        }

        if ((string) Arr::get($payload, 'code') !== 'SUCCESS') {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API WhatsApp mengembalikan status gagal.'),
            );
        }

        return [
            'code' => (string) Arr::get($payload, 'code', ''),
            'message' => (string) Arr::get($payload, 'message', ''),
            'messageId' => (string) Arr::get($payload, 'results.message_id', ''),
            'status' => (string) Arr::get($payload, 'results.status', ''),
            'request' => [
                'phone' => $phone,
                'message' => $message,
                'reply_message_id' => $replyMessageId ?? '',
                'is_forwarded' => $isForwarded,
                'duration' => $duration,
            ],
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
                return 'API WhatsApp error: '.$message;
            }
        }

        return 'API WhatsApp error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
