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

class WilayahApiService
{
    public const API_KEY_NAME = 'apicoid_provider';

    private const BASE_URL = 'https://use.api.co.id';

    private const PATH = '/regional/indonesia';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * Ambil daftar provinsi (opsional filter nama).
     *
     * @return array<string, mixed>
     */
    public function listProvinces(?string $name = null): array
    {
        $name = $this->normalizeName($name);

        try {
            $response = $this->request()
                ->get(self::PATH.'/provinces', array_filter([
                    'name' => $name,
                ]))
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API wilayah Indonesia. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapListResponse('provinces', $response, fn (array $item): array => [
            'code' => (string) Arr::get($item, 'code', ''),
            'name' => (string) Arr::get($item, 'name', ''),
        ]);
    }

    /**
     * Ambil daftar kabupaten/kota dalam satu provinsi.
     *
     * @return array<string, mixed>
     */
    public function listRegencies(string $provinceCode, ?string $name = null): array
    {
        $provinceCode = $this->validateCode($provinceCode, 'kode provinsi');
        $name = $this->normalizeName($name);

        try {
            $response = $this->request()
                ->get(self::PATH.'/provinces/'.$provinceCode.'/regencies', array_filter([
                    'name' => $name,
                ]))
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API wilayah Indonesia. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapListResponse('regencies', $response, fn (array $item): array => [
            'code' => (string) Arr::get($item, 'code', ''),
            'name' => (string) Arr::get($item, 'name', ''),
            'province_code' => (string) Arr::get($item, 'province_code', ''),
            'province' => (string) Arr::get($item, 'province', ''),
        ]);
    }

    /**
     * Ambil daftar kecamatan dalam satu kabupaten/kota.
     *
     * @return array<string, mixed>
     */
    public function listDistricts(string $regencyCode, ?string $name = null): array
    {
        $regencyCode = $this->validateCode($regencyCode, 'kode kabupaten/kota');
        $name = $this->normalizeName($name);

        try {
            $response = $this->request()
                ->get(self::PATH.'/regencies/'.$regencyCode.'/districts', array_filter([
                    'name' => $name,
                ]))
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API wilayah Indonesia. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapListResponse('districts', $response, fn (array $item): array => [
            'code' => (string) Arr::get($item, 'code', ''),
            'name' => (string) Arr::get($item, 'name', ''),
            'regency_code' => (string) Arr::get($item, 'regency_code', ''),
            'regency' => (string) Arr::get($item, 'regency', ''),
            'province_code' => (string) Arr::get($item, 'province_code', ''),
            'province' => (string) Arr::get($item, 'province', ''),
        ]);
    }

    /**
     * Ambil daftar desa/kelurahan dalam satu kecamatan.
     *
     * @return array<string, mixed>
     */
    public function listVillages(string $districtCode, ?string $name = null): array
    {
        $districtCode = $this->validateCode($districtCode, 'kode kecamatan');
        $name = $this->normalizeName($name);

        try {
            $response = $this->request()
                ->get(self::PATH.'/districts/'.$districtCode.'/villages', array_filter([
                    'name' => $name,
                ]))
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API wilayah Indonesia. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapListResponse('villages', $response, fn (array $item): array => [
            'code' => (string) Arr::get($item, 'code', ''),
            'name' => (string) Arr::get($item, 'name', ''),
            'district_code' => (string) Arr::get($item, 'district_code', ''),
            'district' => (string) Arr::get($item, 'district', ''),
            'regency_code' => (string) Arr::get($item, 'regency_code', ''),
            'regency' => (string) Arr::get($item, 'regency', ''),
            'province_code' => (string) Arr::get($item, 'province_code', ''),
            'province' => (string) Arr::get($item, 'province', ''),
            'postal_codes' => Arr::wrap(Arr::get($item, 'postal_codes', [])),
            'is_courier_support' => (bool) Arr::get($item, 'is_courier_support', false),
        ]);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->withHeaders([
                'x-api-co-id' => $this->apiKey(),
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
                'API key wilayah Indonesia belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function normalizeName(?string $name): ?string
    {
        $name = $name === null ? null : trim($name);

        return filled($name) ? $name : null;
    }

    private function validateCode(string $code, string $label): string
    {
        $code = trim($code);

        if ($code === '') {
            throw new InvalidArgumentException(ucfirst($label).' wajib diisi.');
        }

        return $code;
    }

    /**
     * @param  callable(array): array<string, mixed>  $mapItem
     * @return array<string, mixed>
     */
    private function mapListResponse(string $endpoint, Response $response, callable $mapItem): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API wilayah Indonesia mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'is_success') !== true) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API wilayah Indonesia mengembalikan status gagal.'),
            );
        }

        $items = collect(Arr::wrap(Arr::get($payload, 'data', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map($mapItem)
            ->values()
            ->all();

        $paging = (array) Arr::get($payload, 'paging', []);

        return [
            'total' => (int) Arr::get($paging, 'total_item', count($items)),
            'items' => $items,
            'message' => (string) Arr::get($payload, 'message', ''),
            'isSuccess' => true,
            'endpoint' => self::PATH.'/'.$endpoint,
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
                return 'API wilayah Indonesia error: '.$message;
            }
        }

        return 'API wilayah Indonesia error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
