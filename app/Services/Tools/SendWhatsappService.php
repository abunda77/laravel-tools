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
    public const DEVICES_ENDPOINT = '/devices';

    public const ENDPOINT = '/send/message';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<int, array{id: string, display_name: string, state: string, jid: string, created_at: string}>
     */
    public function devices(): array
    {
        try {
            $response = $this->request()
                ->get(self::DEVICES_ENDPOINT)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat mengambil daftar device WhatsApp. Periksa koneksi atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapDevicesResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function send(
        string $deviceId,
        string $phone,
        string $message,
        ?string $replyMessageId = null,
        bool $isForwarded = false,
        int $duration = 86400,
    ): array {
        $deviceId = $this->validateDeviceId($deviceId);
        $phone = $this->validatePhone($phone);
        $message = $this->validateMessage($message);
        $replyMessageId = $this->sanitizeReplyMessageId($replyMessageId);
        $duration = $this->validateDuration($duration);

        try {
            $response = $this->request()
                ->withHeaders([
                    'X-Device-Id' => $deviceId,
                ])
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

        return $this->mapResponse($response, $deviceId, $phone, $message, $replyMessageId, $isForwarded, $duration);
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

    private function validateDeviceId(string $deviceId): string
    {
        $deviceId = trim($deviceId);

        if (blank($deviceId)) {
            throw new InvalidArgumentException('Device WhatsApp wajib dipilih.');
        }

        return $deviceId;
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
     * @return array<int, array{id: string, display_name: string, state: string, jid: string, created_at: string}>
     */
    private function mapDevicesResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API WhatsApp mengembalikan daftar device yang tidak valid (bukan JSON).');
        }

        if ((string) Arr::get($payload, 'code') !== 'SUCCESS') {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API WhatsApp gagal mengambil daftar device.'),
            );
        }

        return collect(Arr::wrap(Arr::get($payload, 'results', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'id' => (string) Arr::get($item, 'id', ''),
                'display_name' => (string) Arr::get($item, 'display_name', ''),
                'state' => (string) Arr::get($item, 'state', ''),
                'jid' => (string) Arr::get($item, 'jid', ''),
                'created_at' => (string) Arr::get($item, 'created_at', ''),
            ])
            ->filter(fn (array $item): bool => filled($item['id']))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapResponse(
        Response $response,
        string $deviceId,
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
                'device_id' => $deviceId,
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
