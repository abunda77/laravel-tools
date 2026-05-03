<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\FreepikImageSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FreepikImage extends Component
{
    public string $query = 'white t-shirt mockup';

    public int $limit = 12;

    public string $order = 'relevance';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?array $selectedResource = null;

    public array $selectedFormats = [];

    public ?array $selectedFormatDownload = null;

    public ?string $selectedFormat = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', FreepikImageSearchService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(FreepikImageSearchService $service): void
    {
        $this->query = trim($this->query);

        $this->validate([
            'query' => ['required', 'string', 'max:255'],
            'limit' => ['required', 'integer', 'min:1', 'max:50'],
            'order' => ['required', 'in:relevance,recent'],
        ]);

        try {
            $this->result = $service->search($this->query, 1, $this->limit, $this->order);
            $this->hasSavedApiKey = true;
            $this->errorMessage = null;
            $this->selectedResource = null;
            $this->selectedFormats = [];
            $this->selectedFormat = null;
            $this->selectedFormatDownload = null;
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->selectedResource = null;
            $this->selectedFormats = [];
            $this->selectedFormat = null;
            $this->selectedFormatDownload = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function selectResource(int $resourceId, FreepikImageSearchService $service): void
    {
        try {
            $this->selectedResource = $service->getResourceDetails($resourceId);
            $this->selectedFormats = $this->selectedResource['availableFormats'] ?? [];
            $this->selectedFormat = null;
            $this->selectedFormatDownload = null;
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function downloadFormat(string $format, FreepikImageSearchService $service): void
    {
        $resourceId = (int) ($this->selectedResource['id'] ?? 0);

        if ($resourceId < 1) {
            $this->errorMessage = 'Pilih resource terlebih dahulu sebelum mengambil format download.';

            return;
        }

        try {
            $this->selectedFormat = strtolower(trim($format));
            $this->selectedFormatDownload = $service->downloadResourceByFormat($resourceId, $this->selectedFormat);
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->selectedFormatDownload = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getPrettyDataProperty(): string
    {
        return json_encode($this->result['responseData'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public function getPrettySelectedResourceProperty(): string
    {
        return json_encode($this->selectedResource['raw'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public function render(): View
    {
        return view('livewire.search.freepik-image');
    }
}
