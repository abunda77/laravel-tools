<?php

namespace App\Services\Search;

use App\Models\ApiKey;
use App\Support\Settings\SystemSettings;
use DateInterval;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use RuntimeException;

class YoutubeFinderService
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
     * @return array{
     *     query: string,
     *     videos: array<int, array<string, mixed>>,
     *     nextPageToken: string|null,
     *     totalResults: int,
     *     regionCode: string,
     *     rawItems: array<int, mixed>
     * }
     */
    public function search(string $query, ?string $pageToken = null, int $maxResults = 25): array
    {
        $query = $this->validateQuery($query);
        $maxResults = $this->validateMaxResults($maxResults);

        $params = [
            'part' => 'snippet',
            'q' => $query,
            'type' => 'video',
            'maxResults' => $maxResults,
            'order' => 'relevance',
            'key' => $this->apiKey(),
        ];

        if (filled($pageToken)) {
            $params['pageToken'] = $pageToken;
        }

        try {
            $searchResponse = $this->request()->get('/search', $params)->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Tidak dapat melakukan pencarian YouTube Finder. Periksa koneksi internet.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapSearchResponse($query, $searchResponse);
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

    private function validateQuery(string $query): string
    {
        $query = trim($query);

        if (blank($query)) {
            throw new InvalidArgumentException('Keyword pencarian YouTube tidak boleh kosong.');
        }

        return $query;
    }

    private function validateMaxResults(int $maxResults): int
    {
        if ($maxResults < 1 || $maxResults > 50) {
            throw new InvalidArgumentException('maxResults harus berada di antara 1 dan 50.');
        }

        return $maxResults;
    }

    /**
     * @return array{
     *     query: string,
     *     videos: array<int, array<string, mixed>>,
     *     nextPageToken: string|null,
     *     totalResults: int,
     *     regionCode: string,
     *     rawItems: array<int, mixed>
     * }
     */
    private function mapSearchResponse(string $query, Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('YouTube Finder mengembalikan response pencarian yang tidak valid.');
        }

        $searchItems = collect(Arr::get($payload, 'items', []))
            ->filter(fn (mixed $item): bool => is_array($item) && Arr::get($item, 'id.kind') === 'youtube#video')
            ->values();

        $videoIds = $searchItems
            ->map(fn (array $item): string => (string) Arr::get($item, 'id.videoId', ''))
            ->filter()
            ->values()
            ->all();

        $videoDetails = $this->fetchVideoDetails($videoIds);

        $videos = $searchItems
            ->map(function (array $item, int $index) use ($videoDetails): array {
                $videoId = (string) Arr::get($item, 'id.videoId', '');
                $details = $videoDetails[$videoId] ?? [];
                $snippet = Arr::get($item, 'snippet', []);

                return [
                    'index' => $index + 1,
                    'videoId' => $videoId,
                    'title' => (string) Arr::get($snippet, 'title', ''),
                    'description' => (string) Arr::get($snippet, 'description', ''),
                    'channelId' => (string) Arr::get($snippet, 'channelId', ''),
                    'channelTitle' => (string) Arr::get($snippet, 'channelTitle', ''),
                    'publishedAt' => (string) Arr::get($snippet, 'publishedAt', ''),
                    'thumbnail' => (string) (
                        Arr::get($snippet, 'thumbnails.high.url')
                        ?: Arr::get($snippet, 'thumbnails.medium.url')
                        ?: Arr::get($snippet, 'thumbnails.default.url')
                        ?: ''
                    ),
                    'url' => $videoId !== '' ? 'https://www.youtube.com/watch?v='.$videoId : '',
                    'views' => (int) Arr::get($details, 'views', 0),
                    'likes' => (int) Arr::get($details, 'likes', 0),
                    'comments' => (int) Arr::get($details, 'comments', 0),
                    'duration' => (string) Arr::get($details, 'duration', ''),
                    'definition' => (string) Arr::get($details, 'definition', ''),
                    'dimension' => (string) Arr::get($details, 'dimension', ''),
                    'licensedContent' => (bool) Arr::get($details, 'licensedContent', false),
                ];
            })
            ->all();

        return [
            'query' => $query,
            'videos' => $videos,
            'nextPageToken' => is_string(Arr::get($payload, 'nextPageToken')) ? Arr::get($payload, 'nextPageToken') : null,
            'totalResults' => (int) Arr::get($payload, 'pageInfo.totalResults', 0),
            'regionCode' => (string) Arr::get($payload, 'regionCode', ''),
            'rawItems' => $searchItems->all(),
        ];
    }

    /**
     * @param  array<int, string>  $videoIds
     * @return array<string, array<string, mixed>>
     */
    private function fetchVideoDetails(array $videoIds): array
    {
        if ($videoIds === []) {
            return [];
        }

        try {
            $response = $this->request()->get('/videos', [
                'part' => 'contentDetails,statistics,status',
                'id' => implode(',', $videoIds),
                'maxResults' => count($videoIds),
                'key' => $this->apiKey(),
            ])->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Tidak dapat mengambil detail video YouTube Finder. Periksa koneksi internet.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('YouTube Finder mengembalikan response detail video yang tidak valid.');
        }

        return collect(Arr::get($payload, 'items', []))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->mapWithKeys(function (array $item): array {
                $videoId = (string) Arr::get($item, 'id', '');

                return [
                    $videoId => [
                        'views' => (int) Arr::get($item, 'statistics.viewCount', 0),
                        'likes' => (int) Arr::get($item, 'statistics.likeCount', 0),
                        'comments' => (int) Arr::get($item, 'statistics.commentCount', 0),
                        'duration' => $this->formatDuration((string) Arr::get($item, 'contentDetails.duration', '')),
                        'definition' => (string) Arr::get($item, 'contentDetails.definition', ''),
                        'dimension' => (string) Arr::get($item, 'contentDetails.dimension', ''),
                        'licensedContent' => (bool) Arr::get($item, 'contentDetails.licensedContent', false),
                    ],
                ];
            })
            ->all();
    }

    private function formatDuration(string $duration): string
    {
        if ($duration === '') {
            return '';
        }

        try {
            $interval = new DateInterval($duration);
        } catch (Exception) {
            return $duration;
        }

        $hours = ($interval->d * 24) + $interval->h;
        $minutes = str_pad((string) $interval->i, 2, '0', STR_PAD_LEFT);
        $seconds = str_pad((string) $interval->s, 2, '0', STR_PAD_LEFT);

        if ($hours > 0) {
            return $hours.':'.$minutes.':'.$seconds;
        }

        return ((int) $minutes).':'.$seconds;
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
