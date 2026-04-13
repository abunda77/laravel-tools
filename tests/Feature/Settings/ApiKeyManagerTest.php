<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\ApiKeyManager;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ApiKeyManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_existing_api_key_without_replacing_stored_secret(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::query()->create([
            'name' => 'downloader_provider',
            'label' => 'Downloader Provider',
            'description' => 'Initial description',
            'value' => 'original-secret',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ApiKeyManager::class)
            ->call('openEdit', $apiKey->id)
            ->set('label', 'Downloader Provider Updated')
            ->set('description', 'Updated description')
            ->set('value', '')
            ->set('isActive', false)
            ->call('save')
            ->assertHasNoErrors();

        $apiKey->refresh();

        $this->assertSame('Downloader Provider Updated', $apiKey->label);
        $this->assertSame('Updated description', $apiKey->description);
        $this->assertSame('original-secret', $apiKey->value);
        $this->assertFalse($apiKey->is_active);
    }

    public function test_user_can_replace_existing_api_key_secret(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::query()->create([
            'name' => 'gemini',
            'label' => 'Gemini',
            'description' => 'Initial description',
            'value' => 'original-secret',
            'is_active' => true,
        ]);

        Livewire::actingAs($user)
            ->test(ApiKeyManager::class)
            ->call('openEdit', $apiKey->id)
            ->set('value', 'replacement-secret')
            ->call('save')
            ->assertHasNoErrors();

        $apiKey->refresh();

        $this->assertSame('replacement-secret', $apiKey->value);
    }
}
