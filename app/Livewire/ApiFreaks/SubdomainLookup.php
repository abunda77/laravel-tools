<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\SubdomainLookupService;
use Illuminate\Contracts\View\View;

class SubdomainLookup extends ApiFreaksComponent
{
    public string $domain = 'stock-bill.com';

    public function run(SubdomainLookupService $subdomainLookupService): void
    {
        $this->domain = strtolower(trim($this->domain));

        $this->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            $this->result = $subdomainLookupService->fetch($this->domain);
            $this->domain = (string) ($this->result['domain'] ?? $this->domain);
            $this->errorMessage = null;
            $this->refreshApiKeyState();
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.api-freaks.subdomain-lookup');
    }
}
