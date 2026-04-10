<?php

namespace App\Livewire\Tools;

use App\Models\ApiKey;
use App\Services\Tools\CekResiService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CekResi extends Component
{
    public string $resi = '';

    public string $ekspedisi = 'shopee-express';

    public bool $hasSavedApiKey = false;

    public ?array $result = null;

    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->hasSavedApiKey = filled(
            ApiKey::query()
                ->active()
                ->where('name', CekResiService::API_KEY_NAME)
                ->first()
                ?->value,
        );
    }

    public function run(CekResiService $cekResiService): void
    {
        $this->resi = strtoupper(trim($this->resi));
        $this->ekspedisi = strtolower(trim($this->ekspedisi));

        $this->validate([
            'resi' => ['required', 'string', 'max:128'],
            'ekspedisi' => ['required', 'string', 'regex:/^[a-z0-9-]+$/', 'max:80'],
        ], [
            'ekspedisi.regex' => 'Ekspedisi hanya boleh berisi huruf, angka, dan tanda hubung. Contoh: shopee-express.',
        ]);

        try {
            $this->result = $cekResiService->track($this->resi, $this->ekspedisi);
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
        return view('livewire.tools.cek-resi');
    }
}
