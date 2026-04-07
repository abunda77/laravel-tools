<?php

namespace App\Livewire\Generation;

use App\Services\Freepik\ImageGenerationService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

class ImageGeneration extends Component
{
    private const TASK_HISTORY_CACHE_KEY = 'freepik.task-history';

    public string $prompt = '';

    public string $imageSize = 'square_hd';

    public array $images = [];

    public bool $isGenerating = false;

    public ?string $taskId = null;

    public string $taskStatus = '';

    public array $taskHistory = [];

    public function mount(ImageGenerationService $service)
    {
        $this->loadTaskHistory($service);
    }

    public function loadTaskHistory(ImageGenerationService $service)
    {
        try {
            $response = Cache::remember(
                self::TASK_HISTORY_CACHE_KEY,
                now()->addSeconds(30),
                fn () => $service->getTasksHistory(),
            );

            if (isset($response['data']) && is_array($response['data'])) {
                // Reverse the array so newest tasks are first, assuming the API returns oldest first or chronological
                $this->taskHistory = array_reverse($response['data']);
            }
        } catch (Exception $e) {
            // fail silently or flash error
            session()->flash('error_history', 'Failed to load task history: '.$e->getMessage());
        }
    }

    public function generateImage(ImageGenerationService $service)
    {
        $this->validate([
            'prompt' => 'required|string|min:3',
            'imageSize' => 'required|in:square,square_hd,portrait_3_4,portrait_9_16,landscape_4_3,landscape_16_9',
        ]);

        $this->isGenerating = true;

        try {
            $response = $service->generate($this->prompt, $this->imageSize);

            $data = $response['data'] ?? $response;

            if (isset($data['task_id'])) {
                $this->taskId = $data['task_id'];
                $this->taskStatus = $data['status'] ?? 'CREATED';

                if (isset($data['generated']) && ! empty($data['generated'])) {
                    $this->images = array_merge($this->images, $data['generated']);
                    $this->taskId = null;
                }
                $this->forgetTaskHistoryCache();
                $this->loadTaskHistory($service); // refresh history
            } else {
                session()->flash('info', 'Task submitted: '.json_encode($response));
            }
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
        } finally {
            $this->isGenerating = false;
        }
    }

    public function checkTaskStatus(ImageGenerationService $service)
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

            if (isset($data['generated']) && ! empty($data['generated'])) {
                $this->images = array_unique(array_merge($data['generated'], $this->images));
                $this->taskId = null; // stop polling
                session()->flash('success', 'Image generated successfully!');
                $this->forgetTaskHistoryCache();
                $this->loadTaskHistory($service); // refresh history
            } elseif ($this->taskStatus === 'FAILED' || $this->taskStatus === 'ERROR') {
                session()->flash('error', 'Image generation failed.');
                $this->taskId = null;
                $this->forgetTaskHistoryCache();
                $this->loadTaskHistory($service); // refresh history
            }
        } catch (Exception $e) {
            session()->flash('error', $e->getMessage());
            $this->taskId = null;
        }
    }

    public function refreshTaskHistory(ImageGenerationService $service): void
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
        return view('livewire.generation.image-generation');
    }
}
