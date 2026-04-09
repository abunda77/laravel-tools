<?php

namespace Tests\Feature\ImageAi;

use App\Livewire\Generation\VideoGeneration;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class VideoGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_generation_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/generation/video');

        $response
            ->assertOk()
            ->assertSee('Generation Video AI')
            ->assertSee('Text to Video Generation');
    }

    public function test_video_generation_component_only_keeps_the_10_most_recent_tasks_in_history(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Cache::flush();

        Http::fake([
            'https://api.freepik.com/v1/ai/video/kling-v3' => Http::response([
                'data' => collect(range(1, 12))
                    ->map(fn (int $number): array => [
                        'task_id' => 'video-task-'.$number,
                        'status' => 'COMPLETED',
                    ])
                    ->all(),
            ], 200),
        ]);

        $component = Livewire::test(VideoGeneration::class)
            ->assertCount('taskHistory', 10)
            ->assertSet('taskHistory.0.task_id', 'video-task-12')
            ->assertSet('taskHistory.9.task_id', 'video-task-3')
            ->assertSee('video-task-12')
            ->assertSee('video-task-3');

        $taskIds = collect($component->get('taskHistory'))
            ->pluck('task_id')
            ->all();

        $this->assertSame(
            ['video-task-12', 'video-task-11', 'video-task-10', 'video-task-9', 'video-task-8', 'video-task-7', 'video-task-6', 'video-task-5', 'video-task-4', 'video-task-3'],
            $taskIds,
        );
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
