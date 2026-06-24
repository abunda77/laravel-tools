<?php

namespace App\Livewire\ImageAi;

use App\Models\ApiKey;
use App\Services\ImageAi\FreeimageHostService;
use App\Services\ImageAi\Image2PromptService;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ImageToPrompt extends Component
{
    use WithFileUploads;

    public string $imageUrl = '';

    public ?TemporaryUploadedFile $imageFile = null;

    public bool $hasSavedApiKey = false;

    public ?string $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', Image2PromptService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function generate(Image2PromptService $image2Prompt, FreeimageHostService $freeimageHost): void
    {
        $this->reset(['result', 'errorMessage']);

        if (blank($this->imageUrl) && ! $this->imageFile instanceof TemporaryUploadedFile) {
            $this->errorMessage = 'Masukkan URL gambar atau upload file gambar.';

            return;
        }

        try {
            $link = $this->resolveImageLink($freeimageHost);
            $response = $image2Prompt->generate($link);
            $this->result = $response['result'];
            $this->hasSavedApiKey = true;
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function clearResult(): void
    {
        $this->reset(['result', 'errorMessage', 'imageUrl']);
        $this->imageFile = null;
    }

    public function render(): View
    {
        return view('livewire.image-ai.image-to-prompt');
    }

    private function resolveImageLink(FreeimageHostService $freeimageHost): string
    {
        $url = trim($this->imageUrl);

        if (filled($url)) {
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                throw new \RuntimeException('URL gambar tidak valid.');
            }

            return $url;
        }

        if (! $this->imageFile instanceof TemporaryUploadedFile) {
            throw new \RuntimeException('Masukkan URL gambar atau upload file gambar.');
        }

        $this->validateOnly('imageFile', [
            'imageFile' => ['nullable', 'image', 'max:5120'],
        ]);

        $contents = file_get_contents($this->imageFile->getRealPath());

        if ($contents === false) {
            throw new \RuntimeException('Gagal membaca file gambar.');
        }

        $mimeType = $this->imageFile->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode($contents);

        $uploadResult = $freeimageHost->uploadFromBase64($base64, $mimeType);

        return $uploadResult['url'];
    }
}
