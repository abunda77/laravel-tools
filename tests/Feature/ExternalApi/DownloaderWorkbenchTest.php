<?php

namespace Tests\Feature\ExternalApi;

use App\Livewire\ExternalApi\DownloaderWorkbench;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class DownloaderWorkbenchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_external_api_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('external-api'));

        $response->assertOk();
        $response->assertSeeLivewire(DownloaderWorkbench::class);
    }

    public function test_downloader_can_execute_tiktok_request_using_saved_settings(): void
    {
        $this->configureDownloaderSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/downloader/tiktok*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'data' => [
                    'title' => 'Sample TikTok',
                    'cover' => 'https://cdn.example.com/cover.jpg',
                    'play' => 'https://cdn.example.com/video.mp4',
                    'size' => 9360454,
                    'author' => [
                        'nickname' => 'ANATOLY',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DownloaderWorkbench::class)
            ->set('selectedProvider', 'tiktok')
            ->set('link', 'https://www.tiktok.com/@anatoly_pranks/video/1234567890')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.title', 'Sample TikTok')
            ->assertSet('result.downloadUrl', 'https://cdn.example.com/video.mp4')
            ->assertSet('result.authorName', 'ANATOLY');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ferdev.my.id/downloader/tiktok?link=https%3A%2F%2Fwww.tiktok.com%2F%40anatoly_pranks%2Fvideo%2F1234567890&apikey=saved-api-key';
        });
    }

    public function test_downloader_requires_api_key_when_no_saved_key_exists(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DownloaderWorkbench::class)
            ->set('selectedProvider', 'instagram')
            ->set('link', 'https://www.instagram.com/reel/example/')
            ->call('run')
            ->assertHasErrors(['apiKeyOverride']);
    }

    public function test_downloader_can_execute_instagram_request_using_metadata_payload(): void
    {
        $this->configureDownloaderSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/downloader/instagram*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'data' => [
                    'type' => 'mp4',
                    'dlink' => 'https://media.fastdl.app/get?filename=video.mp4',
                    'metadata' => [
                        'title' => 'Because I love Danger',
                        'username' => 'amitandjanvi',
                        'likeCount' => 233808,
                        'commentCount' => 621,
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DownloaderWorkbench::class)
            ->set('selectedProvider', 'instagram')
            ->set('link', 'https://www.instagram.com/reel/example/')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.title', 'Because I love Danger')
            ->assertSet('result.downloadUrl', 'https://media.fastdl.app/get?filename=video.mp4')
            ->assertSet('result.authorName', 'amitandjanvi');
    }

    public function test_downloader_can_execute_facebook_request_with_hd_and_sd_links(): void
    {
        $this->configureDownloaderSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/downloader/facebook*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'data' => [
                    'url' => 'https://www.facebook.com/share/v/1CYwXY89hc/',
                    'hd' => 'https://video.example.com/video-hd.mp4',
                    'sd' => 'https://video.example.com/video-sd.mp4',
                    'title' => 'unknown',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DownloaderWorkbench::class)
            ->set('selectedProvider', 'facebook')
            ->set('link', 'https://www.facebook.com/share/v/1CYwXY89hc/')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.downloadUrl', 'https://video.example.com/video-hd.mp4')
            ->assertSet('result.downloadOptions.0.label', 'Download HD')
            ->assertSet('result.downloadOptions.0.url', 'https://video.example.com/video-hd.mp4')
            ->assertSet('result.downloadOptions.1.label', 'Download SD')
            ->assertSet('result.downloadOptions.1.url', 'https://video.example.com/video-sd.mp4');
    }

    public function test_downloader_can_execute_ytshorts_request_using_download_payload(): void
    {
        $this->configureDownloaderSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/downloader/ytshorts*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'data' => [
                    'title' => 'top 5 kelakuan pasangan lucu #memengakakkocak #lucu #komedi',
                    'download' => 'https://ydl.ymcdn.org/api/v1/download/0460a7f9483628878cbac0b68e820251/aVCpCbEQVDk',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DownloaderWorkbench::class)
            ->set('selectedProvider', 'ytshorts')
            ->set('link', 'https://www.youtube.com/shorts/aVCpCbEQVDk')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.title', 'top 5 kelakuan pasangan lucu #memengakakkocak #lucu #komedi')
            ->assertSet('result.downloadUrl', 'https://ydl.ymcdn.org/api/v1/download/0460a7f9483628878cbac0b68e820251/aVCpCbEQVDk')
            ->assertSet('result.downloadOptions.0.label', 'Download (download)')
            ->assertSet('result.downloadOptions.0.url', 'https://ydl.ymcdn.org/api/v1/download/0460a7f9483628878cbac0b68e820251/aVCpCbEQVDk');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ferdev.my.id/downloader/ytshorts?link=https%3A%2F%2Fwww.youtube.com%2Fshorts%2FaVCpCbEQVDk&apikey=saved-api-key';
        });
    }

    public function test_ytshorts_page_preselects_ytshorts_provider(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('external-api.ytshorts'));

        $response->assertOk();
        $response->assertSeeLivewire(DownloaderWorkbench::class);
    }

    public function test_downloader_can_execute_ytmp4_request_using_dlink_payload(): void
    {
        $this->configureDownloaderSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/downloader/ytmp4*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'data' => [
                    'metadata' => [
                        'title' => 'Baby Shark Dance | #babyshark Most Viewed Video',
                        'quality' => '360p',
                    ],
                    'dlink' => 'https://redirector.googlevideo.com/videoplayback?expire=1784529659&ei=m25datm6DuGwp-',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(DownloaderWorkbench::class)
            ->set('selectedProvider', 'ytmp4')
            ->set('link', 'https://www.youtube.com/watch?v=XqZsoesa55w')
            ->call('run')
            ->assertHasNoErrors()
            ->assertSet('result.title', 'Baby Shark Dance | #babyshark Most Viewed Video')
            ->assertSet('result.downloadUrl', 'https://redirector.googlevideo.com/videoplayback?expire=1784529659&ei=m25datm6DuGwp-')
            ->assertSet('result.downloadOptions.0.label', 'Download (dlink)')
            ->assertSet('result.downloadOptions.0.url', 'https://redirector.googlevideo.com/videoplayback?expire=1784529659&ei=m25datm6DuGwp-');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ferdev.my.id/downloader/ytmp4?link=https%3A%2F%2Fwww.youtube.com%2Fwatch%3Fv%3DXqZsoesa55w&apikey=saved-api-key';
        });
    }

    public function test_ytmp4_page_preselects_ytmp4_provider(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('external-api.ytmp4'));

        $response->assertOk();
        $response->assertSeeLivewire(DownloaderWorkbench::class);
    }

    private function configureDownloaderSettings(): void
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
            'value' => 'saved-api-key',
            'is_active' => true,
        ]);
    }
}
