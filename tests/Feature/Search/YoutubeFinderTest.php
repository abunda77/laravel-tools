<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\YoutubeFinder;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class YoutubeFinderTest extends TestCase
{
    use RefreshDatabase;

    public function test_youtube_finder_page_requires_authentication(): void
    {
        $response = $this->get(route('search.youtube-finder'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_youtube_finder_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.youtube-finder'));

        $response->assertOk();
        $response->assertSeeLivewire(YoutubeFinder::class);
    }

    public function test_youtube_finder_can_fetch_videos_using_youtubeapi_provider_key(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/search*' => Http::response([
                'regionCode' => 'ID',
                'pageInfo' => ['totalResults' => 2, 'resultsPerPage' => 25],
                'items' => [
                    [
                        'id' => ['kind' => 'youtube#video', 'videoId' => 'laravel001'],
                        'snippet' => [
                            'publishedAt' => '2025-01-15T09:00:00Z',
                            'channelId' => 'channel-001',
                            'title' => 'Laravel 12 Crash Course',
                            'description' => 'Belajar Laravel dari nol.',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/laravel001/hqdefault.jpg'],
                            ],
                            'channelTitle' => 'Code Studio',
                        ],
                    ],
                    [
                        'id' => ['kind' => 'youtube#video', 'videoId' => 'laravel002'],
                        'snippet' => [
                            'publishedAt' => '2025-02-01T11:30:00Z',
                            'channelId' => 'channel-002',
                            'title' => 'Livewire 3 Deep Dive',
                            'description' => 'Component patterns and testing.',
                            'thumbnails' => [
                                'high' => ['url' => 'https://i.ytimg.com/vi/laravel002/hqdefault.jpg'],
                            ],
                            'channelTitle' => 'Laravel Lab',
                        ],
                    ],
                ],
            ], 200),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [
                    [
                        'id' => 'laravel001',
                        'statistics' => [
                            'viewCount' => '120045',
                            'likeCount' => '3300',
                            'commentCount' => '221',
                        ],
                        'contentDetails' => [
                            'duration' => 'PT14M2S',
                            'definition' => 'hd',
                            'dimension' => '2d',
                            'licensedContent' => true,
                        ],
                    ],
                    [
                        'id' => 'laravel002',
                        'statistics' => [
                            'viewCount' => '88000',
                            'likeCount' => '2800',
                            'commentCount' => '190',
                        ],
                        'contentDetails' => [
                            'duration' => 'PT1H2M5S',
                            'definition' => 'hd',
                            'dimension' => '2d',
                            'licensedContent' => false,
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeFinder::class)
            ->set('query', 'laravel tutorial')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('regionCode', 'ID')
            ->assertSet('totalResults', 2)
            ->assertSet('videos.0.videoId', 'laravel001')
            ->assertSet('videos.0.views', 120045)
            ->assertSet('videos.0.duration', '14:02')
            ->assertSet('videos.1.duration', '1:02:05')
            ->assertSee('Laravel 12 Crash Course')
            ->assertSee('Livewire 3 Deep Dive');

        Http::assertSentCount(2);
    }

    public function test_youtube_finder_can_load_more_results(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/search*' => Http::sequence()
                ->push([
                    'regionCode' => 'ID',
                    'nextPageToken' => 'NEXT_PAGE',
                    'pageInfo' => ['totalResults' => 2, 'resultsPerPage' => 25],
                    'items' => [
                        [
                            'id' => ['kind' => 'youtube#video', 'videoId' => 'laravel001'],
                            'snippet' => [
                                'publishedAt' => '2025-01-15T09:00:00Z',
                                'channelId' => 'channel-001',
                                'title' => 'Laravel 12 Crash Course',
                                'description' => 'Belajar Laravel dari nol.',
                                'thumbnails' => [
                                    'high' => ['url' => 'https://i.ytimg.com/vi/laravel001/hqdefault.jpg'],
                                ],
                                'channelTitle' => 'Code Studio',
                            ],
                        ],
                    ],
                ], 200)
                ->push([
                    'regionCode' => 'ID',
                    'pageInfo' => ['totalResults' => 2, 'resultsPerPage' => 25],
                    'items' => [
                        [
                            'id' => ['kind' => 'youtube#video', 'videoId' => 'laravel002'],
                            'snippet' => [
                                'publishedAt' => '2025-02-01T11:30:00Z',
                                'channelId' => 'channel-002',
                                'title' => 'Livewire 3 Deep Dive',
                                'description' => 'Component patterns and testing.',
                                'thumbnails' => [
                                    'high' => ['url' => 'https://i.ytimg.com/vi/laravel002/hqdefault.jpg'],
                                ],
                                'channelTitle' => 'Laravel Lab',
                            ],
                        ],
                    ],
                ], 200),
            'https://www.googleapis.com/youtube/v3/videos*' => Http::sequence()
                ->push([
                    'items' => [
                        [
                            'id' => 'laravel001',
                            'statistics' => ['viewCount' => '120045', 'likeCount' => '3300', 'commentCount' => '221'],
                            'contentDetails' => ['duration' => 'PT14M2S', 'definition' => 'hd', 'dimension' => '2d', 'licensedContent' => true],
                        ],
                    ],
                ], 200)
                ->push([
                    'items' => [
                        [
                            'id' => 'laravel002',
                            'statistics' => ['viewCount' => '88000', 'likeCount' => '2800', 'commentCount' => '190'],
                            'contentDetails' => ['duration' => 'PT12M10S', 'definition' => 'hd', 'dimension' => '2d', 'licensedContent' => false],
                        ],
                    ],
                ], 200),
        ]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(YoutubeFinder::class)
            ->set('query', 'laravel tutorial')
            ->call('run')
            ->assertSet('nextPageToken', 'NEXT_PAGE')
            ->assertCount('videos', 1);

        $component->call('loadMore')
            ->assertSet('nextPageToken', null)
            ->assertCount('videos', 2)
            ->assertSet('videos.1.videoId', 'laravel002');
    }

    public function test_youtube_finder_requires_active_youtube_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeFinder::class)
            ->set('query', 'laravel tutorial')
            ->call('run')
            ->assertSet('errorMessage', 'YouTube Data API key belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "youtubeapi_provider".');
    }

    public function test_youtube_finder_validates_query(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeFinder::class)
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

    private function createYoutubeApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'youtubeapi_provider',
            'label' => 'YouTube Data API',
            'value' => 'saved-youtube-key',
            'is_active' => true,
        ]);
    }
}
