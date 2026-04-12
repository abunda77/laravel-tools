<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\TiktokVideoSearch;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TiktokVideoSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiktok_video_search_page_requires_authentication(): void
    {
        $response = $this->get(route('search.tiktok'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_tiktok_video_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.tiktok'));

        $response->assertOk();
        $response->assertSeeLivewire(TiktokVideoSearch::class);
    }

    public function test_tiktok_video_search_can_fetch_videos_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/search/tiktok*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => [
                    'https://tikwm.com/video/media/play/7497110513667624198.mp4',
                    'https://tikwm.com/video/media/play/7612307513840504085.mp4',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TiktokVideoSearch::class)
            ->set('query', 'pargoy')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.query', 'pargoy')
            ->assertSet('result.total', 2)
            ->assertSet('result.videos.0.index', 1)
            ->assertSet('result.videos.0.filename', '7497110513667624198.mp4')
            ->assertSee('7497110513667624198.mp4')
            ->assertSee('7612307513840504085.mp4');

        Http::assertSent(function ($request) {
            $url = $request->url();
            $query = [];

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://api.ferdev.my.id/search/tiktok')
                && ($query['query'] ?? null) === 'pargoy'
                && ($query['apikey'] ?? null) === 'saved-downloader-key';
        });
    }

    public function test_tiktok_video_search_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TiktokVideoSearch::class)
            ->set('query', 'pargoy')
            ->call('run')
            ->assertSet('errorMessage', 'API key TikTok search belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_tiktok_video_search_validates_query(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TiktokVideoSearch::class)
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
