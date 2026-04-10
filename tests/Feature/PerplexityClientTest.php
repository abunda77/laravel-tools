<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Services\Ai\PerplexityClient;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PerplexityClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_normalized_sources(): void
    {
        $this->configureRequestSettings();
        $this->createPerplexityApiKey();

        Http::fake([
            'https://api.perplexity.ai/search' => Http::response([
                'results' => [
                    [
                        'title' => 'Laravel AI SDK',
                        'url' => 'https://laravel.com/docs/13.x/ai-sdk',
                        'snippet' => 'AI SDK docs.',
                    ],
                ],
            ], 200),
        ]);

        $sources = app(PerplexityClient::class)->search('Laravel AI SDK');

        $this->assertSame('Laravel AI SDK', $sources[0]['title']);
        $this->assertSame('https://laravel.com/docs/13.x/ai-sdk', $sources[0]['url']);
        $this->assertSame('perplexity', $sources[0]['source_provider']);
    }

    public function test_chat_returns_content_and_citations(): void
    {
        $this->configureRequestSettings();
        $this->createPerplexityApiKey();

        Http::fake([
            'https://api.perplexity.ai/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Jawaban [1].',
                        ],
                    ],
                ],
                'citations' => [
                    'https://example.com/source',
                ],
                'usage' => [
                    'total_tokens' => 20,
                ],
            ], 200),
        ]);

        $response = app(PerplexityClient::class)->chat('sonar-pro', [
            ['role' => 'user', 'content' => 'Cari data terbaru.'],
        ]);

        $this->assertSame('Jawaban [1].', $response->content);
        $this->assertSame('https://example.com/source', $response->citations[0]['url']);
        $this->assertSame(20, $response->usage['total_tokens']);
    }

    private function configureRequestSettings(): void
    {
        app(SystemSettings::class)->putMany([
            'request_timeout_seconds' => 30,
            'request_retry_times' => 0,
            'request_retry_sleep_ms' => 100,
            'queue_connection' => 'database',
        ]);
    }

    private function createPerplexityApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'perplexity',
            'label' => 'Perplexity',
            'value' => 'test-perplexity-key',
            'is_active' => true,
        ]);
    }
}
