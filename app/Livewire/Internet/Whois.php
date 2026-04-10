<?php

namespace App\Livewire\Internet;

use App\Models\ApiKey;
use App\Services\Internet\WhoisService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Whois extends Component
{
    public string $domain = 'produkmastah.com';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', WhoisService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(WhoisService $whoisService): void
    {
        $this->domain = strtolower(trim($this->domain));

        $this->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        try {
            $this->result = $whoisService->lookup($this->domain);
            $this->domain = $this->result['domain'];
            $this->errorMessage = null;
            $this->hasSavedApiKey = true;
        } catch (\Throwable $throwable) {
            $this->result = null;
            $this->errorMessage = $throwable->getMessage();
        }
    }

    public function getPrettyDataProperty(): string
    {
        return json_encode($this->result['responseData'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    public function render(): View
    {
        return view('livewire.internet.whois');
    }
}
