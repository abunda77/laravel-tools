<?php

use App\Services\QrCodeTemporaryFileService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Validate(['required', 'string', 'max:5000'])]
    public string $content = '';

    public string $pngFilename = '';

    public string $jpgFilename = '';

    public string $previewDataUri = '';

    public string $generateError = '';

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return [
            'content' => 'Teks QR Code',
        ];
    }

    public function generate(QrCodeTemporaryFileService $temporaryFileService): void
    {
        $this->validate();

        $this->generateError = '';

        $this->clearTemporaryFiles($temporaryFileService);

        try {
            $result = $temporaryFileService->generate($this->content);
        } catch (\Throwable $exception) {
            $this->generateError = 'Gagal membuat QR Code: '.$exception->getMessage();

            return;
        }

        $this->pngFilename = $result['png_filename'];
        $this->jpgFilename = $result['jpg_filename'];
        $this->previewDataUri = $result['preview_data_uri'];
    }

    public function clearTemporaryFiles(QrCodeTemporaryFileService $temporaryFileService): void
    {
        $hasFiles = $this->pngFilename !== '' || $this->jpgFilename !== '';

        $temporaryFileService->deleteMany([$this->pngFilename, $this->jpgFilename]);

        $this->pngFilename = '';
        $this->jpgFilename = '';
        $this->previewDataUri = '';
        $this->generateError = '';

        if ($hasFiles) {
            $this->js('window.dispatchEvent(new CustomEvent("qr-cleared"))');
        }
    }
}; ?>

<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Tools module</p>
            <h3>QR Code Generator</h3>
            <p>
                Masukkan teks lalu generate QR Code dalam format PNG dan JPG. Preview tampil langsung dan file dapat diunduh sementara.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Format</span>
                <strong>PNG / JPG</strong>
            </div>
            <div class="mini-stat">
                <span>Ukuran</span>
                <strong>{{ \App\Services\QrCodeTemporaryFileService::Size }}px</strong>
            </div>
            <div class="mini-stat">
                <span>Kedaluwarsa</span>
                <strong>{{ \App\Services\QrCodeTemporaryFileService::ExpiryHours }} jam</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Generate QR Code</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Isi teks yang akan dijadikan QR Code, lalu tekan tombol generate untuk membuat file temporary.
                    </p>
                </div>
            </div>

            @if ($generateError)
                <div class="form-alert form-alert--danger">
                    {{ $generateError }}
                </div>
            @endif

            <form wire:submit="generate" class="settings-form">
                <div class="form-field md:col-span-2">
                    <label for="qr_content" class="form-label">Teks QR Code</label>
                    <textarea
                        id="qr_content"
                        wire:model="content"
                        rows="6"
                        class="form-input"
                        placeholder="https://laravel.com"
                        maxlength="5000"
                    ></textarea>
                    <p class="form-help">Maksimal 5000 karakter. Cocok untuk URL, teks, atau kontak.</p>
                    @error('content') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        File disimpan sementara dan dibersihkan otomatis setelah {{ \App\Services\QrCodeTemporaryFileService::ExpiryHours }} jam.
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="generate">Generate QR Code</span>
                        <span wire:loading wire:target="generate">Membuat...</span>
                    </button>
                </div>
            </form>

            @if ($previewDataUri && $pngFilename && $jpgFilename)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Hasil generate</p>
                            <h4>QR Code siap diunduh</h4>
                            <p>Preview ditampilkan langsung tanpa file publik.</p>
                        </div>

                        <button
                            type="button"
                            wire:click="clearTemporaryFiles"
                            class="app-sidebar__logout w-auto px-4 py-2 text-xs"
                        >
                            Hapus Temporary
                        </button>
                    </div>

                    <div class="flex flex-col items-start gap-5">
                        <img
                            src="{{ $previewDataUri }}"
                            alt="Preview QR Code"
                            class="h-56 w-56 rounded-3xl border border-[rgba(var(--app-border),0.8)] bg-white object-contain p-3"
                        />

                        <div class="grid w-full gap-3 sm:grid-cols-2">
                            <article class="rounded-[1.5rem] border border-[rgba(var(--app-border),0.8)] bg-white/80 p-4">
                                <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-[rgb(var(--app-muted))]">File PNG</span>
                                <strong class="mt-1 block truncate font-mono text-sm text-[rgb(var(--app-ink))]">{{ $pngFilename }}</strong>
                                <a
                                    href="{{ route('qr-code.download', ['filename' => $pngFilename]) }}"
                                    class="primary-action mt-3 inline-flex"
                                >
                                    Download PNG
                                </a>
                            </article>

                            <article class="rounded-[1.5rem] border border-[rgba(var(--app-border),0.8)] bg-white/80 p-4">
                                <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-[rgb(var(--app-muted))]">File JPG</span>
                                <strong class="mt-1 block truncate font-mono text-sm text-[rgb(var(--app-ink))]">{{ $jpgFilename }}</strong>
                                <a
                                    href="{{ route('qr-code.download', ['filename' => $jpgFilename]) }}"
                                    class="primary-action mt-3 inline-flex"
                                >
                                    Download JPG
                                </a>
                            </article>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Dokumentasi</p>
                        <h3>Cara kerja</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>Input teks</strong>
                        <p>Isi teks maksimal 5000 karakter, wajib diisi sebelum generate.</p>
                    </article>
                    <article>
                        <strong>Generate</strong>
                        <p>Service membuat file PNG dan JPG unik berbasis UUID ke storage lokal.</p>
                    </article>
                    <article>
                        <strong>Preview & download</strong>
                        <p>Preview tampil langsung dan file dapat diunduh lewat route download.</p>
                    </article>
                    <article>
                        <strong>Pembersihan</strong>
                        <p>File lama dibersihkan otomatis saat generate dan dapat dihapus manual.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
