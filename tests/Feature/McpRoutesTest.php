<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class McpRoutesTest extends TestCase
{
    public function test_laravel_boost_mcp_route_is_registered(): void
    {
        $route = collect(Route::getRoutes()->getRoutes())
            ->first(fn ($route): bool => $route->uri() === 'mcp/laravel-boost' && in_array('POST', $route->methods(), true));

        $this->assertNotNull($route);
        $this->assertSame('mcp/laravel-boost', $route->uri());
        $this->assertContains('POST', $route->methods());
    }
}
