<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\UnsplashSearch;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class UnsplashSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsplash_search_page_requires_authentication(): void
    {
        $response = $this->get(route('search.unsplash'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_unsplash_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.unsplash'));

        $response->assertOk();
        $response->assertSeeLivewire(UnsplashSearch::class);
    }

    public function test_unsplash_search_can_fetch_images_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/search/unsplash*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => [
                    [
                        'title' => 'a stream running through a lush green forest',
                        'download' => 'https://plus.unsplash.com/premium_photo-1669312741146-582497b95b65?fit=max&w=1080',
                        'preview' => 'https://unsplash.com/photos/a-stream-running-through-a-lush-green-forest-Wo65D-IOtsk',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UnsplashSearch::class)
            ->set('query', 'river in the mount')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.query', 'river in the mount')
            ->assertSet('result.total', 1)
            ->assertSet('result.images.0.title', 'a stream running through a lush green forest')
            ->assertSee('a stream running through a lush green forest')
            ->assertSee('https://unsplash.com/photos/a-stream-running-through-a-lush-green-forest-Wo65D-IOtsk');

        Http::assertSent(function ($request) {
            $url = $request->url();
            $query = [];

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://api.ferdev.my.id/search/unsplash')
                && ($query['query'] ?? null) === 'river in the mount'
                && ($query['apikey'] ?? null) === 'saved-downloader-key';
        });
    }

    public function test_unsplash_search_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UnsplashSearch::class)
            ->set('query', 'river in the mount')
            ->call('run')
            ->assertSet('errorMessage', 'API key Unsplash search belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_unsplash_search_validates_query(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UnsplashSearch::class)
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
