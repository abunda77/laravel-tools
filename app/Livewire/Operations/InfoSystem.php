<?php

namespace App\Livewire\Operations;

use App\Support\SystemInfo;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class InfoSystem extends Component
{
    public string $activeTab = 'overview';

    public bool $autoRefresh = false;

    private ?SystemInfo $systemInfo = null;

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            'overview' => 'Ringkasan',
            'os' => 'Sistem Operasi',
            'php' => 'PHP',
            'modules' => 'Modul PHP',
            'composer' => 'Composer',
            'infra' => 'Runtime & Infra',
        ];
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs())) {
            $this->activeTab = $tab;
        }
    }

    /**
     * Bersihkan cache probe perangkat keras agar data diambil ulang.
     */
    public function refresh(): void
    {
        $this->systemInfo()->flush();

        session()->now('system_info_status', 'Informasi sistem diperbarui.');
    }

    public function systemInfo(): SystemInfo
    {
        return $this->systemInfo ??= app(SystemInfo::class);
    }

    public function render(): View
    {
        return view('livewire.operations.info-system');
    }
}
