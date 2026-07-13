<?php

namespace App\Services\Internet;

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

class HolidayService
{
    public const API_KEY_NAME = 'apicoid_provider';

    private const BASE_URL = 'https://use.api.co.id';

    private const ENDPOINT_HOLIDAYS = '/holidays/indonesia';

    private const ENDPOINT_CHECK_DATE = '/holidays/indonesia/check/date';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * Ambil daftar libur nasional untuk tahun tertentu.
     *
     * API tidak menyediakan endpoint "upcoming" terpisah (panggilan ke
     * /holidays/indonesia/upcoming malah dianggap ID hari libur sehingga
     * mengembalikan "Invalid holiday ID"). Data "upcoming" dihitung dari
     * field `days_until` / `is_upcoming` yang sudah disertakan tiap item
     * pada endpoint /holidays/indonesia.
     *
     * @return array<string, mixed>
     */
    public function listHolidays(int $year): array
    {
        $year = $this->validateYear($year);

        try {
            $response = $this->request()
                ->get(self::ENDPOINT_HOLIDAYS, [
                    'year' => $year,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API kalender libur nasional. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapHolidaysResponse($year, $response);
    }

    /**
     * @return array<string, mixed>
     */
    public function checkDate(string $date): array
    {
        $date = $this->validateDate($date);

        try {
            $response = $this->request()
                ->get(self::ENDPOINT_CHECK_DATE, [
                    'date' => $date,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API kalender libur nasional. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapCheckDateResponse($date, $response);
    }

    /**
     * Ambil libur mendatang yang dihitung dari daftar libur tahun berjalan.
     *
     * @return array<string, mixed>
     */
    public function upcomingHolidays(int $limit): array
    {
        $limit = $this->validateLimit($limit);

        $currentYear = (int) now()->year;

        try {
            $response = $this->request()
                ->get(self::ENDPOINT_HOLIDAYS, [
                    'year' => $currentYear,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API kalender libur nasional. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        return $this->mapUpcomingResponse($limit, $response);
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
                'API key API.co.id belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function validateYear(int $year): int
    {
        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Tahun harus antara 2000 sampai 2100.');
        }

        return $year;
    }

    private function validateDate(string $date): string
    {
        $date = trim($date);

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || strtotime($date) === false) {
            throw new InvalidArgumentException('Tanggal harus format YYYY-MM-DD yang valid, contoh 2025-01-01.');
        }

        return $date;
    }

    private function validateLimit(int $limit): int
    {
        if ($limit < 1) {
            $limit = 1;
        }

        if ($limit > 50) {
            $limit = 50;
        }

        return $limit;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapHolidaysResponse(int $year, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API kalender libur nasional mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'is_success') !== true) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API kalender libur nasional mengembalikan status gagal.'),
            );
        }

        $items = collect(Arr::wrap(Arr::get($payload, 'data', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'id' => (int) Arr::get($item, 'id', 0),
                'date' => (string) Arr::get($item, 'date', ''),
                'date_formatted' => (string) Arr::get($item, 'date_formatted', ''),
                'day_of_week' => (string) Arr::get($item, 'day_of_week', ''),
                'name' => (string) Arr::get($item, 'name', ''),
                'type' => (string) Arr::get($item, 'type', ''),
                'year' => (int) Arr::get($item, 'year', $year),
                'is_today' => (bool) Arr::get($item, 'is_today', false),
                'is_upcoming' => (bool) Arr::get($item, 'is_upcoming', false),
                'is_holiday' => (bool) Arr::get($item, 'is_holiday', false),
                'is_joint_holiday' => (bool) Arr::get($item, 'is_joint_holiday', false),
                'is_observance' => (bool) Arr::get($item, 'is_observance', false),
                'days_until' => (int) Arr::get($item, 'days_until', 0),
            ])
            ->values()
            ->all();

        return [
            'year' => $year,
            'total' => count($items),
            'items' => $items,
            'message' => (string) Arr::get($payload, 'message', ''),
            'isSuccess' => true,
            'endpoint' => self::ENDPOINT_HOLIDAYS,
            'response' => $payload,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCheckDateResponse(string $date, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API kalender libur nasional mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'is_success') !== true) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API kalender libur nasional mengembalikan status gagal.'),
            );
        }

        $data = (array) Arr::get($payload, 'data', []);

        $holidays = collect(Arr::wrap(Arr::get($data, 'holidays', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => [
                'name' => (string) Arr::get($item, 'name', ''),
            ])
            ->values()
            ->all();

        return [
            'date' => (string) Arr::get($data, 'date', $date),
            'is_holiday' => (bool) Arr::get($data, 'is_holiday', false),
            'day_of_week' => (string) Arr::get($data, 'day_of_week', ''),
            'is_weekend' => (bool) Arr::get($data, 'is_weekend', false),
            'holidays' => $holidays,
            'message' => (string) Arr::get($payload, 'message', ''),
            'isSuccess' => true,
            'endpoint' => self::ENDPOINT_CHECK_DATE,
            'response' => $payload,
        ];
    }

    /**
     * Hitung libur mendatang dari daftar libur tahun berjalan.
     *
     * API tidak memiliki endpoint upcoming terpisah, sehingga kita filter
     * item dengan `is_upcoming` bernilai true dan urutkan berdasarkan
     * `days_until`, lalu batasi sesuai limit.
     *
     * @return array<string, mixed>
     */
    private function mapUpcomingResponse(int $limit, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('API kalender libur nasional mengembalikan response yang tidak valid (bukan JSON).');
        }

        if (Arr::get($payload, 'is_success') !== true) {
            throw new RuntimeException(
                (string) (Arr::get($payload, 'message') ?: 'API kalender libur nasional mengembalikan status gagal.'),
            );
        }

        $items = collect(Arr::wrap(Arr::get($payload, 'data', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->filter(fn (array $item): bool => (bool) Arr::get($item, 'is_upcoming', false))
            ->sortBy(fn (array $item): int => (int) Arr::get($item, 'days_until', PHP_INT_MAX))
            ->take($limit)
            ->map(fn (array $item): array => [
                'date' => (string) Arr::get($item, 'date', ''),
                'name' => (string) Arr::get($item, 'name', ''),
                'type' => (string) Arr::get($item, 'type', ''),
                'days_until' => (int) Arr::get($item, 'days_until', 0),
            ])
            ->values()
            ->all();

        return [
            'limit' => $limit,
            'total' => count($items),
            'items' => $items,
            'year' => (int) now()->year,
            'message' => (string) Arr::get($payload, 'message', ''),
            'isSuccess' => true,
            'endpoint' => self::ENDPOINT_HOLIDAYS,
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
                return 'API kalender libur nasional error: '.$message;
            }
        }

        return 'API kalender libur nasional error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
