<?php

namespace App\Services\Search;

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

class YoutubeChannelService
{
    public const API_KEY_NAME = 'youtubeapi_provider';

    private const BASE_URL = 'https://www.googleapis.com/youtube/v3';

    private const MIN_TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {
    }

    /**
     * Fetch channel info + first page of uploaded videos.
     *
     * @return array{channel: array<string, mixed>, videos: array<int, array<string, mixed>>, nextPageToken: string|null}
     */
    public function fetch(string $channelInput): array
    {
        $channelInput = $this->validateChannelInput($channelInput);
        $channelData = $this->getChannelInfo($channelInput);

        $uploadsPlaylistId = (string) Arr::get($channelData, 'contentDetails.relatedPlaylists.uploads', '');

        $videosData = blank($uploadsPlaylistId)
            ? ['videos' => [], 'nextPageToken' => null]
            : $this->getPlaylistVideos($uploadsPlaylistId);

        return [
            'channel' => $channelData,
            'videos' => $videosData['videos'],
            'nextPageToken' => $videosData['nextPageToken'],
        ];
    }

    /**
     * Fetch a page of videos from the uploads playlist.
     *
     * @return array{videos: array<int, array<string, mixed>>, nextPageToken: string|null}
     */
    public function getPlaylistVideos(string $playlistId, ?string $pageToken = null, int $maxResults = 50): array
    {
        $playlistId = $this->validatePlaylistId($playlistId);
        $maxResults = $this->validateMaxResults($maxResults);

        $params = [
            'part' => 'snippet',
            'playlistId' => $playlistId,
            'maxResults' => $maxResults,
            'key' => $this->apiKey(),
        ];

        if (filled($pageToken)) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $response = $this->request()->get('/playlistItems', $params)->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Tidak dapat mengambil daftar video channel. Periksa koneksi internet.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapPlaylistItemsResponse($response);
    }

    /**
     * Search videos within a channel by keyword (costs 100 quota units per call).
     *
     * @return array{videos: array<int, array<string, mixed>>, nextPageToken: string|null, totalResults: int}
     */
    public function searchVideos(string $channelId, string $keyword, ?string $pageToken = null, int $maxResults = 50): array
    {
        $channelId = $this->validateChannelId($channelId);
        $keyword = $this->validateKeyword($keyword);
        $maxResults = $this->validateMaxResults($maxResults);

        $params = [
            'part' => 'snippet',
            'channelId' => $channelId,
            'q' => $keyword,
            'type' => 'video',
            'maxResults' => $maxResults,
            'key' => $this->apiKey(),
        ];

        if (filled($pageToken)) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $response = $this->request()->get('/search', $params)->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Tidak dapat melakukan pencarian video. Periksa koneksi internet.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapSearchResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function getChannelInfo(string $channelInput): array
    {
        $params = [
            'part' => 'snippet,statistics,contentDetails',
            'key' => $this->apiKey(),
            'maxResults' => 1,
        ];

        if (str_starts_with($channelInput, 'UC')) {
            $params['id'] = $channelInput;
        } else {
            $params['forHandle'] = str_starts_with($channelInput, '@') ? $channelInput : '@'.$channelInput;
        }

        try {
            $response = $this->request()->get('/channels', $params)->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Tidak dapat terhubung ke YouTube API. Periksa koneksi internet.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapChannelResponse($response);
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
                'YouTube Data API key belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "'.self::API_KEY_NAME.'".',
            );
        }

        return $apiKey;
    }

    private function validateChannelInput(string $channelInput): string
    {
        $channelInput = trim($channelInput);

        if (blank($channelInput)) {
            throw new InvalidArgumentException('Channel ID atau handle tidak boleh kosong.');
        }

        return $channelInput;
    }

    private function validatePlaylistId(string $playlistId): string
    {
        $playlistId = trim($playlistId);

        if (blank($playlistId)) {
            throw new InvalidArgumentException('Playlist ID uploads tidak boleh kosong.');
        }

        return $playlistId;
    }

    private function validateChannelId(string $channelId): string
    {
        $channelId = trim($channelId);

        if (blank($channelId)) {
            throw new InvalidArgumentException('Channel ID tidak boleh kosong.');
        }

        return $channelId;
    }

    private function validateKeyword(string $keyword): string
    {
        $keyword = trim($keyword);

        if (blank($keyword)) {
            throw new InvalidArgumentException('Keyword pencarian video tidak boleh kosong.');
        }

        return $keyword;
    }

    private function validateMaxResults(int $maxResults): int
    {
        if ($maxResults < 1 || $maxResults > 50) {
            throw new InvalidArgumentException('maxResults harus berada di antara 1 dan 50.');
        }

        return $maxResults;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapChannelResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('YouTube API mengembalikan response yang tidak valid.');
        }

        $items = Arr::get($payload, 'items', []);

        if (empty($items)) {
            throw new RuntimeException('Channel tidak ditemukan. Pastikan handle atau Channel ID yang dimasukkan benar.');
        }

        $item = $items[0];
        $snippet = Arr::get($item, 'snippet', []);
        $statistics = Arr::get($item, 'statistics', []);

        return [
            'id' => (string) Arr::get($item, 'id', ''),
            'title' => (string) Arr::get($snippet, 'title', ''),
            'description' => (string) Arr::get($snippet, 'description', ''),
            'publishedAt' => (string) Arr::get($snippet, 'publishedAt', ''),
            'country' => (string) Arr::get($snippet, 'country', ''),
            'thumbnail' => (string) (
                Arr::get($snippet, 'thumbnails.high.url')
                ?: Arr::get($snippet, 'thumbnails.medium.url')
                ?: Arr::get($snippet, 'thumbnails.default.url')
                ?: ''
            ),
            'subscriberCount' => (string) Arr::get($statistics, 'subscriberCount', '0'),
            'viewCount' => (string) Arr::get($statistics, 'viewCount', '0'),
            'videoCount' => (string) Arr::get($statistics, 'videoCount', '0'),
            'hiddenSubscriberCount' => (bool) Arr::get($statistics, 'hiddenSubscriberCount', false),
            'contentDetails' => Arr::get($item, 'contentDetails', []),
        ];
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, nextPageToken: string|null}
     */
    private function mapPlaylistItemsResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('YouTube API mengembalikan response daftar video yang tidak valid.');
        }

        $nextPageToken = Arr::get($payload, 'nextPageToken');

        $videos = collect(Arr::get($payload, 'items', []))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item, int $index): array => [
                'index' => $index + 1,
                'videoId' => (string) Arr::get($item, 'snippet.resourceId.videoId', ''),
                'title' => (string) Arr::get($item, 'snippet.title', ''),
                'publishedAt' => (string) Arr::get($item, 'snippet.publishedAt', ''),
                'thumbnail' => (string) (
                    Arr::get($item, 'snippet.thumbnails.high.url')
                    ?: Arr::get($item, 'snippet.thumbnails.medium.url')
                    ?: Arr::get($item, 'snippet.thumbnails.default.url')
                    ?: ''
                ),
                'url' => Arr::get($item, 'snippet.resourceId.videoId')
                    ? 'https://www.youtube.com/watch?v='.Arr::get($item, 'snippet.resourceId.videoId')
                    : '',
            ])
            ->values()
            ->all();

        return [
            'videos' => $videos,
            'nextPageToken' => is_string($nextPageToken) ? $nextPageToken : null,
        ];
    }

    /**
     * @return array{videos: array<int, array<string, mixed>>, nextPageToken: string|null, totalResults: int}
     */
    private function mapSearchResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('YouTube API mengembalikan response pencarian yang tidak valid.');
        }

        $nextPageToken = Arr::get($payload, 'nextPageToken');
        $totalResults = (int) Arr::get($payload, 'pageInfo.totalResults', 0);

        $videos = collect(Arr::get($payload, 'items', []))
            ->filter(fn (mixed $item): bool => is_array($item) && Arr::get($item, 'id.kind') === 'youtube#video')
            ->map(fn (array $item, int $index): array => [
                'index' => $index + 1,
                'videoId' => (string) Arr::get($item, 'id.videoId', ''),
                'title' => (string) Arr::get($item, 'snippet.title', ''),
                'publishedAt' => (string) Arr::get($item, 'snippet.publishedAt', ''),
                'thumbnail' => (string) (
                    Arr::get($item, 'snippet.thumbnails.high.url')
                    ?: Arr::get($item, 'snippet.thumbnails.medium.url')
                    ?: Arr::get($item, 'snippet.thumbnails.default.url')
                    ?: ''
                ),
                'url' => Arr::get($item, 'id.videoId')
                    ? 'https://www.youtube.com/watch?v='.Arr::get($item, 'id.videoId')
                    : '',
            ])
            ->values()
            ->all();

        return [
            'videos' => $videos,
            'nextPageToken' => is_string($nextPageToken) ? $nextPageToken : null,
            'totalResults' => $totalResults,
        ];
    }

    private function extractErrorMessage(?Response $response): string
    {
        $payload = $response?->json();

        if (is_array($payload)) {
            $message = Arr::get($payload, 'error.message')
                ?: Arr::get($payload, 'error.errors.0.message')
                ?: Arr::get($payload, 'message');

            if (is_string($message) && filled($message)) {
                return 'YouTube API error: '.$message;
            }
        }

        return 'YouTube API error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
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
}
