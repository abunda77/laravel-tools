<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Laravel\Boost\Mcp\Tools\SearchDocs;
use Laravel\Mcp\Request;
use Tests\TestCase;

class SearchDocsToolTest extends TestCase
{
    public function test_search_docs_returns_remote_response_when_http_request_succeeds(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://boost.laravel.com/api/docs' => Http::response('# Remote Search Results', 200),
        ]);

        $response = app(SearchDocs::class)->handle(new Request([
            'queries' => ['routing'],
            'packages' => ['laravel/framework'],
            'token_limit' => 100,
        ]));

        $this->assertFalse($response->isError());
        $this->assertSame('# Remote Search Results', (string) $response->content());
    }

    public function test_search_docs_falls_back_to_local_docs_when_http_request_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://boost.laravel.com/api/docs' => Http::failedConnection('Network unavailable'),
        ]);

        $response = app(SearchDocs::class)->handle(new Request([
            'queries' => ['route model binding'],
            'packages' => ['laravel/framework'],
            'token_limit' => 100,
        ]));

        $this->assertFalse($response->isError());
        $this->assertStringContainsString('Implicit Route Model Binding', (string) $response->content());
    }
}
