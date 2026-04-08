<?php

namespace App\Livewire\Operations;

use App\Models\ApiKey;
use App\Services\ApiKeys\ApiKeyBackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ApiKeyBackupManager extends Component
{
    use WithFileUploads;

    public ?TemporaryUploadedFile $backupFile = null;

    public function createBackup(ApiKeyBackupService $backups): void
    {
        $filename = $backups->create();

        session()->flash('api_key_backup_status', "Backup [{$filename}] berhasil dibuat.");
    }

    public function downloadBackup(string $filename, ApiKeyBackupService $backups): StreamedResponse
    {
        return $backups->download($filename);
    }

    public function restoreBackup(ApiKeyBackupService $backups): void
    {
        $this->validate([
            'backupFile' => ['required', 'file', 'max:512'],
        ], [
            'backupFile.required' => 'Pilih file backup API key terlebih dahulu.',
            'backupFile.max' => 'Ukuran file backup maksimal 512 KB.',
        ]);

        try {
            $restored = $backups->restoreFromUploadedFile($this->backupFile);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'backupFile' => $exception->getMessage(),
            ]);
        }

        $this->backupFile = null;

        session()->flash('api_key_backup_status', "{$restored} API key berhasil direstore dari file backup.");
    }

    public function render(ApiKeyBackupService $backups): View
    {
        return view('livewire.operations.api-key-backup-manager', [
            'apiKeyCount' => ApiKey::query()->count(),
            'backupFiles' => $backups->backups(),
        ]);
    }
}
