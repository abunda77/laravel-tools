<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\DomainWhoisHistoryLookupService;
use Illuminate\Contracts\View\View;

class DomainWhoisHistoryLookup extends ApiFreaksComponent
{
    public string $domain = 'apifreaks.com';

    public function run(DomainWhoisHistoryLookupService $domainWhoisHistoryLookupService): void
    {
        $this->domain = strtolower(trim($this->domain));

        $this->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            $this->result = $domainWhoisHistoryLookupService->fetch($this->domain);
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
        return view('livewire.api-freaks.domain-whois-history-lookup');
    }
}
