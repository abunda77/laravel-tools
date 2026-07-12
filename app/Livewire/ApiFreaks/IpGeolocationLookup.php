<?php

namespace App\Livewire\ApiFreaks;

use App\Services\ApiFreaks\IpGeolocationService;
use Illuminate\Contracts\View\View;

class IpGeolocationLookup extends ApiFreaksComponent
{
    public string $ip = '8.8.8.8';

    public string $lang = 'en';

    public string $fields = 'location';

    public string $include = 'security,hostnameFallbackLive';

    public function run(IpGeolocationService $service): void
    {
        $this->validate([
            'ip' => ['required', 'string', 'max:45'],
            'lang' => ['required', 'string', 'in:en,id', 'size:2'],
            'fields' => ['nullable', 'string', 'max:255'],
            'include' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->result = $service->lookup(
                $this->ip,
                $this->lang,
                $this->fields,
                $this->include,
            );
            $this->ip = (string) ($this->result['ip'] ?? $this->ip);
            $this->errorMessage = null;
            $this->refreshApiKeyState();
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function render(): View
    {
        return view('livewire.api-freaks.ip-geolocation-lookup');
    }
}
