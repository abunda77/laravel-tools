<?php

namespace Tests\Feature\ImageAi;

use App\Models\ApiKey;
use App\Services\ImageAi\ZImageService;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ZImageServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureRequestSettings();
        $this->createKieAiApiKey();
    }

    public function test_create_task_sends_expected_payload_and_returns_task_id(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/createTask' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'taskId' => 'task-123',
                ],
            ], 200),
        ]);

        $service = app(ZImageService::class);
        $result = $service->createTask('A beautiful sunset over the ocean', '1:1', true);

        $this->assertIsArray($result);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals('task-123', $result['data']['taskId']);

        Http::assertSent(function ($request) {
            $this->assertEquals('POST', $request->method());
            $this->assertEquals('https://api.kie.ai/api/v1/jobs/createTask', $request->url());

            $body = $request->data();
            $this->assertEquals('z-image', $body['model']);
            $this->assertEquals('A beautiful sunset over the ocean', $body['input']['prompt']);
            $this->assertEquals('1:1', $body['input']['aspect_ratio']);
            $this->assertTrue($body['input']['nsfw_checker']);

            $this->assertEquals('Bearer test-kieai-key', $request->header('Authorization')[0]);

            return true;
        });
    }

    public function test_create_task_rejects_prompt_over_1000_chars(): void
    {
        $longPrompt = str_repeat('a', 1001);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt tidak boleh lebih dari 1000 karakter.');

        app(ZImageService::class)->createTask($longPrompt, '1:1', true);
    }

    public function test_create_task_rejects_unsupported_aspect_ratio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Aspect ratio tidak didukung: 21:9.');

        app(ZImageService::class)->createTask('A beautiful sunset', '21:9', true);
    }

    public function test_create_task_rejects_empty_prompt(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');

        app(ZImageService::class)->createTask('', '1:1', true);
    }

    public function test_inactive_api_key_is_not_used(): void
    {
        $this->createKieAiApiKey(isActive: false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Z-Image API key belum diatur atau tidak aktif. Tambahkan di Settings -> API Keys dengan name "kieai_provider".');

        app(ZImageService::class)->createTask('A beautiful sunset', '1:1', true);
    }

    public function test_check_status_returns_decoded_response(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/recordInfo*' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'taskId' => 'task-123',
                    'state' => 'success',
                    'resultJson' => json_encode(['resultUrls' => ['https://example.com/image.png']]),
                ],
            ], 200),
        ]);

        $result = app(ZImageService::class)->checkStatus('task-123');

        $this->assertIsArray($result);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals('task-123', $result['data']['taskId']);
        $this->assertEquals('success', $result['data']['state']);
        $this->assertIsString($result['data']['resultJson']);
    }

    public function test_non_json_response_throws_controlled_exception(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/createTask' => Http::response('Not JSON', 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Z-Image API mengembalikan response yang tidak valid (bukan JSON).');

        app(ZImageService::class)->createTask('A beautiful sunset', '1:1', true);
    }

    public function test_connection_error_throws_bahasa_indonesia_message(): void
    {
        Http::fake(function () {
            throw new ConnectionException('Connection refused');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak dapat terhubung ke API Z-Image. Periksa koneksi internet atau coba beberapa saat lagi.');

        app(ZImageService::class)->createTask('A beautiful sunset', '1:1', true);
    }

    private function configureRequestSettings(): void
    {
        $settings = app(SystemSettings::class);
        $settings->put('request_timeout_seconds', 30);
        $settings->put('request_retry_times', 1);
        $settings->put('request_retry_sleep_ms', 500);
    }

    private function createKieAiApiKey(bool $isActive = true): void
    {
        ApiKey::query()->where('name', 'kieai_provider')->delete();

        ApiKey::create([
            'name' => 'kieai_provider',
            'label' => 'Kie.ai Provider',
            'value' => 'test-kieai-key',
            'is_active' => $isActive,
        ]);
    }
}
