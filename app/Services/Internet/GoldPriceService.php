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
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class GoldPriceService
{
    public const API_KEY_NAME = 'goldapi_provider';

    public const ENDPOINT = '/api/XAU/USD';

    public const DEFAULT_HISTORY_DAYS = 7;

    private const BASE_URL = 'https://www.goldapi.io';

    private const TROY_OUNCE_IN_GRAMS = 31.1035;

    private const MIN_TIMEOUT_SECONDS = 5;

    private const CACHE_TTL_MINUTES = 60;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
        private readonly CurrencyExchangeRateService $currencyExchangeRateService,
    ) {}

    /**
     * Harga emas per gram (24k) dalam USD dan IDR untuk N hari kalender terakhir.
     *
     * goldapi.io menyediakan data per tanggal, jadi N hari = N panggilan HTTP.
     * Hasilnya di-cache agar kuota API tidak cepat habis.
     *
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     usdToIdr: float,
     *     days: int,
     *     fetchedAt: string
     * }
     */
    public function history(int $days = self::DEFAULT_HISTORY_DAYS): array
    {
        $days = max(1, min(31, $days));

        return Cache::remember(
            'gold-price.history.'.$days.'.'.now()->format('Y-m-d'),
            now()->addMinutes(self::CACHE_TTL_MINUTES),
            fn (): array => $this->fetchHistory($days),
        );
    }

    /**
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     usdToIdr: float,
     *     days: int,
     *     fetchedAt: string
     * }
     */
    private function fetchHistory(int $days): array
    {
        $apiKey = $this->apiKey();
        $usdToIdr = $this->usdToIdrRate();

        $rows = [];
        $lastError = null;

        for ($offset = 0; $offset < $days; $offset++) {
            $date = now()->subDays($offset);

            $payload = $this->fetchDate($date->format('Ymd'), $apiKey);

            if ($payload === null) {
                $lastError ??= 'Tidak ada data harga emas yang tersedia untuk '.$date->format('d M Y').'.';

                continue;
            }

            $row = $this->mapRow($date->format('Y-m-d'), $payload, $usdToIdr);

            if ($row === null) {
                continue;
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            throw new RuntimeException(
                'Tidak ada data harga emas yang tersedia untuk '.$days.' hari terakhir. '.($lastError ?? ''),
            );
        }

        return [
            'rows' => $rows,
            'usdToIdr' => $usdToIdr,
            'days' => $days,
            'fetchedAt' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>|null Null jika tanggal tanpa data (weekend / libur pasar).
     */
    private function fetchDate(string $date, string $apiKey): ?array
    {
        try {
            $response = $this->request($apiKey)
                ->get(self::ENDPOINT.'/'.$date)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke API harga emas. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $this->extractErrorMessage($exception->response),
                previous: $exception,
            );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return null;
        }

        // goldapi mengembalikan HTTP 200 + field `error` untuk tanggal tanpa data.
        // ponytail: heuristik skip; kalau goldapi ubah bentuk pesannya, cocokkan di sini.
        if (filled(Arr::get($payload, 'error'))) {
            return null;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function mapRow(string $date, array $payload, float $usdToIdr): ?array
    {
        $priceGramUsd = Arr::get($payload, 'price_gram_24k');

        if (! is_numeric($priceGramUsd)) {
            return null;
        }

        $priceGramUsd = (float) $priceGramUsd;
        $priceGramIdr = $priceGramUsd * $usdToIdr;

        $prevCloseUsd = Arr::get($payload, 'prev_close_price');
        $prevCloseGramIdr = is_numeric($prevCloseUsd)
            ? ((float) $prevCloseUsd / self::TROY_OUNCE_IN_GRAMS) * $usdToIdr
            : null;

        $changeIdr = $prevCloseGramIdr === null ? null : $priceGramIdr - $prevCloseGramIdr;
        $changePercent = Arr::get($payload, 'chp');
        $priceOunceUsd = Arr::get($payload, 'price');

        return [
            'date' => $date,
            'priceGramUsd' => $priceGramUsd,
            'priceGramIdr' => $priceGramIdr,
            'changeIdr' => $changeIdr,
            'changePercent' => is_numeric($changePercent) ? (float) $changePercent : null,
            'isUp' => $changeIdr === null || $changeIdr >= 0,
            'priceOunceUsd' => is_numeric($priceOunceUsd) ? (float) $priceOunceUsd : null,
        ];
    }

    private function usdToIdrRate(): float
    {
        $rate = Arr::get($this->currencyExchangeRateService->fetch('USDIDR'), 'rate');

        if (! is_numeric($rate)) {
            throw new RuntimeException('Kurs USDIDR tidak tersedia dari API kurs mata uang.');
        }

        return (float) $rate;
    }

    private function request(string $apiKey): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->withHeaders([
                'x-access-token' => $apiKey,
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
                'API key goldapi.io belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
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

    private function extractErrorMessage(?Response $response): string
    {
        $payload = $response?->json();

        if (is_array($payload)) {
            $message = Arr::get($payload, 'error') ?: Arr::get($payload, 'message');

            if (is_string($message) && filled($message)) {
                return 'API harga emas error: '.$message;
            }
        }

        return 'API harga emas error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
