<?php

namespace App\Livewire\ExternalApi;

use App\Models\ApiKey;
use App\Services\ExternalApi\DownloaderService;
use Livewire\Component;

class DownloaderWorkbench extends Component
{
    public string $selectedProvider = 'instagram';

    public string $link = '';

    public string $apiKeyOverride = '';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    /**
     * @var array<string, array<string, string>>
     */
    public array $providers = [];

    public function mount(): void
    {
        $this->providers = DownloaderService::providers();
        $this->hasSavedApiKey = filled(ApiKey::valueByName(DownloaderService::API_KEY_NAME));
    }

    public function selectProvider(string $provider): void
    {
        if (! array_key_exists($provider, $this->providers)) {
            return;
        }

        $this->selectedProvider = $provider;
        $this->resetResult();
    }

    public function run(DownloaderService $downloaderService): void
    {
        $this->validate([
            'selectedProvider' => ['required', 'in:instagram,tiktok,facebook'],
            'link' => ['required', 'url', 'max:2048'],
            'apiKeyOverride' => ['nullable', 'string', 'max:2048'],
        ]);

        $savedApiKey = filled($this->apiKeyOverride) ? null : ApiKey::valueByName(DownloaderService::API_KEY_NAME);
        $apiKey = $this->apiKeyOverride ?: $savedApiKey;

        if (! filled($apiKey)) {
            $this->addError('apiKeyOverride', 'API key belum tersedia. Simpan dulu di Settings atau isi override API key.');

            return;
        }

        try {
            $this->result = $downloaderService->execute(
                $this->selectedProvider,
                $this->link,
                $apiKey,
            );

            $this->errorMessage = null;
            $this->hasSavedApiKey = filled($savedApiKey) || ($this->hasSavedApiKey && filled($this->apiKeyOverride));
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getPrettyResponseProperty(): string
    {
        return json_encode($this->result['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function resetResult(): void
    {
        $this->result = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.external-api.downloader-workbench');
    }
}
