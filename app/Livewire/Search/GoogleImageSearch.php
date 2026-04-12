<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\GoogleImageSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GoogleImageSearch extends Component
{
    public string $query = 'burung perkutut';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', GoogleImageSearchService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(GoogleImageSearchService $googleImageSearchService): void
    {
        $this->query = trim($this->query);

        $this->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->result = $googleImageSearchService->search($this->query);
            $this->errorMessage = null;
            $this->hasSavedApiKey = true;
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getPrettyDataProperty(): string
    {
        return json_encode($this->result['responseData'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public function render(): View
    {
        return view('livewire.search.google-image-search');
    }
}
