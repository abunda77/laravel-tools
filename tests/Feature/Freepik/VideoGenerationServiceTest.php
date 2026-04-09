<?php

namespace Tests\Feature\Freepik;

use App\Models\ApiKey;
use App\Services\Freepik\VideoGenerationService;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class VideoGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_sends_expected_payload_and_returns_json_response(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/video/kling-v3-std' => Http::response([
                'data' => [
                    'task_id' => 'video-task-123',
                    'status' => 'CREATED',
                ],
            ], 200),
        ]);

        $response = app(VideoGenerationService::class)->generate(
            '  cinematic astronaut walking through neon rain  ',
            '9:16',
            '6',
            'blurry faces',
            true,
            0.7,
        );

        $this->assertSame('video-task-123', $response['data']['task_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.freepik.com/v1/ai/video/kling-v3-std'
                && $request->method() === 'POST'
                && $request->hasHeader('x-freepik-api-key', 'test-freepik-key')
                && $request['prompt'] === 'cinematic astronaut walking through neon rain'
                && $request['aspect_ratio'] === '9:16'
                && $request['duration'] === '6'
                && $request['negative_prompt'] === 'blurry faces'
                && $request['generate_audio'] === true
                && $request['multi_shot'] === false
                && $request['cfg_scale'] === 0.7;
        });
    }

    public function test_generate_rejects_unsupported_aspect_ratio_before_requesting_freepik(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aspect ratio tidak didukung');

        try {
            app(VideoGenerationService::class)->generate('a valid prompt', '4:5');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_inactive_freepik_key_is_not_used_for_video_generation(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey(isActive: false);

        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Freepik API key belum diatur atau tidak aktif');

        try {
            app(VideoGenerationService::class)->generate('a valid prompt');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_non_json_success_response_throws_controlled_exception(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/video/kling-v3-std' => Http::response('not json', 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Freepik Video API mengembalikan response yang tidak valid');

        app(VideoGenerationService::class)->generate('a valid prompt');
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
