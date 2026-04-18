<?php

namespace App\Livewire\Search;

use App\Models\ApiKey;
use App\Services\Search\YoutubeChannelService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Component;

class YoutubeChannel extends Component
{
    public string $channelInput = '@Google';

    public string $searchKeyword = '';

    public bool $hasSavedApiKey = false;

    public bool $isSearchMode = false;

    public ?array $channel = null;

    /** @var array<int, array<string, mixed>> */
    public array $videos = [];

    public ?string $nextPageToken = null;

    public int $searchTotalResults = 0;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', YoutubeChannelService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(YoutubeChannelService $service): void
    {
        $this->channelInput = trim($this->channelInput);

        $this->validate(['channelInput' => ['required', 'string', 'max:255']]);

        try {
            $result = $service->fetch($this->channelInput);
            $this->resetResults();
            $this->channel = $result['channel'];
            $this->videos = $result['videos'];
            $this->nextPageToken = $result['nextPageToken'];
            $this->hasSavedApiKey = true;
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function loadMore(YoutubeChannelService $service): void
    {
        if (blank($this->nextPageToken) || $this->channel === null) {
            return;
        }

        try {
            if ($this->isSearchMode) {
                $result = $service->searchVideos(
                    $this->channel['id'],
                    $this->searchKeyword,
                    $this->nextPageToken,
                );
                $this->searchTotalResults = $result['totalResults'];
            } else {
                $uploadsPlaylistId = (string) Arr::get($this->channel, 'contentDetails.relatedPlaylists.uploads', '');
                $result = $service->getPlaylistVideos($uploadsPlaylistId, $this->nextPageToken);
            }

            $this->videos = array_merge($this->videos, $result['videos']);
            $this->nextPageToken = $result['nextPageToken'];
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function runSearch(YoutubeChannelService $service): void
    {
        $this->searchKeyword = trim($this->searchKeyword);

        $this->validate(['searchKeyword' => ['required', 'string', 'max:255']]);

        if ($this->channel === null) {
            return;
        }

        $this->videos = [];
        $this->nextPageToken = null;
        $this->isSearchMode = true;

        try {
            $result = $service->searchVideos($this->channel['id'], $this->searchKeyword);
            $this->videos = $result['videos'];
            $this->nextPageToken = $result['nextPageToken'];
            $this->searchTotalResults = $result['totalResults'];
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function clearSearch(YoutubeChannelService $service): void
    {
        $this->searchKeyword = '';
        $this->isSearchMode = false;
        $this->videos = [];
        $this->nextPageToken = null;
        $this->searchTotalResults = 0;
        $this->errorMessage = null;

        if ($this->channel === null) {
            return;
        }

        $uploadsPlaylistId = (string) Arr::get($this->channel, 'contentDetails.relatedPlaylists.uploads', '');

        if (blank($uploadsPlaylistId)) {
            return;
        }

        try {
            $result = $service->getPlaylistVideos($uploadsPlaylistId);
            $this->videos = $result['videos'];
            $this->nextPageToken = $result['nextPageToken'];
        } catch (\Throwable $throwable) {
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getChannelVideoCountProperty(): int
    {
        return (int) ($this->channel['videoCount'] ?? 0);
    }

    public function getUploadsPlaylistIdProperty(): string
    {
        return (string) Arr::get($this->channel ?? [], 'contentDetails.relatedPlaylists.uploads', '');
    }

    public function getPrettyDataProperty(): string
    {
        return json_encode($this->channel ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    private function resetResults(): void
    {
        $this->channel = null;
        $this->videos = [];
        $this->nextPageToken = null;
        $this->isSearchMode = false;
        $this->searchKeyword = '';
        $this->searchTotalResults = 0;
        $this->errorMessage = null;
    }

    public function render(): View
    {
        return view('livewire.search.youtube-channel');
    }
}
