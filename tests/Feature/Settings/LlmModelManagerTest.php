<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\LlmModelManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LlmModelManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_providers_include_openrouter(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LlmModelManager::class)
            ->assertSet('provider', 'openai')
            ->call('openAdd')
            ->assertSet('provider', 'openai');

        $component = new LlmModelManager;

        $this->assertContains('openrouter', $component->providers());
    }
}
