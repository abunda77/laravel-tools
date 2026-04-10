<?php

namespace App\Services\Tools;

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

class CekResiService
{
    public const API_KEY_NAME = 'downloader_provider';

    public const ENDPOINT = '/tools/cekresi';

    private const BASE_URL = 'https://api.ferdev.my.id';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function track(string $resi, string $ekspedisi): array
    {
        $resi = $this->validateResi($resi);
        $ekspedisi = $this->validateEkspedisi($ekspedisi);

        try {
            $response = $this->request()
                ->get(self::ENDPOINT, [
                    'resi' => $resi,
                    'ekspedisi' => $ekspedisi,
                    'apikey' => $this->apiKey(),
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API cek resi. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapResponse($resi, $ekspedisi, $response);
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
                'API key cek resi belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function validateResi(string $resi): string
    {
        $resi = trim($resi);

        if (blank($resi)) {
            throw new InvalidArgumentException('Nomor resi tidak boleh kosong.');
        }

        return $resi;
    }

    private function validateEkspedisi(string $ekspedisi): string
    {
        $ekspedisi = strtolower(trim($ekspedisi));

        if (! preg_match('/^[a-z0-9-]+$/', $ekspedisi)) {
            throw new InvalidArgumentException('Kode ekspedisi hanya boleh berisi huruf, angka, dan tanda hubung.');
        }

        return $ekspedisi;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapResponse(string $resi, string $ekspedisi, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API cek resi mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'success') !== true || (int) Arr::get($payload, 'status', 0) >= 400) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API cek resi mengembalikan status gagal.'),
            );
        }

        $data = Arr::get($payload, 'data');

        if (! is_array($data)) {
            throw new RuntimeException('API cek resi tidak mengembalikan data paket yang valid.');
        }

        return [
            'resi' => (string) (Arr::get($data, 'resi') ?: $resi),
            'ekspedisi' => (string) Arr::get($data, 'ekspedisi', ''),
            'ekspedisiCode' => (string) Arr::get($data, 'ekspedisiCode', ''),
            'ekspedisiSlug' => $ekspedisi,
            'status' => (string) Arr::get($data, 'status', ''),
            'tanggalKirim' => (string) Arr::get($data, 'tanggalKirim', ''),
            'customerService' => (string) Arr::get($data, 'customerService', ''),
            'lastPosition' => (string) Arr::get($data, 'lastPosition', ''),
            'shareLink' => (string) Arr::get($data, 'shareLink', ''),
            'history' => $this->mapHistory(Arr::wrap(Arr::get($data, 'history', []))),
            'message' => (string) Arr::get($payload, 'message', ''),
            'endpoint' => self::ENDPOINT,
            'responseData' => $data,
        ];
    }

    /**
     * @param  array<int, mixed>  $history
     * @return array<int, array{tanggal: string, keterangan: string}>
     */
    private function mapHistory(array $history): array
    {
        return collect($history)
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'tanggal' => (string) Arr::get($item, 'tanggal', ''),
                'keterangan' => (string) Arr::get($item, 'keterangan', ''),
            ])
            ->values()
            ->all();
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
                return 'API cek resi error: '.$message;
            }
        }

        return 'API cek resi error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
