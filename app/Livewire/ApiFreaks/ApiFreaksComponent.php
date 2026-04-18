<?php

namespace App\Livewire\ApiFreaks;

use App\Models\ApiKey;
use App\Services\ApiFreaks\ApiFreaksService;
use Livewire\Component;

abstract class ApiFreaksComponent extends Component
{
    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->refreshApiKeyState();
    }

    public function getPrettyResponseProperty(): string
    {
        return json_encode($this->result['response'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }

    protected function refreshApiKeyState(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', ApiFreaksService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }
}
