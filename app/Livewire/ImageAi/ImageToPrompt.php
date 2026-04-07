<?php

namespace App\Livewire\ImageAi;

use App\Services\Freepik\ImageToPromptService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use RuntimeException;

class ImageToPrompt extends Component
{
    use WithFileUploads;

    #[Validate('nullable|url|required_without:imageFile')]
    public string $imageUrl = '';

    #[Validate('nullable|image|max:5120|required_without:imageUrl')]
    public ?TemporaryUploadedFile $imageFile = null;

    #[Validate('nullable|url')]
    public string $webhookUrl = '';

    public ?string $taskId = null;

    public string $taskStatus = '';

    public array $generatedPrompts = [];

    public function generatePrompt(ImageToPromptService $service): void
    {
        $this->validate([
            'imageUrl' => ['nullable', 'url', 'required_without:imageFile'],
            'imageFile' => ['nullable', 'image', 'max:5120', 'required_without:imageUrl'],
            'webhookUrl' => ['nullable', 'url'],
        ]);

        try {
            $response = $service->generate(
                $this->imageInput(),
                filled($this->webhookUrl) ? $this->webhookUrl : null,
            );

            $this->applyTaskResponse($response);
            session()->flash('success', 'Task Image2Prompt berhasil dibuat.');
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function checkTaskStatus(ImageToPromptService $service): void
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
        return view('livewire.image-ai.image-to-prompt');
    }

    private function imageInput(): string
    {
        if (filled($this->imageUrl)) {
            return trim($this->imageUrl);
        }

        if (! $this->imageFile instanceof TemporaryUploadedFile) {
            throw new RuntimeException('Pilih URL gambar atau upload file gambar terlebih dahulu.');
        }

        $contents = file_get_contents($this->imageFile->getRealPath());

        if ($contents === false) {
            throw new RuntimeException('File gambar tidak dapat dibaca.');
        }

        return base64_encode($contents);
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
