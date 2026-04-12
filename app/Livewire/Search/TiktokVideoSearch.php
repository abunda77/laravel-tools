<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\TiktokVideoSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class TiktokVideoSearch extends Component
{
    public string $query = 'pargoy';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', TiktokVideoSearchService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(TiktokVideoSearchService $tiktokVideoSearchService): void
    {
        $this->query = trim($this->query);

        $this->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->result = $tiktokVideoSearchService->search($this->query);
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
        return view('livewire.search.tiktok-video-search');
    }
}
