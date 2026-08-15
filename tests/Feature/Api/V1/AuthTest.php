<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
            'device_name' => 'test-device',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->id,
            'name' => 'test-device',
        ]);
    }

    public function test_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson(route('api.auth.login'), [
            'email' => 'test@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson(route('api.auth.login'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.auth.logout'));

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout berhasil.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $user->id,
        ]);
    }

    public function test_can_logout_all_devices(): void
    {
        $user = User::factory()->create();
        $user->createToken('device-1');
        $user->createToken('device-2');
        $token = $user->createToken('device-3')->plainTextToken;

        $this->assertEquals(3, $user->tokens()->count());

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson(route('api.auth.logout-all'));

        $response->assertStatus(200)
            ->assertJson(['message' => 'Semua token berhasil dihapus.']);

        $this->assertEquals(0, $user->tokens()->count());
    }

    public function test_can_get_authenticated_user_info(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.auth.me'));

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                    ],
                ],
            ]);
    }

    public function test_can_get_user_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('device-1');
        $user->createToken('device-2');
        $token = $user->createToken('device-3')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson(route('api.auth.tokens'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'tokens' => [
                        '*' => ['id', 'name', 'abilities', 'last_used_at', 'created_at', 'expires_at'],
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data.tokens');
    }

    public function test_can_revoke_specific_token(): void
    {
        $user = User::factory()->create();
        $tokenToRevoke = $user->createToken('device-to-revoke');
        $currentToken = $user->createToken('current-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$currentToken)
            ->deleteJson(route('api.auth.revoke-token', ['tokenId' => $tokenToRevoke->accessToken->id]));

        $response->assertStatus(200)
            ->assertJson(['message' => 'Token berhasil dihapus.']);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenToRevoke->accessToken->id,
        ]);
    }

    public function test_cannot_revoke_token_that_does_not_exist(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson(route('api.auth.revoke-token', ['tokenId' => 99999]));

        $response->assertStatus(404)
            ->assertJson(['message' => 'Token tidak ditemukan.']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson(route('api.auth.me'));

        $response->assertStatus(401);
    }
}
