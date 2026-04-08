<?php

namespace App\Livewire\ImageAi;

use App\Services\Freepik\ImprovePromptService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

class ImprovePrompt extends Component
{
    #[Validate('nullable|string|max:2500')]
    public string $prompt = '';

    #[Validate('required|in:image,video')]
    public string $type = 'image';

    #[Validate('required|regex:/^[a-z]{2}$/')]
    public string $language = 'en';

    #[Validate('nullable|url')]
    public string $webhookUrl = '';

    public ?string $taskId = null;

    public string $taskStatus = '';

    public array $generatedPrompts = [];

    public function improvePrompt(ImprovePromptService $service): void
    {
        $this->validate([
            'prompt' => ['nullable', 'string', 'max:2500'],
            'type' => ['required', 'in:image,video'],
            'language' => ['required', 'regex:/^[a-z]{2}$/'],
            'webhookUrl' => ['nullable', 'url'],
        ]);

        try {
            $response = $service->improve(
                $this->prompt,
                $this->type,
                $this->language,
                filled($this->webhookUrl) ? $this->webhookUrl : null,
            );

            $this->applyTaskResponse($response);
            session()->flash('success', 'Task Improve Prompt berhasil dibuat.');
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function checkTaskStatus(ImprovePromptService $service): void
    {
        if (! $this->taskId) {
            return;
        }

        try {
            $this->applyTaskResponse($service->checkStatus($this->taskId));
        } catch (\Throwable $exception) {
            $this->taskId = null;
            session()->flash('error', $exception->getMessage());
        }
    }

    public function clearResult(): void
    {
        $this->taskId = null;
        $this->taskStatus = '';
        $this->generatedPrompts = [];
    }

    public function render(): \Illuminate\Contracts\View\View
    {
        return view('livewire.image-ai.improve-prompt');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function applyTaskResponse(array $response): void
    {
        $data = $response['data'] ?? $response;

        if (! is_array($data)) {
            throw new RuntimeException('Freepik API mengembalikan response task yang tidak valid.');
        }

        $this->taskId = isset($data['task_id']) && is_string($data['task_id']) ? $data['task_id'] : $this->taskId;
        $this->taskStatus = isset($data['status']) && is_string($data['status']) ? $data['status'] : $this->taskStatus;

        if (isset($data['generated']) && is_array($data['generated']) && $data['generated'] !== []) {
            $this->generatedPrompts = array_values(array_filter($data['generated'], is_string(...)));
        }

        if ($this->generatedPrompts !== [] || in_array($this->taskStatus, ['COMPLETED', 'FAILED', 'ERROR'], true)) {
            $this->taskId = null;
        }
    }
}
