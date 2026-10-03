<?php

namespace Tests\Feature\Internet;

use App\Livewire\Internet\GoldPrice;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GoldPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gold_price_page_requires_authentication(): void
    {
        $response = $this->get(route('internet.gold-price'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_gold_price_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('internet.gold-price'));

        $response->assertOk();
        $response->assertSeeLivewire(GoldPrice::class);
    }

    public function test_gold_price_can_fetch_history_using_saved_api_keys(): void
    {
        $this->travelTo(Carbon::parse('2026-04-20 10:00:00'));

        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();
        $this->createGoldApiApiKey();

        Http::fake([
            'https://use.api.co.id/currency/exchange-rate*' => Http::response([
                'is_success' => true,
                'message' => 'Success',
                'data' => [
                    'pair' => 'USDIDR',
                    'updated_at' => 1732178872000,
                    'rate' => 16000,
                ],
            ], 200),
            'https://www.goldapi.io/api/XAU/USD/*' => Http::response($this->goldApiPayload(), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoldPrice::class)
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('errorMessage', null)
            ->assertSet('result.usdToIdr', 16000)
            ->assertSet('result.days', 7)
            ->assertSet('result.rows', fn ($rows) => count($rows) === 7)
            ->assertSet('result.rows.0.date', '2026-04-20')
            ->assertSet('result.rows.0.priceGramUsd', 105.0)
            ->assertSet('result.rows.0.priceGramIdr', 1680000)
            ->assertSet('result.rows.0.changeIdr', 80000)
            ->assertSet('result.rows.0.changePercent', 5.0)
            ->assertSet('result.rows.0.isUp', true)
            ->assertSee('Rp 1.680.000');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://www.goldapi.io/api/XAU/USD/20260420'
                && $request->hasHeader('x-access-token', 'saved-goldapi-key');
        });
    }

    public function test_gold_price_skips_dates_without_market_data(): void
    {
        $this->travelTo(Carbon::parse('2026-04-20 10:00:00'));

        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();
        $this->createGoldApiApiKey();

        Http::fake([
            'https://use.api.co.id/currency/exchange-rate*' => Http::response([
                'is_success' => true,
                'data' => ['pair' => 'USDIDR', 'rate' => 16000],
            ], 200),
            'https://www.goldapi.io/api/XAU/USD/20260419' => Http::response(['error' => 'No data available for this date'], 200),
            'https://www.goldapi.io/api/XAU/USD/*' => Http::response($this->goldApiPayload(), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoldPrice::class)
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.rows', fn ($rows) => count($rows) === 6)
            ->assertSet('result.rows', fn ($rows) => ! in_array('2026-04-19', array_column($rows, 'date'), true));
    }

    public function test_gold_price_requires_saved_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoldPrice::class)
            ->call('run')
            ->assertSet('hasSavedApiKey', false)
            ->assertSet('errorMessage', 'API key goldapi.io belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "goldapi_provider".');
    }

    public function test_gold_price_reports_error_when_no_data_available(): void
    {
        $this->travelTo(Carbon::parse('2026-04-20 10:00:00'));

        $this->configureRequestSettings();
        $this->createApiCoIdApiKey();
        $this->createGoldApiApiKey();

        Http::fake([
            'https://use.api.co.id/currency/exchange-rate*' => Http::response([
                'is_success' => true,
                'data' => ['pair' => 'USDIDR', 'rate' => 16000],
            ], 200),
            'https://www.goldapi.io/api/XAU/USD/*' => Http::response(['error' => 'No data available for this date'], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoldPrice::class)
            ->call('run')
            ->assertSet('result', null)
            ->assertSet('errorMessage', fn ($message) => str_contains($message, 'Tidak ada data harga emas yang tersedia'));
    }

    /**
     * @return array<string, mixed>
     */
    private function goldApiPayload(): array
    {
        return [
            'timestamp' => 1776675600000,
            'metal' => 'XAU',
            'currency' => 'USD',
            'price' => 3266.0,
            'prev_close_price' => 3110.35,
            'chp' => 5.0,
            'price_gram_24k' => 105.0,
        ];
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

    private function createGoldApiApiKey(bool $isActive = true): void
    {
        ApiKey::query()->create([
            'name' => 'goldapi_provider',
            'label' => 'goldapi.io Provider',
            'value' => 'saved-goldapi-key',
            'is_active' => $isActive,
        ]);
    }
}
