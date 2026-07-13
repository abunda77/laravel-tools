<?php

namespace App\Livewire\ImageAi;

use App\Services\ImageAi\ZImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

class GenerateImageZ extends Component
{
    #[Validate('required|string|min:3|max:1000')]
    public string $prompt = '';

    #[Validate('required|in:1:1,4:3,3:4,16:9,9:16')]
    public string $aspectRatio = '1:1';

    #[Validate('boolean')]
    public bool $nsfwChecker = true;

    public ?string $taskId = null;

    public string $taskState = '';

    public array $resultUrls = [];

    public function generateImage(ZImageService $service): void
    {
        $this->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:1000'],
            'aspectRatio' => ['required', 'in:1:1,4:3,3:4,16:9,9:16'],
            'nsfwChecker' => ['boolean'],
        ]);

        try {
            $response = $service->createTask($this->prompt, $this->aspectRatio, $this->nsfwChecker);
            $data = $response['data'] ?? $response;

            if (isset($data['taskId']) && is_string($data['taskId']) && $data['taskId'] !== '') {
                $this->taskId = $data['taskId'];
                $this->taskState = 'waiting';
                session()->flash('success', 'Task Z-Image berhasil dibuat. Menunggu hasil...');
            } else {
                $message = Arr::get($response, 'msg', 'Gagal membuat task Z-Image: response tidak berisi taskId.');
                session()->flash('error', $message);
            }
        } catch (\Throwable $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function checkTaskStatus(ZImageService $service): void
    {
        if (! $this->taskId) {
            return;
        }

        try {
            $response = $service->checkStatus($this->taskId);
            $this->applyTaskResponse($response);
        } catch (\Throwable $exception) {
            $this->taskId = null;
            session()->flash('error', $exception->getMessage());
        }
    }

    public function clearResult(): void
    {
        $this->reset(['taskId', 'taskState', 'resultUrls']);
    }

    public function render(): View
    {
        return view('livewire.image-ai.generate-image-z');
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function applyTaskResponse(array $response): void
    {
        $data = $response['data'] ?? $response;

        if (! is_array($data)) {
            throw new RuntimeException('Z-Image API mengembalikan response task yang tidak valid.');
        }

        if (isset($data['state']) && is_string($data['state'])) {
            $this->taskState = $data['state'];
        }

        if ($this->taskState === 'success') {
            $this->extractResultUrls($data);
            $this->taskId = null;
            session()->flash('success', 'Gambar berhasil dibuat.');
        } elseif ($this->taskState === 'fail') {
            $this->taskId = null;
            session()->flash('error', Arr::get($data, 'failMsg', 'Task Z-Image gagal.'));
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractResultUrls(array $data): void
    {
        $resultJson = Arr::get($data, 'resultJson');

        if (! is_string($resultJson) || $resultJson === '') {
            session()->flash('error', 'Gagal membaca hasil gambar dari response Z-Image.');

            return;
        }

        $decoded = json_decode($resultJson, true);

        if (! is_array($decoded)) {
            session()->flash('error', 'Gagal membaca hasil gambar dari response Z-Image.');

            return;
        }

        $urls = Arr::get($decoded, 'resultUrls');

        if (! is_array($urls) || $urls === []) {
            session()->flash('error', 'Gagal membaca hasil gambar dari response Z-Image.');

            return;
        }

        $this->resultUrls = array_values(array_filter($urls, 'is_string'));
    }
}
