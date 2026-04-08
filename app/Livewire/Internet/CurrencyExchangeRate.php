<?php

namespace App\Livewire\Internet;

use App\Models\ApiKey;
use App\Services\Internet\CurrencyExchangeRateService;
use Livewire\Component;

class CurrencyExchangeRate extends Component
{
    public string $pair = 'USDIDR';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(ApiKey::valueByName(CurrencyExchangeRateService::API_KEY_NAME));
    }

    public function run(CurrencyExchangeRateService $currencyExchangeRateService): void
    {
        $this->pair = strtoupper(trim($this->pair));

        $this->validate([
            'pair' => ['required', 'string', 'regex:/^[A-Z]{6}$/'],
        ], [
            'pair.regex' => 'Pair harus 6 huruf tanpa spasi, contoh USDIDR.',
        ]);

        try {
            $this->result = $currencyExchangeRateService->fetch($this->pair);
            $this->errorMessage = null;
            $this->hasSavedApiKey = filled(ApiKey::valueByName(CurrencyExchangeRateService::API_KEY_NAME));
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getPrettyResponseProperty(): string
    {
        return json_encode($this->result['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function render()
    {
        return view('livewire.internet.currency-exchange-rate');
    }
}
