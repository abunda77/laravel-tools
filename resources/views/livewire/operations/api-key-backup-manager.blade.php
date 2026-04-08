<div class="settings-stack">
    <section class="settings-hero">
        <article class="settings-hero__intro">
            <p class="section-kicker">Backup Data ApiKey</p>
            <h3>Backup dan restore koleksi API key.</h3>
            <p>
                Buat file backup JSON untuk semua API key yang tersimpan, download file backup, atau restore kembali dari file backup.
                Simpan file backup dengan aman karena file berisi value API key asli.
            </p>
        </article>

        <div class="settings-hero__stats">
            <article class="mini-stat">
                <span>API Keys</span>
                <strong>{{ $apiKeyCount }}</strong>
            </article>

            <article class="mini-stat">
                <span>Backup Files</span>
                <strong>{{ count($backupFiles) }}</strong>
            </article>
        </div>
    </section>

    @if (session('api_key_backup_status'))
        <div class="form-alert form-alert--success">
            {{ session('api_key_backup_status') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_22rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <p class="section-kicker">Backup table</p>
                    <h3>Daftar File Backup</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Klik Backup untuk membuat snapshot terbaru, lalu Download file yang dibutuhkan.
                    </p>
                </div>

                <button class="primary-action" wire:click="createBackup" wire:loading.attr="disabled" wire:target="createBackup">
                    <span wire:loading.remove wire:target="createBackup">Backup</span>
                    <span wire:loading wire:target="createBackup">Memproses...</span>
                </button>
            </div>

            @if (empty($backupFiles))
                <div class="empty-state">
                    <p>Belum ada file backup. Klik <strong>Backup</strong> untuk membuat backup pertama.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="backup-table">
                        <thead>
                            <tr>
                                <th>File</th>
                                <th>Dibuat</th>
                                <th>Ukuran</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($backupFiles as $backupFile)
                                <tr wire:key="api-key-backup-{{ $backupFile['filename'] }}">
                                    <td>
                                        <code>{{ $backupFile['filename'] }}</code>
                                    </td>
                                    <td>{{ $backupFile['last_modified']->format('d M Y H:i') }}</td>
                                    <td>{{ number_format($backupFile['size'] / 1024, 2) }} KB</td>
                                    <td class="text-right">
                                        <button
                                            type="button"
                                            class="icon-action icon-action--edit"
                                            wire:click="downloadBackup('{{ $backupFile['filename'] }}')"
                                        >
                                            Download
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Restore</p>
                        <h3>Restore Apikey</h3>
                    </div>
                </div>

                <form wire:submit="restoreBackup" class="settings-form">
                    <div class="form-field form-field--wide">
                        <label for="api_key_backup_file" class="form-label">File backup JSON</label>
                        <input
                            id="api_key_backup_file"
                            type="file"
                            wire:model="backupFile"
                            class="form-input"
                            accept=".json,application/json"
                        />
                        <p class="form-help">
                            Restore akan membuat atau memperbarui API key berdasarkan <code>name</code> dari file backup.
                        </p>
                        @error('backupFile') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <button class="primary-action w-full justify-center" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="restoreBackup,backupFile">Restore</span>
                        <span wire:loading wire:target="restoreBackup,backupFile">Memproses...</span>
                    </button>
                </form>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Catatan keamanan</p>
                        <h3>File berisi secret</h3>
                    </div>
                </div>

                <p class="surface-panel__text">
                    File backup disimpan pada disk lokal private dan value API key hanya ditulis ke file backup agar dapat direstore. Jangan upload file ini ke repository.
                </p>
            </section>
        </aside>
    </div>

    <style>
.backup-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 42rem;
}

.backup-table th,
.backup-table td {
    padding: 0.85rem 1rem;
    border-bottom: 1px solid var(--border-subtle, rgba(255,255,255,0.08));
    text-align: left;
    font-size: 0.875rem;
}

.backup-table th {
    color: rgb(var(--app-muted));
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.backup-table code {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.8125rem;
}

.empty-state {
    padding: 2.5rem 1.5rem;
    text-align: center;
    opacity: 0.6;
    font-size: 0.9rem;
}

.icon-action {
    padding: 0.3rem 0.65rem;
    font-size: 0.75rem;
    border-radius: 0.35rem;
    border: 1px solid transparent;
    cursor: pointer;
    transition: background 0.15s, opacity 0.15s;
    font-weight: 500;
    white-space: nowrap;
}

.icon-action--edit {
    background: rgba(99, 102, 241, 0.12);
    color: rgb(165, 163, 255);
    border-color: rgba(99, 102, 241, 0.25);
}

.icon-action--edit:hover { background: rgba(99, 102, 241, 0.22); }
    </style>
</div>
