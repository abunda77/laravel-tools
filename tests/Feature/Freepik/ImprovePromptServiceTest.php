<?php

namespace Tests\Feature\Freepik;

use App\Models\ApiKey;
use App\Services\Freepik\ImprovePromptService;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ImprovePromptServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_improve_sends_expected_payload_and_returns_json_response(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/improve-prompt' => Http::response([
                'data' => [
                    'task_id' => 'task-123',
                    'status' => 'CREATED',
                    'generated' => [],
                ],
            ], 200),
        ]);

        $response = app(ImprovePromptService::class)->improve(
            '  A beautiful landscape  ',
            'VIDEO',
            'ID',
            'https://example.com/webhook',
        );

        $this->assertSame('task-123', $response['data']['task_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.freepik.com/v1/ai/improve-prompt'
                && $request->method() === 'POST'
                && $request->hasHeader('x-freepik-api-key', 'test-freepik-key')
                && $request['prompt'] === 'A beautiful landscape'
                && $request['type'] === 'video'
                && $request['language'] === 'id'
                && $request['webhook_url'] === 'https://example.com/webhook';
        });
    }

    public function test_check_status_uses_task_endpoint(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/improve-prompt/task-123' => Http::response([
                'data' => [
                    'task_id' => 'task-123',
                    'status' => 'COMPLETED',
                    'generated' => ['A cinematic landscape prompt.'],
                ],
            ], 200),
        ]);

        $response = app(ImprovePromptService::class)->checkStatus('task-123');

        $this->assertSame('COMPLETED', $response['data']['status']);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.freepik.com/v1/ai/improve-prompt/task-123'
            && $request->method() === 'GET');
    }

    public function test_improve_allows_blank_prompt_for_creative_generation(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/improve-prompt' => Http::response([
                'data' => [
                    'task_id' => 'task-blank',
                    'status' => 'CREATED',
                    'generated' => [],
                ],
            ], 200),
        ]);

        app(ImprovePromptService::class)->improve('   ');

        Http::assertSent(fn ($request) => $request['prompt'] === ''
            && $request['type'] === 'image'
            && $request['language'] === 'en');
    }

    public function test_improve_rejects_unsupported_type_before_requesting_freepik(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipe prompt tidak didukung');

        try {
            app(ImprovePromptService::class)->improve('a prompt', 'audio');
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
            app(ImprovePromptService::class)->improve('a prompt');
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
