<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\CreditUsageService;
use Illuminate\Contracts\View\View;

class CreditUsage extends ApiFreaksComponent
{
    public function run(CreditUsageService $creditUsageService): void
    {
        try {
            $this->result = $creditUsageService->fetch();
            $this->errorMessage = null;
            $this->refreshApiKeyState();
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.api-freaks.credit-usage');
    }
}
