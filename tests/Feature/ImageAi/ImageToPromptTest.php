<?php

namespace Tests\Feature\ImageAi;

use App\Livewire\ImageAi\ImageToPrompt;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
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

    public function test_forwarded_https_requests_generate_secure_urls(): void
    {
        Route::middleware('web')->get('/_testing/proxy-url', function (Request $request) {
            return response()->json([
                'is_secure' => $request->isSecure(),
                'upload_url' => url('/livewire/upload-file'),
            ]);
        });

        $response = $this
            ->withServerVariables([
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST' => 'tools.serverdata.my.id',
                'HTTP_X_FORWARDED_PORT' => '443',
                'HTTP_HOST' => 'tools.serverdata.my.id',
                'SERVER_PORT' => 80,
                'HTTPS' => 'off',
                'REMOTE_ADDR' => '127.0.0.1',
            ])
            ->get('/_testing/proxy-url');

        $response
            ->assertOk()
            ->assertJson([
                'is_secure' => true,
                'upload_url' => 'https://tools.serverdata.my.id/livewire/upload-file',
            ]);
    }

    public function test_component_generates_prompt_task_from_image_url(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/image-to-prompt' => Http::response([
                'data' => [
                    'task_id' => 'task-123',
                    'status' => 'CREATED',
                    'generated' => [],
                ],
            ], 200),
        ]);

        Livewire::test(ImageToPrompt::class)
            ->set('imageUrl', 'https://example.com/sample-image.jpg')
            ->call('generatePrompt')
            ->assertSet('taskId', 'task-123')
            ->assertSet('taskStatus', 'CREATED');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.freepik.com/v1/ai/image-to-prompt'
            && $request['image'] === 'https://example.com/sample-image.jpg');
    }

    public function test_component_applies_completed_status_results(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/image-to-prompt/task-123' => Http::response([
                'data' => [
                    'task_id' => 'task-123',
                    'status' => 'COMPLETED',
                    'generated' => ['A detailed prompt from the image.'],
                ],
            ], 200),
        ]);

        Livewire::test(ImageToPrompt::class)
            ->set('taskId', 'task-123')
            ->call('checkTaskStatus')
            ->assertSet('taskId', null)
            ->assertSet('taskStatus', 'COMPLETED')
            ->assertSee('A detailed prompt from the image.');
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

    private function createFreepikApiKey(bool $isActive = true): void
    {
        ApiKey::query()->create([
            'name' => 'freepik_provider',
            'label' => 'Freepik',
            'value' => 'test-freepik-key',
            'is_active' => $isActive,
        ]);
    }
}
