<?php

namespace App\Livewire\Internet;

use App\Models\ApiKey;
use App\Services\Internet\GoldPriceService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GoldPrice extends Component
{
    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->refreshApiKeyState();
    }

    public function run(GoldPriceService $goldPriceService): void
    {
        try {
            $this->result = $goldPriceService->history();
            $this->errorMessage = null;
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }

        $this->refreshApiKeyState();
    }

    private function refreshApiKeyState(): void
    {
        $this->hasSavedApiKey = filled(ApiKey::valueByName(GoldPriceService::API_KEY_NAME));
    }

    public function render(): View
    {
        return view('livewire.internet.gold-price');
    }
}
