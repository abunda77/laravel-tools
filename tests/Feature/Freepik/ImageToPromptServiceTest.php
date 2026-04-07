<?php

namespace Tests\Feature\Freepik;

use App\Models\ApiKey;
use App\Services\Freepik\ImageToPromptService;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ImageToPromptServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_sends_expected_payload_and_returns_json_response(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/image-to-prompt' => Http::response([
                'data' => [
                    'task_id' => '046b6c7f-0b8a-43b9-b35d-6489e6daee91',
                    'status' => 'CREATED',
                    'generated' => [],
                ],
            ], 200),
        ]);

        $response = app(ImageToPromptService::class)->generate(
            '  https://example.com/sample-image.jpg  ',
            'https://example.com/webhook',
        );

        $this->assertSame('046b6c7f-0b8a-43b9-b35d-6489e6daee91', $response['data']['task_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.freepik.com/v1/ai/image-to-prompt'
                && $request->method() === 'POST'
                && $request->hasHeader('x-freepik-api-key', 'test-freepik-key')
                && $request['image'] === 'https://example.com/sample-image.jpg'
                && $request['webhook_url'] === 'https://example.com/webhook';
        });
    }

    public function test_check_status_uses_task_endpoint(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/image-to-prompt/task-123' => Http::response([
                'data' => [
                    'task_id' => 'task-123',
                    'status' => 'COMPLETED',
                    'generated' => ['A cinematic prompt.'],
                ],
            ], 200),
        ]);

        $response = app(ImageToPromptService::class)->checkStatus('task-123');

        $this->assertSame('COMPLETED', $response['data']['status']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.freepik.com/v1/ai/image-to-prompt/task-123'
            && $request->method() === 'GET');
    }

    public function test_generate_rejects_blank_image_before_requesting_freepik(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image tidak boleh kosong');

        try {
            app(ImageToPromptService::class)->generate('  ');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_inactive_freepik_key_is_not_used(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey(isActive: false);

        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Freepik API key belum diatur atau tidak aktif');

        try {
            app(ImageToPromptService::class)->generate('https://example.com/sample-image.jpg');
        } finally {
            Http::assertNothingSent();
        }
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
