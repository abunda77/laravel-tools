<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\LiveCommodityPricesService;
use Illuminate\Contracts\View\View;

class LiveCommodityPrices extends ApiFreaksComponent
{
    public string $symbols = 'XAU,WTIOIL-SPOT';

    public string $updates = '1m';

    public string $quote = 'USD';

    public function run(LiveCommodityPricesService $liveCommodityPricesService): void
    {
        $this->validate([
            'symbols' => ['required', 'string', 'max:255'],
            'updates' => ['required', 'string', 'in:1m,10m'],
            'quote' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z]*$/'],
        ], [
            'quote.regex' => 'Quote hanya boleh berisi kode mata uang alfabet, contoh USD.',
        ]);

        try {
            $this->result = $liveCommodityPricesService->fetch($this->symbols, $this->updates, $this->quote);
            $this->symbols = (string) ($this->result['symbols'] ?? $this->symbols);
            $this->errorMessage = null;
            $this->refreshApiKeyState();
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.api-freaks.live-commodity-prices');
    }
}
