<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\YoutubeFinderService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class YoutubeFinder extends Component
{
    public string $query = 'laravel tutorial';

    public bool $hasSavedApiKey = false;

    /** @var array<int, array<string, mixed>> */
    public array $videos = [];

    public bool $hasSearched = false;

    public ?string $nextPageToken = null;

    public int $totalResults = 0;

    public string $regionCode = '';

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', YoutubeFinderService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(YoutubeFinderService $service): void
    {
        $this->query = trim($this->query);

        $this->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        try {
            $result = $service->search($this->query);

            $this->videos = $result['videos'];
            $this->hasSearched = true;
            $this->nextPageToken = $result['nextPageToken'];
            $this->totalResults = $result['totalResults'];
            $this->regionCode = $result['regionCode'];
            $this->hasSavedApiKey = true;
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function loadMore(YoutubeFinderService $service): void
    {
        if (blank($this->nextPageToken)) {
            return;
        }

        try {
            $result = $service->search($this->query, $this->nextPageToken);

            $this->videos = array_merge($this->videos, $result['videos']);
            $this->nextPageToken = $result['nextPageToken'];
            $this->totalResults = $result['totalResults'];
            $this->regionCode = $result['regionCode'];
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getPrettyDataProperty(): string
    {
        return json_encode($this->videos, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public function render(): View
    {
        return view('livewire.search.youtube-finder');
    }
}
