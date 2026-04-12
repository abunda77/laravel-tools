<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\TokopediaSearch;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TokopediaSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokopedia_search_page_requires_authentication(): void
    {
        $response = $this->get(route('search.tokopedia'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_tokopedia_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.tokopedia'));

        $response->assertOk();
        $response->assertSeeLivewire(TokopediaSearch::class);
    }

    public function test_tokopedia_search_can_fetch_products_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/search/tokopedia*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'data' => [
                    [
                        'id' => 102711680391,
                        'name' => 'ITEL CITY100 6GB 128GB FAIRY PURPLE (BOX CHA + SCR)',
                        'price' => 'Rp1.439.000',
                        'price_number' => 1439000,
                        'shop' => [
                            'name' => 'Laptop Project',
                            'city' => 'Jakarta Pusat',
                        ],
                        'url' => 'https://www.tokopedia.com/laptopproject/itel-city100-6gb-128gb-fairy-purple-box-cha-scr-1733657662605460887',
                        'thumbnail' => 'https://example.com/thumb.jpeg',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TokopediaSearch::class)
            ->set('query', 'itel city 100')
            ->set('displayMode', 'table')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.query', 'itel city 100')
            ->assertSet('result.total', 1)
            ->assertSet('result.items.0.shopName', 'Laptop Project')
            ->assertSet('result.items.0.shopCity', 'Jakarta Pusat')
            ->assertSee('ITEL CITY100 6GB 128GB FAIRY PURPLE')
            ->assertSee('Rp1.439.000');

        Http::assertSent(function ($request) {
            $url = $request->url();
            $query = [];

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://api.ferdev.my.id/search/tokopedia')
                && ($query['query'] ?? null) === 'itel city 100'
                && ($query['apikey'] ?? null) === 'saved-downloader-key';
        });
    }

    public function test_tokopedia_search_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TokopediaSearch::class)
            ->set('query', 'itel city 100')
            ->call('run')
            ->assertSet('errorMessage', 'API key Tokopedia search belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_tokopedia_search_validates_query(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TokopediaSearch::class)
            ->set('query', '')
            ->call('run')
            ->assertHasErrors(['query']);
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

    private function createDownloaderApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'downloader_provider',
            'label' => 'Downloader Provider',
            'value' => 'saved-downloader-key',
            'is_active' => true,
        ]);
    }
}
