<?php

namespace Tests\Feature\ImageAi;

use App\Livewire\ImageAi\ImprovePrompt;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ImprovePromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_improve_prompt_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/image-ai/improve-prompt');

        $response
            ->assertOk()
            ->assertSee('Improve Prompt')
            ->assertSee('Tingkatkan Prompt')
            ->assertSee('Copy text');
    }

    public function test_component_creates_improve_prompt_task(): void
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

        Livewire::test(ImprovePrompt::class)
            ->set('prompt', 'A beautiful landscape')
            ->set('type', 'video')
            ->set('language', 'id')
            ->call('improvePrompt')
            ->assertSet('taskId', 'task-123')
            ->assertSet('taskStatus', 'CREATED');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.freepik.com/v1/ai/improve-prompt'
            && $request['prompt'] === 'A beautiful landscape'
            && $request['type'] === 'video'
            && $request['language'] === 'id');
    }

    public function test_component_applies_completed_status_results(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Http::fake([
            'https://api.freepik.com/v1/ai/improve-prompt/task-123' => Http::response([
                'data' => [
                    'task_id' => 'task-123',
                    'status' => 'COMPLETED',
                    'generated' => ['An improved cinematic prompt.'],
                ],
            ], 200),
        ]);

        Livewire::test(ImprovePrompt::class)
            ->set('taskId', 'task-123')
            ->call('checkTaskStatus')
            ->assertSet('taskId', null)
            ->assertSet('taskStatus', 'COMPLETED')
            ->assertSee('An improved cinematic prompt.');
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
