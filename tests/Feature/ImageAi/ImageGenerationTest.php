<?php

namespace Tests\Feature\ImageAi;

use App\Livewire\Generation\ImageGeneration;
use App\Models\ApiKey;
use App\Models\User;
use App\Support\Settings\SystemSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ImageGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_page_is_displayed_for_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/generation/image');

        $response
            ->assertOk()
            ->assertSee('Generation Image Video')
            ->assertSee('Text to Image Generation');
    }

    public function test_component_only_keeps_the_10_most_recent_tasks_in_history(): void
    {
        $this->configureRequestSettings();
        $this->createFreepikApiKey();

        Cache::flush();

        Http::fake([
            'https://api.freepik.com/v1/ai/text-to-image/z-image' => Http::response([
                'data' => collect(range(1, 12))
                    ->map(fn (int $number): array => [
                        'task_id' => 'task-'.$number,
                        'status' => 'COMPLETED',
                    ])
                    ->all(),
            ], 200),
        ]);

        $component = Livewire::test(ImageGeneration::class)
            ->assertCount('taskHistory', 10)
            ->assertSet('taskHistory.0.task_id', 'task-12')
            ->assertSet('taskHistory.9.task_id', 'task-3')
            ->assertSee('task-12')
            ->assertSee('task-3');

        $taskIds = collect($component->get('taskHistory'))
            ->pluck('task_id')
            ->all();

        $this->assertSame(
            ['task-12', 'task-11', 'task-10', 'task-9', 'task-8', 'task-7', 'task-6', 'task-5', 'task-4', 'task-3'],
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
