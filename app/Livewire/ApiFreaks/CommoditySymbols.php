<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\CommoditySymbolsService;
use Illuminate\Contracts\View\View;

class CommoditySymbols extends ApiFreaksComponent
{
    public function run(CommoditySymbolsService $commoditySymbolsService): void
    {
        try {
            $this->result = $commoditySymbolsService->fetch();
            $this->errorMessage = null;
            $this->refreshApiKeyState();
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.api-freaks.commodity-symbols');
    }
}
