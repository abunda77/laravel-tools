<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\TokopediaSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TokopediaSearch extends Component
{
    public string $query = 'itel city 100';

    public string $displayMode = 'card';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', TokopediaSearchService::API_KEY_NAME)
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

    public function run(TokopediaSearchService $tokopediaSearchService): void
    {
        $this->query = trim($this->query);

        $this->validate([
            'query' => ['required', 'string', 'max:255'],
            'displayMode' => ['required', 'in:card,table'],
        ]);

        try {
            $this->result = $tokopediaSearchService->search($this->query);
            $this->errorMessage = null;
            $this->hasSavedApiKey = true;
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getPrettyDataProperty(): string
    {
        return json_encode($this->result['responseData'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function render(): View
    {
        return view('livewire.search.tokopedia-search');
    }
}
