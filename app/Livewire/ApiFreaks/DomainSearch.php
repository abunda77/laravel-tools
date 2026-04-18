<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\DomainSearchService;
use Illuminate\Contracts\View\View;

class DomainSearch extends ApiFreaksComponent
{
    public string $domain = 'apifreaks.com';

    public string $source = 'dns';

    public function run(DomainSearchService $domainSearchService): void
    {
        $this->domain = strtolower(trim($this->domain));
        $this->source = strtolower(trim($this->source));

        $this->validate([
            'domain' => ['required', 'string', 'max:253'],
            'source' => ['required', 'string', 'in:dns,whois'],
        ]);

        try {
            $this->result = $domainSearchService->fetch($this->domain, $this->source);
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
        return view('livewire.api-freaks.domain-search');
    }
}
