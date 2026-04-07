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
            ->set('requestTimeoutSeconds', 45)
            ->set('requestRetryTimes', 3)
            ->set('requestRetrySleepMs', 750)
            ->set('queueConnection', 'database')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('app_settings', [
            'key' => 'request_timeout_seconds',
            'value' => '45',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'key' => 'request_retry_times',
            'value' => '3',
        ]);

        $this->assertDatabaseHas('app_settings', [
            'key' => 'request_retry_sleep_ms',
            'value' => '750',
        ]);

        $this->assertSame(45, app(SystemSettings::class)->get('request_timeout_seconds'));
        $this->assertSame(3, app(SystemSettings::class)->get('request_retry_times'));
        $this->assertSame(750, app(SystemSettings::class)->get('request_retry_sleep_ms'));
    }

    public function test_existing_settings_are_loaded_on_mount(): void
    {
        app(SystemSettings::class)->putMany([
            'request_timeout_seconds' => 60,
            'request_retry_times' => 2,
            'request_retry_sleep_ms' => 250,
            'queue_connection' => 'sync',
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GeneralSettings::class)
            ->assertSet('requestTimeoutSeconds', 60)
            ->assertSet('requestRetryTimes', 2)
            ->assertSet('requestRetrySleepMs', 250)
            ->assertSet('queueConnection', 'sync');
    }

    public function test_system_settings_cache_is_invalidated_when_setting_is_updated(): void
    {
        $settings = app(SystemSettings::class);

        $settings->put('request_timeout_seconds', 30);

        $this->assertSame(30, $settings->get('request_timeout_seconds'));

        AppSetting::query()
            ->where('key', 'request_timeout_seconds')
            ->update(['value' => '45']);

        $this->assertSame(30, $settings->get('request_timeout_seconds'));

        $settings->put('request_timeout_seconds', 60);

        $this->assertSame(60, $settings->get('request_timeout_seconds'));
    }
}
