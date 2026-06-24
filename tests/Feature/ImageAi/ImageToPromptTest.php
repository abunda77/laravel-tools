<?php

namespace Tests\Feature\ImageAi;

use App\Livewire\ImageAi\ImageToPrompt;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ImageToPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_image2prompt_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/image-ai/image2prompt');

        $response
            ->assertOk()
            ->assertSee('Image2Prompt')
            ->assertSee('Buat Prompt dari Gambar');
    }

    public function test_image2prompt_page_requires_authentication(): void
    {
        $response = $this->get(route('image-ai.image2prompt'));

        $response->assertRedirect(route('login'));
    }

    public function test_component_generates_prompt_from_image_url(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/tools/img2prompt*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => 'Gambar ini menampilkan monitor komputer dengan browser web terbuka menampilkan situs.',
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImageToPrompt::class)
            ->set('imageUrl', 'https://example.com/sample-image.jpg')
            ->call('generate')
            ->assertHasNoErrors()
            ->assertSet('result', 'Gambar ini menampilkan monitor komputer dengan browser web terbuka menampilkan situs.')
            ->assertSee('Generated Prompt')
            ->assertSee('Copy');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.ferdev.my.id/tools/img2prompt?link=https%3A%2F%2Fexample.com%2Fsample-image.jpg&apikey=saved-downloader-key';
        });
    }

    public function test_component_generates_prompt_from_uploaded_file_via_freeimage(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();
        $this->createFreeimageApiKey();

        $uploadedUrl = 'https://freeimage.host/images/2026/06/24/test-image.png';

        Http::fake([
            'https://freeimage.host/api/1/upload/*' => Http::response([
                'status_code' => 200,
                'status_txt' => 'OK',
                'success' => ['message' => 'image uploaded', 'code' => 200],
                'image' => [
                    'url' => $uploadedUrl,
                    'display_url' => $uploadedUrl,
                    'size' => 1024,
                    'mime' => 'image/png',
                ],
            ], 200),
            'https://api.ferdev.my.id/tools/img2prompt*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => 'Prompt dari file upload.',
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImageToPrompt::class)
            ->set('imageFile', UploadedFile::fake()->image('test.png', 100, 100))
            ->call('generate')
            ->assertHasNoErrors()
            ->assertSet('result', 'Prompt dari file upload.')
            ->assertSee('Generated Prompt');

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://freeimage.host/api/1/upload/');
        });

        Http::assertSent(function ($request) use ($uploadedUrl) {
            return str_starts_with($request->url(), 'https://api.ferdev.my.id/tools/img2prompt')
                && str_contains($request->url(), urlencode($uploadedUrl));
        });
    }

    public function test_image2prompt_without_inputs_shows_error(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImageToPrompt::class)
            ->call('generate')
            ->assertSet('errorMessage', 'Masukkan URL gambar atau upload file gambar.');
    }

    public function test_image2prompt_requires_active_downloader_provider_api_key(): void
    {
        $this->configureRequestSettings();

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImageToPrompt::class)
            ->set('imageUrl', 'https://example.com/sample-image.jpg')
            ->call('generate')
            ->assertSet('errorMessage', 'API key Image2Prompt belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "downloader_provider".');
    }

    public function test_image2prompt_shows_missing_key_status(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImageToPrompt::class)
            ->assertSet('hasSavedApiKey', false)
            ->assertSee('Missing');
    }

    public function test_image2prompt_can_clear_result(): void
    {
        $this->configureRequestSettings();
        $this->createDownloaderApiKey();

        Http::fake([
            'https://api.ferdev.my.id/tools/img2prompt*' => Http::response([
                'success' => true,
                'status' => 200,
                'author' => 'Feri',
                'result' => 'A descriptive prompt.',
            ], 200),
        ]);

        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ImageToPrompt::class)
            ->set('imageUrl', 'https://example.com/sample-image.jpg')
            ->call('generate')
            ->assertSet('result', 'A descriptive prompt.')
            ->call('clearResult')
            ->assertSet('result', null)
            ->assertSet('errorMessage', null)
            ->assertSet('imageUrl', '');
    }

    private function configureRequestSettings(): void
    {
        app(SystemSettings::class)->putMany([
            'request_timeout_seconds' => 30,
            'request_retry_times' => 0,
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

    private function createFreeimageApiKey(): void
    {
        ApiKey::query()->create([
            'name' => 'freeimage_host',
            'label' => 'Freeimage Host',
            'value' => 'saved-freeimage-key',
            'is_active' => true,
        ]);
    }
}
