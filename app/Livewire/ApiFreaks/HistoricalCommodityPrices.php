<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\HistoricalCommodityPricesService;
use Illuminate\Contracts\View\View;

class HistoricalCommodityPrices extends ApiFreaksComponent
{
    public string $symbols = 'XAU,WTIOIL-SPOT';

    public string $date = '2026-04-18';

    public function run(HistoricalCommodityPricesService $historicalCommodityPricesService): void
    {
        $this->validate([
            'symbols' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        try {
            $this->result = $historicalCommodityPricesService->fetch($this->symbols, $this->date);
            $this->symbols = (string) ($this->result['symbols'] ?? $this->symbols);
            $this->date = (string) ($this->result['date'] ?? $this->date);
            $this->errorMessage = null;
            $this->refreshApiKeyState();
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.api-freaks.historical-commodity-prices');
    }
}
