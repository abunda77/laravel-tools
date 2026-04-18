<?php

namespace Tests\Feature\Search;

use App\Livewire\Search\YoutubeChannel;
use App\Models\ApiKey;
use App\Models\User;
use App\Services\Search\YoutubeChannelService;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class YoutubeChannelTest extends TestCase
{
    use RefreshDatabase;

    public function test_youtube_channel_page_requires_authentication(): void
    {
        $response = $this->get(route('search.youtube-channel'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_youtube_channel_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('search.youtube-channel'));

        $response->assertOk();
        $response->assertSeeLivewire(YoutubeChannel::class);
    }

    public function test_youtube_channel_can_fetch_channel_info_and_videos(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response($this->fakeChannelResponse(), 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response($this->fakePlaylistResponse(), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@Google')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('channel.id', 'UCVHdiysqBjVRSBHRODX2p0Q')
            ->assertSet('channel.title', 'Google')
            ->assertSet('channel.subscriberCount', '10000000')
            ->assertSet('videos.0.videoId', 'abc123')
            ->assertSet('videos.0.title', 'What is AI?')
            ->assertSee('Google')
            ->assertSee('What is AI?');
    }

    public function test_youtube_channel_load_more_appends_videos(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response($this->fakeChannelResponse(), 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::sequence()
                ->push($this->fakePlaylistResponse(nextPageToken: 'NEXT_PAGE'), 200)
                ->push($this->fakePlaylistResponse(videoId: 'def456', title: 'Second Video', nextPageToken: null), 200),
        ]);

        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@Google')
            ->call('run')
            ->assertSet('nextPageToken', 'NEXT_PAGE');

        $component->call('loadMore')
            ->assertSet('nextPageToken', null)
            ->assertCount('videos', 2);
    }

    public function test_youtube_channel_search_within_channel(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response($this->fakeChannelResponse(), 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response($this->fakePlaylistResponse(), 200),
            'https://www.googleapis.com/youtube/v3/search*' => Http::response([
                'kind' => 'youtube#searchListResponse',
                'nextPageToken' => null,
                'pageInfo' => ['totalResults' => 5, 'resultsPerPage' => 50],
                'items' => [
                    [
                        'kind' => 'youtube#searchResult',
                        'id' => ['kind' => 'youtube#video', 'videoId' => 'search001'],
                        'snippet' => [
                            'title' => 'Tutorial PHP Pemula',
                            'publishedAt' => '2024-03-01T00:00:00Z',
                            'thumbnails' => ['high' => ['url' => 'https://i.ytimg.com/vi/search001/hq.jpg']],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@Google')
            ->call('run')
            ->set('searchKeyword', 'tutorial php')
            ->call('runSearch')
            ->assertHasNoErrors()
            ->assertSet('isSearchMode', true)
            ->assertSet('searchTotalResults', 5)
            ->assertSet('videos.0.videoId', 'search001')
            ->assertSet('videos.0.title', 'Tutorial PHP Pemula')
            ->assertSee('Tutorial PHP Pemula');
    }

    public function test_youtube_channel_clear_search_restores_browse_mode(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response($this->fakeChannelResponse(), 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response($this->fakePlaylistResponse(), 200),
            'https://www.googleapis.com/youtube/v3/search*' => Http::response([
                'kind' => 'youtube#searchListResponse',
                'pageInfo' => ['totalResults' => 1, 'resultsPerPage' => 50],
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@Google')
            ->call('run')
            ->set('searchKeyword', 'tutorial')
            ->call('runSearch')
            ->assertSet('isSearchMode', true)
            ->call('clearSearch')
            ->assertSet('isSearchMode', false)
            ->assertSet('searchKeyword', '');
    }

    public function test_youtube_channel_requires_active_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@Google')
            ->call('run')
            ->assertSet('errorMessage', 'YouTube Data API key belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "youtubeapi_provider".');
    }

    public function test_youtube_channel_validates_channel_input(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '')
            ->call('run')
            ->assertHasErrors(['channelInput']);
    }

    public function test_youtube_channel_validates_search_keyword(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response($this->fakeChannelResponse(), 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response($this->fakePlaylistResponse(), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@Google')
            ->call('run')
            ->set('searchKeyword', '')
            ->call('runSearch')
            ->assertHasErrors(['searchKeyword']);
    }

    public function test_youtube_channel_shows_error_when_channel_not_found(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response([
                'kind' => 'youtube#channelListResponse',
                'items' => [],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@channeltidakada12345')
            ->call('run')
            ->assertSet('errorMessage', 'Channel tidak ditemukan. Pastikan handle atau Channel ID yang dimasukkan benar.');
    }

    public function test_youtube_channel_keeps_previous_results_when_refresh_fails(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake([
            'https://www.googleapis.com/youtube/v3/channels*' => Http::sequence()
                ->push($this->fakeChannelResponse(), 200)
                ->push([
                    'kind' => 'youtube#channelListResponse',
                    'items' => [],
                ], 200),
            'https://www.googleapis.com/youtube/v3/playlistItems*' => Http::response($this->fakePlaylistResponse(), 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(YoutubeChannel::class)
            ->set('channelInput', '@Google')
            ->call('run')
            ->assertSet('channel.title', 'Google')
            ->assertSet('videos.0.videoId', 'abc123')
            ->set('channelInput', '@channel-tidak-ada')
            ->call('run')
            ->assertSet('errorMessage', 'Channel tidak ditemukan. Pastikan handle atau Channel ID yang dimasukkan benar.')
            ->assertSet('channel.title', 'Google')
            ->assertSet('videos.0.videoId', 'abc123');
    }

    public function test_youtube_channel_service_rejects_blank_search_keyword_before_requesting_api(): void
    {
        $this->configureRequestSettings();
        $this->createYoutubeApiKey();

        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keyword pencarian video tidak boleh kosong.');

        try {
            app(YoutubeChannelService::class)->searchVideos('UCVHdiysqBjVRSBHRODX2p0Q', '   ');
        } finally {
            Http::assertNothingSent();
        }
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
            'value' => 'fake-yt-api-key',
            'is_active' => true,
        ]);
    }

    /** @return array<string, mixed> */
    private function fakeChannelResponse(): array
    {
        return [
            'kind' => 'youtube#channelListResponse',
            'items' => [
                [
                    'kind' => 'youtube#channel',
                    'id' => 'UCVHdiysqBjVRSBHRODX2p0Q',
                    'snippet' => [
                        'title' => 'Google',
                        'description' => 'The official Google YouTube channel.',
                        'publishedAt' => '2005-09-08T18:38:13Z',
                        'country' => 'US',
                        'thumbnails' => ['high' => ['url' => 'https://yt3.ggpht.com/google-high.jpg']],
                    ],
                    'statistics' => [
                        'viewCount' => '500000000',
                        'subscriberCount' => '10000000',
                        'hiddenSubscriberCount' => false,
                        'videoCount' => '5000',
                    ],
                    'contentDetails' => [
                        'relatedPlaylists' => ['uploads' => 'UUVHdiysqBjVRSBHRODX2p0Q'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fakePlaylistResponse(
        string $videoId = 'abc123',
        string $title = 'What is AI?',
        ?string $nextPageToken = null,
    ): array {
        $response = [
            'kind' => 'youtube#playlistItemListResponse',
            'items' => [
                [
                    'kind' => 'youtube#playlistItem',
                    'snippet' => [
                        'publishedAt' => '2024-01-15T10:00:00Z',
                        'title' => $title,
                        'thumbnails' => ['high' => ['url' => 'https://i.ytimg.com/vi/'.$videoId.'/hqdefault.jpg']],
                        'resourceId' => ['kind' => 'youtube#video', 'videoId' => $videoId],
                    ],
                ],
            ],
        ];

        if ($nextPageToken !== null) {
            $response['nextPageToken'] = $nextPageToken;
        }

        return $response;
    }
}
