<?php

namespace App\Console\Commands;

use App\Services\QrCodeTemporaryFileService;
use Illuminate\Console\Command;

class CleanupLivewireTemporaryUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:temporary-uploads';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bersihkan temporary upload Livewire yang kedaluwarsa beserta file QR Code temporary.';

    /**
     * Execute the console command.
     */
    public function handle(QrCodeTemporaryFileService $qrCodeTemporaryFileService): int
    {
        $qrDeletedCount = $qrCodeTemporaryFileService->cleanupExpiredFiles();

        $this->info('Pembersihan selesai.');

        if ($qrDeletedCount > 0) {
            $this->info("File QR Code temporary dihapus: {$qrDeletedCount}");
        } else {
            $this->comment('Tidak ada file QR Code temporary yang kedaluwarsa.');
        }

        return self::SUCCESS;
    }
}
