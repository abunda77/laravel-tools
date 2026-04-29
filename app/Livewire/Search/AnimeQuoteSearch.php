<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\AnimeQuoteSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AnimeQuoteSearch extends Component
{
    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', AnimeQuoteSearchService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(AnimeQuoteSearchService $animeQuoteSearchService): void
    {
        try {
            $this->result = $animeQuoteSearchService->fetch();
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
        return view('livewire.search.anime-quote-search');
    }
}
