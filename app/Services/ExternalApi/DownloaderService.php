<?php

namespace App\Services\ExternalApi;

use App\Models\ApiKey;
use App\Support\Settings\SystemSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class DownloaderService
{
    /**
     * Minimum allowed timeout in seconds.
     * Prevents timeout(0) which disables timeout entirely in Guzzle.
     */
    private const MIN_TIMEOUT_SECONDS = 5;

    /**
     * Base URL for the downloader provider API.
     * API key is stored separately in the api_keys table (name: 'downloader_provider').
     */
    public const API_KEY_NAME = 'downloader_provider';

    private const BASE_URL = 'https://api.ferdev.my.id';

    /**
     * @var array<string, array<string, string>>
     */
    private const PROVIDERS = [
        'instagram' => [
            'label' => 'Instagram Downloader',
            'endpoint' => '/downloader/instagram',
        ],
        'tiktok' => [
            'label' => 'TikTok Downloader',
            'endpoint' => '/downloader/tiktok',
        ],
        'facebook' => [
            'label' => 'Facebook Downloader',
            'endpoint' => '/downloader/facebook',
        ],
    ];

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * @return array<string, mixed>
     *
     * @throws InvalidArgumentException  When provider or link is invalid.
     * @throws RuntimeException          When API call fails or returns an error.
     */
    public function execute(string $provider, string $link, ?string $apiKeyOverride = null): array
    {
        // --- Guard: provider ---
        $providerConfig = self::PROVIDERS[$provider] ?? null;

        if ($providerConfig === null) {
            throw new InvalidArgumentException("Unsupported provider [{$provider}].");
        }

        // --- Guard: link URL ---
        if (blank($link)) {
            throw new InvalidArgumentException('URL link tidak boleh kosong.');
        }

        if (! filter_var($link, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException("URL link tidak valid: [{$link}].");
        }

        // --- Base URL: defined as class constant ---
        $baseUrl = self::BASE_URL;

        // --- Guard: API key (from api_keys table, name='downloader_provider') ---
        $apiKey = $apiKeyOverride ?: ApiKey::valueByName(self::API_KEY_NAME);

        if (blank($apiKey)) {
            throw new RuntimeException(
                'API key belum diatur. Tambahkan di Settings → API Keys dengan name "'.self::API_KEY_NAME.'".',
            );
        }

        // --- Timeout: ensure never 0 (which means no timeout in Guzzle) ---
        $timeout = max(self::MIN_TIMEOUT_SECONDS, (int) $this->settings->get('request_timeout_seconds'));

        $startedAt = now();

        try {
            $response = $this->http
                ->acceptJson()
                ->timeout($timeout)
                ->retry(
                    max(0, (int) $this->settings->get('request_retry_times')),
                    max(0, (int) $this->settings->get('request_retry_sleep_ms')),
                )
                ->get($baseUrl.$providerConfig['endpoint'], [
                    'link' => $link,
                    'apikey' => $apiKey,
                ])
                ->throw();

        } catch (ConnectionException $exception) {
            // Network-level failure: DNS error, server unreachable, connection timeout, etc.
            $this->logExecution($provider, $link, null, 'connection_error', now()->diffInMilliseconds($startedAt), $exception->getMessage());

            throw new RuntimeException(
                'Tidak dapat terhubung ke provider API. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );

        } catch (RequestException $exception) {
            // HTTP 4xx / 5xx response
            $message = Arr::get($exception->response?->json() ?? [], 'message')
                ?: $exception->getMessage();

            $this->logExecution($provider, $link, null, 'http_error', now()->diffInMilliseconds($startedAt), $message);

            throw new RuntimeException($message, previous: $exception);
        }

        $payload = $response->json();

        // --- Guard: response must be a valid JSON array ---
        if (! is_array($payload)) {
            throw new RuntimeException('Provider mengembalikan response yang tidak valid (bukan JSON).');
        }

        // --- Guard: API-level error returned as JSON body ---
        $apiStatus = Arr::get($payload, 'status');

        if ($apiStatus === false || $apiStatus === 0 || $apiStatus === 'error' || $apiStatus === 'failed') {
            $errMsg = Arr::get($payload, 'message', 'Provider mengembalikan status error.');

            $this->logExecution($provider, $link, $payload, 'api_error', now()->diffInMilliseconds($startedAt), $errMsg);

            throw new RuntimeException($errMsg);
        }

        // --- Build & return result ---
        // extractDownloadOptions is called once to avoid redundant parsing.
        $downloadOptions = $this->extractDownloadOptions($payload);

        $result = [
            'provider' => $provider,
            'providerLabel' => $providerConfig['label'],
            'endpoint' => $providerConfig['endpoint'],
            'response' => $payload,
            'title' => $this->extractTitle($payload),
            'coverUrl' => $this->extractCoverUrl($payload),
            'downloadOptions' => $downloadOptions,
            'downloadUrl' => $downloadOptions[0]['url'] ?? null,
            'authorName' => $this->extractAuthor($payload),
            'stats' => $this->extractStats($payload),
        ];

        $this->logExecution($provider, $link, $payload, 'success', now()->diffInMilliseconds($startedAt));

        return $result;
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function providers(): array
    {
        return self::PROVIDERS;
    }

    /**
     * Log execution result for audit trail.
     *
     * TODO: Implement when the `execution_histories` table and model are created.
     *       Wire this to App\Models\ExecutionHistory::create([...]).
     *
     * @param  array<mixed>|null  $responsePayload
     */
    private function logExecution(
        string $provider,
        string $link,
        ?array $responsePayload,
        string $status,
        int $durationMs,
        ?string $errorMessage = null,
    ): void {
        // Stub — will be implemented with ExecutionHistory model.
        // Example future implementation:
        //
        // ExecutionHistory::create([
        //     'user_id'          => auth()->id(),
        //     'type'             => 'external_api',
        //     'module_name'      => "downloader.{$provider}",
        //     'request_payload'  => ['link' => $link],
        //     'response_payload' => $responsePayload,
        //     'status'           => $status,
        //     'duration_ms'      => $durationMs,
        //     'error_message'    => $errorMessage,
        //     'executed_at'      => now(),
        // ]);
    }

    /**
     * @return array<string, string|int>
     */
    private function extractStats(array $payload): array
    {
        $data = $payload['data'] ?? [];

        $stats = [];

        foreach ([
            'size' => 'Size',
            'play_count' => 'Play count',
            'share_count' => 'Share count',
            'download_count' => 'Download count',
            'metadata.likeCount' => 'Like count',
            'metadata.commentCount' => 'Comment count',
        ] as $key => $label) {
            $value = Arr::get($data, $key);

            if ($value !== null && $value !== '') {
                $stats[$label] = $value;
            }
        }

        return $stats;
    }

    private function extractTitle(array $payload): string
    {
        $data = $payload['data'] ?? [];

        foreach (['title', 'caption', 'desc', 'description', 'metadata.title'] as $key) {
            $value = Arr::get($data, $key);

            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return 'Untitled result';
    }

    private function extractCoverUrl(array $payload): ?string
    {
        $data = $payload['data'] ?? [];

        foreach (['cover', 'thumbnail', 'thumb', 'image'] as $key) {
            $value = Arr::get($data, $key);

            if (is_string($value) && Str::startsWith($value, ['http://', 'https://'])) {
                return $value;
            }
        }

        return null;
    }

    private function extractAuthor(array $payload): ?string
    {
        $data = $payload['data'] ?? [];

        foreach (['metadata.username', 'author.nickname', 'author.name', 'author.username', 'author.unique_id', 'author'] as $key) {
            $value = Arr::get($data, $key);

            if (is_string($value) && filled($value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    private function extractDownloadOptions(array $payload): array
    {
        $data = $payload['data'] ?? [];
        $downloads = [];

        foreach ([
            'hd' => 'Download HD',
            'hd_url' => 'Download HD',
            'sd' => 'Download SD',
            'dlink' => 'Download (dlink)',
            'play' => 'Download (play)',
            'video' => 'Download (video)',
            'download' => 'Download (download)',
            'url' => 'Download (url)',
            'nowm' => 'Download No Watermark',
            'no_wm' => 'Download No Watermark',
            'noWatermark' => 'Download No Watermark',
            'media.video' => 'Download (media.video)',
            'media.url' => 'Download (media.url)',
            'links.video' => 'Download (links.video)',
            'links.download' => 'Download (links.download)',
        ] as $key => $label) {
            $value = Arr::get($data, $key);

            if (is_string($value) && Str::startsWith($value, ['http://', 'https://'])) {
                $downloads[$value] = [
                    'label' => $label,
                    'url' => $value,
                ];
            }
        }

        $mediaItems = Arr::wrap(Arr::get($data, 'medias', []));

        foreach ($mediaItems as $item) {
            if (! is_array($item)) {
                continue;
            }

            $type = Str::lower((string) Arr::get($item, 'type'));
            $url = Arr::get($item, 'url') ?: Arr::get($item, 'video');

            if (($type === '' || str_contains($type, 'video')) && is_string($url) && Str::startsWith($url, ['http://', 'https://'])) {
                $downloads[$url] = [
                    'label' => 'Download video',
                    'url' => $url,
                ];
            }
        }

        return array_values($downloads);
    }
}
