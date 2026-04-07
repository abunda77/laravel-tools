<?php

namespace Tests\Feature\ExternalApi;

use App\Livewire\ExternalApi\DownloaderWorkbench;
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
        app(SystemSettings::class)->putMany([
            'provider_base_url' => 'https://api.ferdev.my.id',
            'provider_api_key' => 'saved-api-key',
            'request_timeout_seconds' => 30,
            'request_retry_times' => 1,
            'request_retry_sleep_ms' => 100,
            'queue_connection' => 'database',
        ]);

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
        app(SystemSettings::class)->putMany([
            'provider_base_url' => 'https://api.ferdev.my.id',
            'provider_api_key' => 'saved-api-key',
            'request_timeout_seconds' => 30,
            'request_retry_times' => 1,
            'request_retry_sleep_ms' => 100,
            'queue_connection' => 'database',
        ]);

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
        app(SystemSettings::class)->putMany([
            'provider_base_url' => 'https://api.ferdev.my.id',
            'provider_api_key' => 'saved-api-key',
            'request_timeout_seconds' => 30,
            'request_retry_times' => 1,
            'request_retry_sleep_ms' => 100,
            'queue_connection' => 'database',
        ]);

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
}
