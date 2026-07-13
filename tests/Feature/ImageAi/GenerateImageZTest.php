<?php

namespace Tests\Feature\ImageAi;

use App\Livewire\ImageAi\GenerateImageZ;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class GenerateImageZTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureRequestSettings();
        $this->createKieAiApiKey();
    }

    public function test_generate_image_z_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/image-ai/generate-image-z');

        $response
            ->assertOk()
            ->assertSee('Generate Image Z')
            ->assertSee('Generate Gambar');
    }

    public function test_component_creates_task(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/createTask*' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'taskId' => 'task-123',
                ],
            ], 200),
        ]);

        Livewire::test(GenerateImageZ::class)
            ->set('prompt', 'A beautiful landscape')
            ->set('aspectRatio', '1:1')
            ->set('nsfwChecker', true)
            ->call('generateImage')
            ->assertSet('taskId', 'task-123')
            ->assertSet('taskState', 'waiting')
            ->assertHasNoErrors();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.kie.ai/api/v1/jobs/createTask'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-kieai-key')
                && $request['model'] === 'z-image'
                && $request['input']['prompt'] === 'A beautiful landscape'
                && $request['input']['aspect_ratio'] === '1:1'
                && $request['input']['nsfw_checker'] === true;
        });
    }

    public function test_component_applies_success_results(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/recordInfo*' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'taskId' => 'task-123',
                    'state' => 'success',
                    'resultJson' => json_encode(['resultUrls' => ['https://example.com/img.png']]),
                ],
            ], 200),
        ]);

        Livewire::test(GenerateImageZ::class)
            ->set('taskId', 'task-123')
            ->call('checkTaskStatus')
            ->assertSet('taskState', 'success')
            ->assertSet('taskId', null);

        Http::assertSent(function ($request) {
            return str_starts_with($request->url(), 'https://api.kie.ai/api/v1/jobs/recordInfo')
                && str_contains($request->url(), 'taskId=task-123');
        });
    }

    public function test_component_handles_failed_task(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/recordInfo*' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'taskId' => 'task-123',
                    'state' => 'fail',
                    'failCode' => 'GENERATION_FAILED',
                    'failMsg' => 'generation failed',
                ],
            ], 200),
        ]);

        Livewire::test(GenerateImageZ::class)
            ->set('taskId', 'task-123')
            ->call('checkTaskStatus')
            ->assertSet('taskState', 'fail')
            ->assertSet('taskId', null);
    }

    public function test_component_continues_polling_on_waiting_state(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/recordInfo*' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'taskId' => 'task-123',
                    'state' => 'waiting',
                    'resultJson' => null,
                ],
            ], 200),
        ]);

        Livewire::test(GenerateImageZ::class)
            ->set('taskId', 'task-123')
            ->call('checkTaskStatus')
            ->assertSet('taskId', 'task-123')
            ->assertSet('taskState', 'waiting');
    }

    public function test_component_validates_prompt_max_length(): void
    {
        $longPrompt = str_repeat('a', 1001);

        Livewire::test(GenerateImageZ::class)
            ->set('prompt', $longPrompt)
            ->call('generateImage')
            ->assertHasErrors('prompt');

        Http::assertNothingSent();
    }

    public function test_component_flashes_error_when_no_taskid_in_response(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/createTask*' => Http::response([
                'code' => 402,
                'msg' => 'Insufficient account balance',
                'data' => null,
            ], 200),
        ]);

        Livewire::test(GenerateImageZ::class)
            ->set('prompt', 'A beautiful landscape')
            ->set('aspectRatio', '1:1')
            ->set('nsfwChecker', true)
            ->call('generateImage')
            ->assertSet('taskId', null)
            ->assertSet('taskState', '');
    }

    public function test_component_handles_malformed_result_json(): void
    {
        Http::fake([
            'api.kie.ai/api/v1/jobs/recordInfo*' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => [
                    'taskId' => 'task-123',
                    'state' => 'success',
                    'resultJson' => 'not-json{',
                ],
            ], 200),
        ]);

        Livewire::test(GenerateImageZ::class)
            ->set('taskId', 'task-123')
            ->call('checkTaskStatus')
            ->assertSet('taskId', null)
            ->assertSet('resultUrls', []);
    }

    public function test_component_clear_result_keeps_form_inputs(): void
    {
        Livewire::test(GenerateImageZ::class)
            ->set('prompt', 'A beautiful sunset')
            ->set('aspectRatio', '16:9')
            ->set('nsfwChecker', false)
            ->set('taskId', 'task-123')
            ->set('taskState', 'success')
            ->set('resultUrls', ['https://example.com/image.png'])
            ->call('clearResult')
            ->assertSet('taskId', null)
            ->assertSet('taskState', '')
            ->assertSet('resultUrls', [])
            ->assertSet('prompt', 'A beautiful sunset')
            ->assertSet('aspectRatio', '16:9')
            ->assertSet('nsfwChecker', false);
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

    private function createKieAiApiKey(bool $isActive = true): void
    {
        ApiKey::query()->create([
            'name' => 'kieai_provider',
            'label' => 'Kie.ai',
            'value' => 'test-kieai-key',
            'is_active' => $isActive,
        ]);
    }
}
