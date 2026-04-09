<?php

namespace App\Livewire\Generation;

use App\Services\Freepik\VideoGenerationService;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class VideoGeneration extends Component
{
    private const TASK_HISTORY_CACHE_KEY = 'freepik.video.task-history';

    private const TASK_HISTORY_LIMIT = 10;

    public string $prompt = '';

    public string $aspectRatio = '16:9';

    public string $duration = '5';

    public string $negativePrompt = 'blur, distort, and low quality';

    public bool $generateAudio = true;

    public float $cfgScale = 0.5;

    public array $videos = [];

    public bool $isGenerating = false;

    public ?string $taskId = null;

    public string $taskStatus = '';

    public array $taskHistory = [];

    /**
     * @var list<string>
     */
    public array $aspectRatioOptions = [];

    /**
     * @var list<string>
     */
    public array $durationOptions = [];

    public function mount(VideoGenerationService $service): void
    {
        $this->aspectRatioOptions = $service->aspectRatioOptions();
        $this->durationOptions = $service->durationOptions();
        $this->loadTaskHistory($service);
    }

    public function loadTaskHistory(VideoGenerationService $service): void
    {
        try {
            $response = Cache::remember(
                self::TASK_HISTORY_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $service->getTasksHistory(),
            );

            $tasks = Arr::get($response, 'data');

            if (is_array($tasks)) {
                $this->taskHistory = array_values(
                    array_slice(array_reverse($tasks), 0, self::TASK_HISTORY_LIMIT),
                );
            }
        } catch (Exception $exception) {
            session()->flash('error_history', 'Failed to load task history: '.$exception->getMessage());
        }
    }

    public function generateVideo(VideoGenerationService $service): void
    {
        $this->validate([
            'prompt' => 'required|string|min:3',
            'aspectRatio' => 'required|in:16:9,9:16,1:1',
            'duration' => 'required|in:3,4,5,6,7,8,9,10,11,12,13,14,15',
            'negativePrompt' => 'nullable|string|max:2500',
            'cfgScale' => 'required|numeric|min:0|max:1',
        ]);

        $this->isGenerating = true;

        try {
            $response = $service->generate(
                $this->prompt,
                $this->aspectRatio,
                $this->duration,
                $this->negativePrompt,
                $this->generateAudio,
                $this->cfgScale,
            );

            $data = $response['data'] ?? $response;

            if (isset($data['task_id'])) {
                $this->taskId = $data['task_id'];
                $this->taskStatus = $data['status'] ?? 'CREATED';

                if (isset($data['generated']) && is_array($data['generated']) && $data['generated'] !== []) {
                    $this->videos = array_values(array_unique(array_merge($data['generated'], $this->videos)));
                    $this->taskId = null;
                }

                $this->forgetTaskHistoryCache();
                $this->loadTaskHistory($service);
            } else {
                session()->flash('info', 'Task submitted: '.json_encode($response));
            }
        } catch (Exception $exception) {
            session()->flash('error', $exception->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    public function checkTaskStatus(VideoGenerationService $service): void
    {
        if (! $this->taskId) {
            return;
        }

        try {
            $response = $service->checkStatus($this->taskId);
            $data = $response['data'] ?? $response;

            if (isset($data['status'])) {
                $this->taskStatus = $data['status'];
            }

            if (isset($data['generated']) && is_array($data['generated']) && $data['generated'] !== []) {
                $this->videos = array_values(array_unique(array_merge($data['generated'], $this->videos)));
                $this->taskId = null;
                session()->flash('success', 'Video generated successfully!');
                $this->forgetTaskHistoryCache();
                $this->loadTaskHistory($service);
            } elseif (in_array($this->taskStatus, ['FAILED', 'ERROR'], true)) {
                session()->flash('error', 'Video generation failed.');
                $this->taskId = null;
                $this->forgetTaskHistoryCache();
                $this->loadTaskHistory($service);
            }
        } catch (Exception $exception) {
            session()->flash('error', $exception->getMessage());
            $this->taskId = null;
        }
    }

    public function refreshTaskHistory(VideoGenerationService $service): void
    {
        $this->forgetTaskHistoryCache();
        $this->loadTaskHistory($service);
    }

    private function forgetTaskHistoryCache(): void
    {
        Cache::forget(self::TASK_HISTORY_CACHE_KEY);
    }

    public function render()
    {
        return view('livewire.generation.video-generation');
    }
}
