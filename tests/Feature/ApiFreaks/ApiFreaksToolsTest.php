<?php

namespace Tests\Feature\ApiFreaks;

use App\Livewire\ApiFreaks\CommoditySymbols;
use App\Livewire\ApiFreaks\CreditUsage;
use App\Livewire\ApiFreaks\DomainSearch;
use App\Livewire\ApiFreaks\DomainWhoisHistoryLookup;
use App\Livewire\ApiFreaks\DomainWhoisLookup;
use App\Livewire\ApiFreaks\HistoricalCommodityPrices;
use App\Livewire\ApiFreaks\LiveCommodityPrices;
use App\Livewire\ApiFreaks\SubdomainLookup;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ApiFreaksToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_apifreaks_tools_page_requires_authentication(): void
    {
        $response = $this->get(route('apifreaks-tools'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_apifreaks_tools_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('apifreaks-tools'))
            ->assertOk()
            ->assertSee('ApiFreaks Tools');

        $this->actingAs($user)
            ->get(route('apifreaks-tools.credit-usage'))
            ->assertOk()
            ->assertSeeLivewire(CreditUsage::class);
    }

    public function test_credit_usage_can_fetch_usage_summary_using_saved_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/credits/usage/info*' => Http::response([
                'success' => true,
                'userStatus' => 'ACTIVE',
                'subscriptionPlan' => 'PRO',
                'subscriptionCreditsUsed' => 120,
                'subscriptionCreditsRemaining' => 880,
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreditUsage::class)
            ->call('run')
            ->assertSet('result.response.userStatus', 'ACTIVE')
            ->assertSee('subscriptionCreditsRemaining');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.apifreaks.com/v1.0/credits/usage/info?apiKey=saved-apifreaks-key'
            && $request->hasHeader('X-apiKey', 'saved-apifreaks-key'));
    }

    public function test_domain_whois_lookup_can_fetch_live_whois(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/domain/whois/live*' => Http::response([
                'status' => true,
                'domain_name' => 'apifreaks.com',
                'query_time' => '2026-04-18T09:30:00Z',
                'whois_server' => 'whois.namecheap.com',
                'domain_registered' => true,
                'create_date' => '2020-01-01',
                'update_date' => '2026-01-01',
                'expiry_date' => '2027-01-01',
                'domain_registrar' => [
                    'registrar_name' => 'Namecheap',
                ],
                'registrant_contact' => [
                    'name' => 'Api Freaks',
                ],
                'administrative_contact' => [],
                'technical_contact' => [],
                'billing_contact' => [],
                'name_servers' => ['ns1.example.com', 'ns2.example.com'],
                'domain_status' => ['clientTransferProhibited'],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DomainWhoisLookup::class)
            ->set('domain', 'https://apifreaks.com/path')
            ->call('run')
            ->assertSet('result.summary.domain_name', 'apifreaks.com')
            ->assertSee('ns1.example.com');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.apifreaks.com/v1.0/domain/whois/live?domainName=apifreaks.com&apiKey=saved-apifreaks-key');
    }

    public function test_domain_whois_history_lookup_can_fetch_history_rows(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/domain/whois/history*' => Http::response([
                'status' => true,
                'total_records' => 1,
                'whois_domains_historical' => [[
                    'num' => 1,
                    'domain_name' => 'apifreaks.com',
                    'query_time' => '2026-04-18T09:30:00Z',
                    'create_date' => '2020-01-01',
                    'update_date' => '2026-01-01',
                    'expiry_date' => '2027-01-01',
                    'domain_registrar' => ['registrar_name' => 'Namecheap'],
                    'registrant_contact' => ['name' => 'Api Freaks'],
                    'name_servers' => ['ns1.example.com'],
                    'domain_status' => ['clientTransferProhibited'],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DomainWhoisHistoryLookup::class)
            ->set('domain', 'apifreaks.com')
            ->call('run')
            ->assertSet('result.total_records', 1)
            ->assertSee('Namecheap');
    }

    public function test_domain_search_can_fetch_domain_availability(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/domain/availability*' => Http::response([
                'domain' => 'apifreaks.com',
                'domainAvailability' => false,
                'message' => 'Domain is already registered.',
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DomainSearch::class)
            ->set('domain', 'apifreaks.com')
            ->set('source', 'dns')
            ->call('run')
            ->assertSet('result.domainAvailability', false)
            ->assertSee('Domain is already registered.');
    }

    public function test_subdomain_lookup_can_fetch_subdomain_table(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/subdomains/lookup*' => Http::response([
                'domain' => 'stock-bill.com',
                'status' => true,
                'query_time' => '2026-04-18T02:49:24.725274418',
                'current_page' => 1,
                'total_pages' => 1,
                'total_records' => 2,
                'subdomains' => [
                    ['subdomain' => 'webmail.stock-bill.com', 'first_seen' => '2018-11-15', 'last_seen' => '2026-02-28'],
                    ['subdomain' => 'blog.stock-bill.com', 'first_seen' => '2017-12-11', 'last_seen' => '2026-03-27'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SubdomainLookup::class)
            ->set('domain', 'stock-bill.com')
            ->call('run')
            ->assertSet('result.total_records', 2)
            ->assertSee('webmail.stock-bill.com');
    }

    public function test_commodity_symbols_can_fetch_symbols_table(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/commodity/symbols*' => Http::response([
                'success' => true,
                'symbols' => [[
                    'symbol' => 'NG-FUT',
                    'category' => 'Energy',
                    'currency' => ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
                    'unit' => ['symbol' => 'MMBtu', 'name' => 'Million British Thermal Units'],
                    'name' => 'Natural Gas Futures',
                    'status' => 'active',
                    'updateInterval' => 'PER_MINUTE',
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CommoditySymbols::class)
            ->call('run')
            ->assertSet('result.total', 1)
            ->assertSee('NG-FUT');
    }

    public function test_live_commodity_prices_can_fetch_latest_rates(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/commodity/rates/latest*' => Http::response([
                'status' => true,
                'timestamp' => '2026-04-18T09:30:00Z',
                'rates' => [
                    'XAU' => 3230.55,
                    'WTIOIL-SPOT' => 72.15,
                ],
                'metadata' => [
                    'XAU' => ['unit' => 'troy ounce', 'quote' => 'USD'],
                    'WTIOIL-SPOT' => ['unit' => 'barrel', 'quote' => 'USD'],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(LiveCommodityPrices::class)
            ->set('symbols', 'XAU,WTIOIL-SPOT')
            ->set('updates', '1m')
            ->set('quote', 'USD')
            ->call('run')
            ->assertSet('result.symbols', 'XAU,WTIOIL-SPOT')
            ->assertSee('3230.55');
    }

    public function test_historical_commodity_prices_can_fetch_ohlc_rows(): void
    {
        $this->configureRequestSettings();
        $this->createApiFreaksApiKey();

        Http::fake([
            'https://api.apifreaks.com/v1.0/commodity/rates/historical*' => Http::response([
                'status' => true,
                'date' => '2026-04-18',
                'rates' => [
                    'XAU' => [
                        'date' => '2026-04-18',
                        'open' => 3200.10,
                        'high' => 3240.30,
                        'low' => 3190.10,
                        'close' => 3230.55,
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(HistoricalCommodityPrices::class)
            ->set('symbols', 'XAU')
            ->set('date', '2026-04-18')
            ->call('run')
            ->assertSet('result.date', '2026-04-18')
            ->assertSee('3230.55');
    }

    public function test_apifreaks_tools_require_active_saved_api_key(): void
    {
        $this->configureRequestSettings();
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreditUsage::class)
            ->call('run')
            ->assertSet('errorMessage', 'API key ApiFreaks belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "apifreaks_provider".');
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

    private function createApiFreaksApiKey(bool $isActive = true): void
    {
        ApiKey::query()->create([
            'name' => 'apifreaks_provider',
            'label' => 'ApiFreaks Provider',
            'value' => 'saved-apifreaks-key',
            'is_active' => $isActive,
        ]);
    }
}
