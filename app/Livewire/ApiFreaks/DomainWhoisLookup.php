<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\DomainWhoisLookupService;
use Illuminate\Contracts\View\View;

class DomainWhoisLookup extends ApiFreaksComponent
{
    public string $domain = 'apifreaks.com';

    public function run(DomainWhoisLookupService $domainWhoisLookupService): void
    {
        $this->domain = strtolower(trim($this->domain));

        $this->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            $this->result = $domainWhoisLookupService->fetch($this->domain);
            $this->domain = (string) ($this->result['summary']['domain_name'] ?? $this->domain);
            $this->errorMessage = null;
            $this->refreshApiKeyState();
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.api-freaks.domain-whois-lookup');
    }
}
