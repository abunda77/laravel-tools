<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\YoutubeSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class YoutubeSearch extends Component
{
    public string $query = 'cara mengecat dinding';

    public string $displayMode = 'card';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', YoutubeSearchService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function setDisplayMode(string $mode): void
    {
        if (! in_array($mode, ['card', 'table'], true)) {
            return;
        }

        $this->displayMode = $mode;
    }

    public function run(YoutubeSearchService $youtubeSearchService): void
    {
        $this->query = trim($this->query);

        $this->validate([
            'query' => ['required', 'string', 'max:255'],
            'displayMode' => ['required', 'in:card,table'],
        ]);

        try {
            $this->result = $youtubeSearchService->search($this->query);
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
        return view('livewire.search.youtube-search');
    }
}
