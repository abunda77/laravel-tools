<?php

namespace Tests\Feature\Freepik;

use App\Models\ApiKey;
use App\Services\Freepik\ImageGenerationService;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ImageGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_sends_expected_payload_and_returns_json_response(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/text-to-image/z-image' => Http::response([
                'data' => [
                    'task_id' => 'task-123',
                    'status' => 'CREATED',
                ],
            ], 200),
        ]);

        $response = app(ImageGenerationService::class)->generate('  a cinematic robot  ', 'square_hd', 'PNG');

        $this->assertSame('task-123', $response['data']['task_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.freepik.com/v1/ai/text-to-image/z-image'
                && $request->method() === 'POST'
                && $request->hasHeader('x-freepik-api-key', 'test-freepik-key')
                && $request['prompt'] === 'a cinematic robot'
                && $request['image_size'] === 'square_hd'
                && $request['output_format'] === 'png';
        });
    }

    public function test_generate_rejects_unsupported_image_size_before_requesting_freepik(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Image size tidak didukung');

        try {
            app(ImageGenerationService::class)->generate('a valid prompt', 'panorama');
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
            app(ImageGenerationService::class)->generate('a valid prompt');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_non_json_success_response_throws_controlled_exception(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/text-to-image/z-image' => Http::response('not json', 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Freepik API mengembalikan response yang tidak valid');

        app(ImageGenerationService::class)->generate('a valid prompt');
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
