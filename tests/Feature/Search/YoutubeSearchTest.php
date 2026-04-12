<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\YoutubeSearch;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class YoutubeSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_youtube_search_page_requires_authentication(): void
    {
        $response = $this->get(route('search.youtube'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_youtube_search_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.youtube'));

        $response->assertOk();
        $response->assertSeeLivewire(YoutubeSearch::class);
    }

    public function test_youtube_search_can_fetch_videos_using_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/search/youtube*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => [
                    [
                        'title' => 'Teknik Mengecat Dinding Mudah dan Cepat Hasil Rapih',
                        'duration' => '0:16',
                        'views' => 1013314,
                        'url' => 'https://youtube.com/watch?v=aG86BlAZVQc',
                        'thumbnail' => 'https://i.ytimg.com/vi/aG86BlAZVQc/hq720_2.jpg',
                        'uploadDate' => '3 years ago',
                        'author' => 'Kuli Proyek Serabutan',
                    ],
                    [
                        'title' => 'Pelajaran Super Cepat: Cara menggulung dinding dengan cat',
                        'duration' => '2:59',
                        'views' => 1218909,
                        'url' => 'https://youtube.com/watch?v=snJ8kwcNTqE',
                        'thumbnail' => 'https://i.ytimg.com/vi/snJ8kwcNTqE/hqdefault.jpg',
                        'uploadDate' => '5 years ago',
                        'author' => 'Brolux Painting',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeSearch::class)
            ->set('query', 'cara mengecat dinding')
            ->set('displayMode', 'table')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.query', 'cara mengecat dinding')
            ->assertSet('result.total', 2)
            ->assertSet('result.videos.0.index', 1)
            ->assertSet('result.videos.0.title', 'Teknik Mengecat Dinding Mudah dan Cepat Hasil Rapih')
            ->assertSee('Teknik Mengecat Dinding Mudah dan Cepat Hasil Rapih')
            ->assertSee('Brolux Painting');

        Http::assertSent(function ($request) {
            $url = $request->url();
            $query = [];

            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://api.ferdev.my.id/search/youtube')
                && ($query['query'] ?? null) === 'cara mengecat dinding'
                && ($query['apikey'] ?? null) === 'saved-downloader-key';
        });
    }

    public function test_youtube_search_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeSearch::class)
            ->set('query', 'cara mengecat dinding')
            ->call('run')
            ->assertSet('errorMessage', 'API key Youtube search belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_youtube_search_validates_query(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeSearch::class)
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
