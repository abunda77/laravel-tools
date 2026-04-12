<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\GoogleImageSearch;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GoogleImageSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_image_search_page_requires_authentication(): void
    {
        $response = $this->get(route('search.google-image'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_google_image_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.google-image'));

        $response->assertOk();
        $response->assertSeeLivewire(GoogleImageSearch::class);
    }

    public function test_google_image_search_can_fetch_images_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/search/gimage*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => [
                    [
                        'title' => 'Jenis-Jenis Burung Perkutut dan Makna ...',
                        'url' => 'https://www.tiktok.com/@dunia.burungkicau/video/7519912383981030712',
                        'image' => 'https://www.tiktok.com/api/img/?itemId=7519912383981030712&location=0&aid=1988',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoogleImageSearch::class)
            ->set('query', 'burung perkutut')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.query', 'burung perkutut')
            ->assertSet('result.total', 1)
            ->assertSet('result.images.0.title', 'Jenis-Jenis Burung Perkutut dan Makna ...')
            ->assertSee('Jenis-Jenis Burung Perkutut dan Makna ...')
            ->assertSee('https://www.tiktok.com/@dunia.burungkicau/video/7519912383981030712');

        Http::assertSent(function ($request) {
            $url = $request->url();
            $query = [];

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://api.ferdev.my.id/search/gimage')
                && ($query['query'] ?? null) === 'burung perkutut'
                && ($query['apikey'] ?? null) === 'saved-downloader-key';
        });
    }

    public function test_google_image_search_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoogleImageSearch::class)
            ->set('query', 'burung perkutut')
            ->call('run')
            ->assertSet('errorMessage', 'API key Google Image search belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_google_image_search_validates_query(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(GoogleImageSearch::class)
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
