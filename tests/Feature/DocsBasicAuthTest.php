<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsBasicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_docs_requires_basic_auth_when_credentials_configured(): void
    {
        config([
            'docs.basic_auth.username' => 'admin',
            'docs.basic_auth.password' => 'secret',
            'app.env' => 'local',
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/docs/api');

        $response->assertStatus(401);
        $response->assertHeader('WWW-Authenticate', 'Basic realm="API Documentation"');
    }

    public function test_docs_accessible_with_valid_basic_auth(): void
    {
        config([
            'docs.basic_auth.username' => 'admin',
            'docs.basic_auth.password' => 'secret',
            'app.env' => 'local',
        ]);

        $user = User::factory()->create();

        $response = $this
            ->withBasicAuth('admin', 'secret')
            ->actingAs($user)
            ->get('/docs/api');

        $response->assertStatus(200);
    }

    public function test_docs_rejects_invalid_basic_auth_credentials(): void
    {
        config([
            'docs.basic_auth.username' => 'admin',
            'docs.basic_auth.password' => 'secret',
            'app.env' => 'local',
        ]);

        $user = User::factory()->create();

        $response = $this
            ->withBasicAuth('admin', 'wrong-password')
            ->actingAs($user)
            ->get('/docs/api');

        $response->assertStatus(401);
    }

    public function test_docs_accessible_without_basic_auth_when_not_configured(): void
    {
        config([
            'docs.basic_auth.username' => null,
            'docs.basic_auth.password' => null,
            'app.env' => 'local',
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/docs/api');

        $response->assertStatus(200);
    }
}
