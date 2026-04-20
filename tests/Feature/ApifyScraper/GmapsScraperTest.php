<?php

namespace Tests\Feature\ApifyScraper;

use App\Livewire\ApifyScraper\GmapsScraper;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GmapsScraperTest extends TestCase
{
    use RefreshDatabase;

    public function test_gmaps_scraper_page_requires_authentication(): void
    {
        $response = $this->get(route('apify-scraper.gmaps-1-0'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_gmaps_scraper_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('apify-scraper.gmaps-1-0'));

        $response->assertOk();
        $response->assertSeeLivewire(GmapsScraper::class);
    }

    public function test_gmaps_scraper_can_fetch_results_using_saved_apify_provider_key(): void
    {
        $this->configureRequestSettings();
        $this->createApifyApiKey();

        Http::fake([
            'https://api.apify.com/v2/acts/sbEjxxfeFlEBHijJS/run-sync-get-dataset-items*' => Http::response([
                [
                    'name' => 'Tukang Taman Jogja',
                    'street' => 'Jl. Affandi No.197',
                    'city' => 'Jawa Tengah',
                    'state' => 'Daerah Istimewa Yogyakarta 55221',
                    'phone_number' => '+62 813-3451-8899',
                    'website_url' => 'https://jogja.gardencenter.co.id/',
                    'review_score' => '4.9',
                    'latitude' => '-7.7811233',
                    'longitude' => '110.3877011',
                    'google_maps_url' => 'https://www.google.com/maps?cid=13089335115778973399',
                ],
            ], 201),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GmapsScraper::class)
            ->set('searchQuery', 'dentist')
            ->set('areaWidth', 20)
            ->set('areaHeight', 20)
            ->set('maxResults', 500)
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('hasResults', true)
            ->assertSet('resultsCount', 1)
            ->assertSet('columns.0', 'name')
            ->assertSet('columns.1', 'street')
            ->assertSet('results.0.name', 'Tukang Taman Jogja')
            ->assertSee('Tukang Taman Jogja')
            ->assertSee('https://jogja.gardencenter.co.id/');

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.apify.com/v2/acts/sbEjxxfeFlEBHijJS/run-sync-get-dataset-items?token=saved-apify-key&format=json&clean=true'
                && $request['search_query'] === 'dentist'
                && $request['gmaps_url'] === ''
                && $request['area_width'] === 20
                && $request['area_height'] === 20
                && $request['max_results'] === 500;
        });
    }

    public function test_gmaps_scraper_requires_active_apify_provider_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GmapsScraper::class)
            ->set('searchQuery', 'dentist')
            ->call('run')
            ->assertSet('errorMessage', 'API key Apify belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "apify_provider".');
    }

    public function test_gmaps_scraper_validates_required_fields(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GmapsScraper::class)
            ->set('searchQuery', '')
            ->call('run')
            ->assertHasErrors([
                'searchQuery',
            ]);
    }

    public function test_gmaps_scraper_can_export_csv(): void
    {
        $component = $this->seedExportableResults();

        $component->call('exportCsv')
            ->assertFileDownloaded('apify-gmaps-1-0.csv');
    }

    public function test_gmaps_scraper_can_export_xlsx(): void
    {
        $component = $this->seedExportableResults();

        $component->call('exportXlsx')
            ->assertFileDownloaded('apify-gmaps-1-0.xlsx');
    }

    public function test_gmaps_scraper_can_export_pdf(): void
    {
        $component = $this->seedExportableResults();

        $component->call('exportPdf')
            ->assertFileDownloaded('apify-gmaps-1-0.pdf');
    }

    private function seedExportableResults(): \Livewire\Features\SupportTesting\Testable
    {
        $user = User::factory()->create();

        return Livewire::actingAs($user)
            ->test(GmapsScraper::class)
            ->set('columns', ['name', 'city', 'website_url'])
            ->set('results', [[
                'name' => 'Tukang Taman Jogja',
                'city' => 'Jawa Tengah',
                'website_url' => 'https://jogja.gardencenter.co.id/',
            ]])
            ->set('hasResults', true)
            ->set('resultsCount', 1);
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

    private function createApifyApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'apify_provider',
            'label' => 'Apify Provider',
            'value' => 'saved-apify-key',
            'is_active' => true,
        ]);
    }
}
