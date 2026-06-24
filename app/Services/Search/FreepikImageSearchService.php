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

class FreepikImageSearchService
{
    public const API_KEY_NAME = 'freepik_provider';

    private const DISABLED_MESSAGE = 'Layanan Freepik sedang dinonaktifkan di dashboard.';

    private const BASE_URL = 'https://api.magnific.com';

    private const MIN_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array{
     *     query: string,
     *     total: int,
     *     resources: array<int, array<string, mixed>>,
     *     pagination: array<string, int>,
     *     responseData: array<int, mixed>
     * }
     */
    public function search(string $query, int $page = 1, int $limit = 12, string $order = 'relevance'): array
    {
        $this->ensureEnabled();
        $query = $this->validateQuery($query);
        $page = $this->validatePage($page);
        $limit = $this->validateLimit($limit);
        $order = $this->validateOrder($order);

        try {
            $response = $this->request()
                ->get('/v1/resources', [
                    'term' => $query,
                    'page' => $page,
                    'limit' => $limit,
                    'order' => $order,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke Magnific resource API. Periksa koneksi internet atau coba beberapa saat lagi.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapSearchResponse($query, $response);
    }

    /**
     * @return array<string, mixed>
     */
    public function getResourceDetails(int $resourceId): array
    {
        $this->ensureEnabled();
        $resourceId = $this->validateResourceId($resourceId);

        try {
            $response = $this->request()
                ->get('/v1/resources/'.$resourceId)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat mengambil detail resource dari Magnific API.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapDetailResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function downloadResourceByFormat(int $resourceId, string $format): array
    {
        $this->ensureEnabled();
        $resourceId = $this->validateResourceId($resourceId);
        $format = $this->validateFormat($format);

        try {
            $response = $this->request()
                ->get('/v1/resources/'.$resourceId.'/download/'.$format)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException(
                'Tidak dapat mengambil download format resource dari Magnific API.',
                previous: $exception,
            );
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        return $this->mapDownloadResponse($response);
    }

    private function request(): PendingRequest
    {
        return $this->http
            ->baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->withHeaders([
                'x-magnific-api-key' => $this->apiKey(),
            ])
            ->timeout($this->timeoutSeconds())
            ->retry($this->retryTimes(), $this->retrySleepMilliseconds());
    }

    private function ensureEnabled(): void
    {
        if (! config('services.freepik.enabled')) {
            throw new RuntimeException(self::DISABLED_MESSAGE);
        }
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

    private function validateQuery(string $query): string
    {
        $query = trim($query);

        if (blank($query)) {
            throw new InvalidArgumentException('Query pencarian Freepik Image tidak boleh kosong.');
        }

        return $query;
    }

    private function validatePage(int $page): int
    {
        if ($page < 1 || $page > 100) {
            throw new InvalidArgumentException('Page harus berada di antara 1 dan 100.');
        }

        return $page;
    }

    private function validateLimit(int $limit): int
    {
        if ($limit < 1 || $limit > 50) {
            throw new InvalidArgumentException('Limit harus berada di antara 1 dan 50.');
        }

        return $limit;
    }

    private function validateOrder(string $order): string
    {
        $order = trim(strtolower($order));

        if (! in_array($order, ['relevance', 'recent'], true)) {
            throw new InvalidArgumentException('Order Freepik Image tidak didukung.');
        }

        return $order;
    }

    private function validateResourceId(int $resourceId): int
    {
        if ($resourceId < 1) {
            throw new InvalidArgumentException('Resource ID tidak valid.');
        }

        return $resourceId;
    }

    private function validateFormat(string $format): string
    {
        $format = trim(strtolower($format));

        if ($format === '') {
            throw new InvalidArgumentException('Format download tidak boleh kosong.');
        }

        return $format;
    }

    /**
     * @return array{
     *     query: string,
     *     total: int,
     *     resources: array<int, array<string, mixed>>,
     *     pagination: array<string, int>,
     *     responseData: array<int, mixed>
     * }
     */
    private function mapSearchResponse(string $query, Response $response): array
    {
        $payload = $this->decodeResponse($response);
        $resources = collect(Arr::wrap(Arr::get($payload, 'data', [])))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->map(fn (array $item): array => $this->mapResource($item))
            ->values()
            ->all();

        return [
            'query' => $query,
            'total' => (int) Arr::get($payload, 'meta.total', count($resources)),
            'resources' => $resources,
            'pagination' => [
                'currentPage' => (int) Arr::get($payload, 'meta.current_page', 1),
                'lastPage' => (int) Arr::get($payload, 'meta.last_page', 1),
                'perPage' => (int) Arr::get($payload, 'meta.per_page', count($resources)),
            ],
            'responseData' => Arr::wrap(Arr::get($payload, 'data', [])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDetailResponse(Response $response): array
    {
        $payload = $this->decodeResponse($response);
        $resource = Arr::get($payload, 'data');

        if (! is_array($resource)) {
            throw new RuntimeException('Detail resource Magnific tidak valid.');
        }

        $mapped = $this->mapResource($resource);
        $mapped['raw'] = $resource;

        return $mapped;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapDownloadResponse(Response $response): array
    {
        $payload = $this->decodeResponse($response);
        $data = Arr::get($payload, 'data');

        if (! is_array($data)) {
            throw new RuntimeException('Response download resource Magnific tidak valid.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    private function mapResource(array $resource): array
    {
        return [
            'id' => (int) Arr::get($resource, 'id', 0),
            'title' => (string) Arr::get($resource, 'title', ''),
            'url' => (string) Arr::get($resource, 'url', ''),
            'filename' => (string) Arr::get($resource, 'filename', ''),
            'imageUrl' => (string) Arr::get($resource, 'image.source.url', ''),
            'imageType' => (string) Arr::get($resource, 'image.type', ''),
            'imageOrientation' => (string) Arr::get($resource, 'image.orientation', ''),
            'imageSize' => (string) Arr::get($resource, 'image.source.size', ''),
            'previewKey' => (string) Arr::get($resource, 'image.source.key', ''),
            'publishedAt' => (string) Arr::get($resource, 'meta.published_at', ''),
            'isNew' => (bool) Arr::get($resource, 'meta.is_new', false),
            'downloads' => (int) Arr::get($resource, 'stats.downloads', 0),
            'likes' => (int) Arr::get($resource, 'stats.likes', 0),
            'authorName' => (string) Arr::get($resource, 'author.name', ''),
            'authorSlug' => (string) Arr::get($resource, 'author.slug', ''),
            'authorAvatar' => (string) Arr::get($resource, 'author.avatar', ''),
            'licenseType' => (string) Arr::get($resource, 'licenses.0.type', ''),
            'availableFormats' => $this->normalizeAvailableFormats(Arr::get($resource, 'meta.available_formats', [])),
        ];
    }

    /**
     * @return array<string, array{total:int,items:array<int, array<string, mixed>>}>
     */
    private function normalizeAvailableFormats(mixed $formats): array
    {
        if (! is_array($formats)) {
            return [];
        }

        return collect($formats)
            ->filter(fn (mixed $value, mixed $key): bool => is_array($value) && is_string($key) && $key !== '')
            ->mapWithKeys(function (array $value, string $key): array {
                return [
                    strtolower($key) => [
                        'total' => (int) Arr::get($value, 'total', 0),
                        'items' => collect(Arr::wrap(Arr::get($value, 'items', [])))
                            ->filter(fn (mixed $item): bool => is_array($item))
                            ->values()
                            ->all(),
                    ],
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(Response $response): array
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Magnific API mengembalikan response yang tidak valid (bukan JSON).');
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
                return 'Magnific API error: '.$message;
            }
        }

        return 'Magnific API error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
