<?php

namespace App\Services\Ai;

use App\Support\Settings\SystemSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use RuntimeException;

class PerplexityClient
{
    private const BASE_URL = 'https://api.perplexity.ai';

    public function __construct(
        private readonly LlmCredentialResolver $credentials,
        private readonly SystemSettings $settings,
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<int, array{title: string|null, url: string, snippet: string|null, source_provider: string}>
     */
    public function search(string $query, int $maxResults = 5): array
    {
        $payload = $this->post('/search', [
            'query' => $query,
            'max_results' => max(1, min($maxResults, 20)),
            'max_tokens_per_page' => 1200,
        ]);

        return collect(Arr::get($payload, 'results', []))
            ->filter(fn (mixed $result): bool => is_array($result) && filled($result['url'] ?? null))
            ->map(fn (array $result): array => [
                'title' => $result['title'] ?? null,
                'url' => $result['url'],
                'snippet' => $result['snippet'] ?? null,
                'source_provider' => 'perplexity',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(string $model, array $messages): ChatResponse
    {
        $payload = $this->post('/chat/completions', [
            'model' => $model,
            'messages' => $messages,
        ]);

        $citations = collect(Arr::get($payload, 'citations', []))
            ->filter(fn (mixed $url): bool => is_string($url) && filled($url))
            ->values()
            ->map(fn (string $url): array => [
                'title' => null,
                'url' => $url,
                'snippet' => null,
                'source_provider' => 'perplexity',
            ])
            ->all();

        return new ChatResponse(
            content: (string) Arr::get($payload, 'choices.0.message.content', ''),
            citations: $citations,
            usage: Arr::get($payload, 'usage', []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        try {
            $response = $this->http
                ->acceptJson()
                ->asJson()
                ->withToken($this->credentials->requireKey('perplexity'))
                ->timeout($this->timeoutSeconds())
                ->retry($this->retryTimes(), $this->retrySleepMilliseconds())
                ->post(self::BASE_URL.$path, $payload)
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Tidak dapat terhubung ke Perplexity API. Periksa koneksi internet atau coba beberapa saat lagi.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException($this->extractErrorMessage($exception->response), previous: $exception);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('Perplexity API mengembalikan response yang tidak valid.');
        }

        return $data;
    }

    private function timeoutSeconds(): int
    {
        return max(10, (int) $this->settings->get('request_timeout_seconds'));
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
            $message = Arr::get($payload, 'error.message')
                ?: Arr::get($payload, 'message')
                ?: Arr::get($payload, 'detail');

            if (is_string($message) && filled($message)) {
                return 'Perplexity API Error: '.$message;
            }
        }

        return 'Perplexity API Error: request gagal dengan status '.($response?->status() ?? 'unknown').'.';
    }
}
