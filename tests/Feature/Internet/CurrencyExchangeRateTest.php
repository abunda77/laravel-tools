<?php

namespace Tests\Feature\Internet;

use App\Livewire\Internet\CurrencyExchangeRate;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CurrencyExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    public function test_currency_exchange_rate_page_requires_authentication(): void
    {
        $response = $this->get(route('internet.currency-exchange-rate'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_currency_exchange_rate_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('internet.currency-exchange-rate'));

        $response->assertOk();
        $response->assertSeeLivewire(CurrencyExchangeRate::class);
    }

    public function test_currency_exchange_rate_can_fetch_rate_using_saved_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();

        Http::fake([
            'https://use.api.co.id/currency/exchange-rate*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    'pair' => 'USDIDR',
                    'updated_at' => 1732178872000,
                    'rate' => 16740,
                ],
                'last_data_updated_at' => 1732178872000,
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CurrencyExchangeRate::class)
            ->set('pair', 'usdidr')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('pair', 'USDIDR')
            ->assertSet('result.pair', 'USDIDR')
            ->assertSet('result.rate', 16740)
            ->assertSet('result.isSuccess', true);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://use.api.co.id/currency/exchange-rate?pair=USDIDR'
                && $request->hasHeader('x-api-co-id', 'saved-apicoid-key');
        });
    }

    public function test_currency_exchange_rate_requires_saved_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CurrencyExchangeRate::class)
            ->set('pair', 'USDIDR')
            ->call('run')
            ->assertSet('errorMessage', 'API key API.co.id belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "apicoid_provider".');
    }

    public function test_currency_exchange_rate_validates_pair_format(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CurrencyExchangeRate::class)
            ->set('pair', 'USD/IDR')
            ->call('run')
            ->assertHasErrors(['pair']);
    }

    private function configureRequestSettings(): void
    {
        app(SystemSettings::class)->putMany([
            'request_timeout_seconds' => 30,
            'request_retry_times' => 1,
            'request_retry_sleep_ms' => 100,
            'queue_connection' => 'database',
        ]);
    }

    private function createApiCoIdApiKey(bool $isActive = true): void
    {
        ApiKey::query()->create([
            'name' => 'apicoid_provider',
            'label' => 'API.co.id Provider',
            'value' => 'saved-apicoid-key',
            'is_active' => $isActive,
        ]);
    }
}
