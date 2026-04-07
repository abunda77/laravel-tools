<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\GeneralSettings;
use App\Models\AppSetting;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_requires_authentication(): void
    {
        $response = $this->get(route('settings'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_settings_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings'));

        $response->assertOk();
        $response->assertSeeLivewire(GeneralSettings::class);
    }

    public function test_authenticated_user_can_save_settings(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GeneralSettings::class)
            ->set('providerBaseUrl', 'https://api.example.com')
            ->set('providerApiKey', 'secret-token')
            ->set('requestTimeoutSeconds', 45)
            ->set('requestRetryTimes', 3)
            ->set('requestRetrySleepMs', 750)
            ->set('queueConnection', 'database')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('app_settings', [
            'key' => 'provider_base_url',
            'value' => 'https://api.example.com',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'key' => 'request_timeout_seconds',
            'value' => '45',
        ]);

        $storedApiKey = AppSetting::query()->where('key', 'provider_api_key')->value('value');

        $this->assertNotNull($storedApiKey);
        $this->assertNotSame('secret-token', $storedApiKey);
        $this->assertSame('secret-token', app(SystemSettings::class)->get('provider_api_key'));
    }

    public function test_existing_api_key_is_preserved_when_input_is_blank(): void
    {
        app(SystemSettings::class)->put('provider_api_key', 'persisted-key');

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GeneralSettings::class)
            ->set('providerApiKey', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('persisted-key', app(SystemSettings::class)->get('provider_api_key'));
    }
}
