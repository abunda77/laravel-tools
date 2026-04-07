<div class="settings-stack">

    {{-- ─── Hero / Stats ───────────────────────────────────────────────── --}}
    <section class="settings-hero">
        <article class="settings-hero__intro">
            <p class="section-kicker">API Key Management</p>
            <h3>Koleksi API key untuk layanan eksternal.</h3>
            <p>
                Simpan dan kelola berbagai API key dari berbagai layanan di satu tempat.
                Setiap service akan mengambil API key berdasarkan <code>name</code>-nya masing-masing.
                Semua nilai disimpan terenkripsi di database.
            </p>
        </article>

        <div class="settings-hero__stats">
            <article class="mini-stat">
                <span>Total Keys</span>
                <strong>{{ $this->apiKeys->count() }}</strong>
            </article>

            <article class="mini-stat">
                <span>Active</span>
                <strong>{{ $this->apiKeys->where('is_active', true)->count() }}</strong>
            </article>

            <article class="mini-stat">
                <span>Has Value</span>
                <strong>{{ $this->apiKeys->filter(fn ($k) => filled($k->value))->count() }}</strong>
            </article>
        </div>
    </section>

    @if (session('api_key_status'))
        <div class="form-alert form-alert--success">
            {{ session('api_key_status') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_22rem]">

        {{-- ─── Main Panel ─────────────────────────────────────────────── --}}
        <div class="space-y-6">

            {{-- Add/Edit Form --}}
            @if ($showForm)
                <section class="surface-panel">
                    <div class="surface-panel__header">
                        <div>
                            <h3>{{ $editingId ? 'Edit API Key' : 'Tambah API Key Baru' }}</h3>
                            <p class="surface-panel__text surface-panel__text--tight">
                                {{ $editingId ? 'Perbarui detail API key yang dipilih.' : 'Daftarkan layanan baru dan simpan API key-nya.' }}
                            </p>
                        </div>
                    </div>

                    <form wire:submit="save" class="settings-form">
                        <div class="form-grid">

                            {{-- Name (only on create) --}}
                            @if (! $editingId)
                                <div class="form-field form-field--wide">
                                    <label for="api_key_name" class="form-label">Identifier (name)</label>
                                    <input
                                        id="api_key_name"
                                        type="text"
                                        wire:model="name"
                                        class="form-input font-mono"
                                        placeholder="downloader_provider"
                                        autocomplete="off"
                                        spellcheck="false"
                                    />
                                    <p class="form-help">
                                        Huruf kecil, angka, underscore. Digunakan kode untuk mengambil key ini, e.g. <code>downloader_provider</code>.
                                    </p>
                                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            @endif

                            {{-- Label --}}
                            <div class="form-field form-field--wide">
                                <label for="api_key_label" class="form-label">Label</label>
                                <input
                                    id="api_key_label"
                                    type="text"
                                    wire:model="label"
                                    class="form-input"
                                    placeholder="Downloader Provider"
                                    autocomplete="off"
                                />
                                <p class="form-help">Nama tampilan untuk API key ini.</p>
                                @error('label') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Description --}}
                            <div class="form-field form-field--wide">
                                <label for="api_key_description" class="form-label">Deskripsi <span class="text-xs opacity-60">(opsional)</span></label>
                                <textarea
                                    id="api_key_description"
                                    wire:model="description"
                                    class="form-input"
                                    rows="2"
                                    placeholder="Digunakan oleh DownloaderService untuk Instagram, TikTok, Facebook..."
                                ></textarea>
                                @error('description') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Value --}}
                            <div class="form-field form-field--wide">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <label for="api_key_value" class="form-label">API Key Value</label>
                                        <p class="form-help">
                                            @if ($editingId && $hasValue)
                                                Key sudah tersimpan. Isi hanya jika ingin menggantinya.
                                            @else
                                                Nilai API key dari layanan tersebut.
                                            @endif
                                        </p>
                                    </div>
                                    @if ($editingId)
                                        <span class="status-pill {{ $hasValue ? 'status-pill--ready' : 'status-pill--pending' }}">
                                            {{ $hasValue ? 'Stored' : 'Empty' }}
                                        </span>
                                    @endif
                                </div>
                                <input
                                    id="api_key_value"
                                    type="password"
                                    wire:model="value"
                                    class="form-input font-mono"
                                    placeholder="{{ $editingId && $hasValue ? '••••••••  (biarkan kosong untuk tidak mengganti)' : 'Masukkan API key' }}"
                                    autocomplete="new-password"
                                />
                                @error('value') <p class="form-error">{{ $message }}</p> @enderror
                            </div>

                            {{-- Active status --}}
                            <div class="form-field">
                                <label class="form-label">Status</label>
                                <label class="checkbox-row">
                                    <input type="checkbox" wire:model="isActive" />
                                    <span>Aktif</span>
                                </label>
                                <p class="form-help">Nonaktifkan untuk menonaktifkan key ini tanpa menghapusnya.</p>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="primary-action" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Simpan' }}</span>
                                <span wire:loading wire:target="save">Menyimpan...</span>
                            </button>
                            <button type="button" class="secondary-action" wire:click="closeForm">
                                Batal
                            </button>
                        </div>
                    </form>
                </section>
            @endif

            {{-- ─── API Keys Table ──────────────────────────────────────── --}}
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <h3>Daftar API Key</h3>
                        <p class="surface-panel__text surface-panel__text--tight">
                            Semua nilai tersimpan terenkripsi. Klik edit untuk memperbarui.
                        </p>
                    </div>
                    <button class="primary-action" wire:click="openAdd">
                        + Tambah
                    </button>
                </div>

                @if ($this->apiKeys->isEmpty())
                    <div class="empty-state">
                        <p>Belum ada API key. Klik <strong>+ Tambah</strong> untuk mendaftarkan layanan pertama.</p>
                    </div>
                @else
                    <div class="api-key-list">
                        @foreach ($this->apiKeys as $apiKey)
                            <article class="api-key-item {{ $apiKey->is_active ? '' : 'api-key-item--inactive' }}">
                                <div class="api-key-item__info">
                                    <div class="api-key-item__header">
                                        <strong class="api-key-item__label">{{ $apiKey->label }}</strong>
                                        <code class="api-key-item__name">{{ $apiKey->name }}</code>
                                    </div>
                                    @if ($apiKey->description)
                                        <p class="api-key-item__desc">{{ $apiKey->description }}</p>
                                    @endif
                                    <div class="api-key-item__meta">
                                        <span class="status-pill {{ filled($apiKey->value) ? 'status-pill--ready' : 'status-pill--pending' }}">
                                            {{ filled($apiKey->value) ? 'Has Key' : 'No Key' }}
                                        </span>
                                        <span class="status-pill {{ $apiKey->is_active ? 'status-pill--ready' : 'status-pill--warning' }}">
                                            {{ $apiKey->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="api-key-item__actions">
                                    <button
                                        class="icon-action icon-action--edit"
                                        wire:click="openEdit({{ $apiKey->id }})"
                                        title="Edit"
                                    >Edit</button>

                                    <button
                                        class="icon-action {{ $apiKey->is_active ? 'icon-action--muted' : 'icon-action--success' }}"
                                        wire:click="toggleActive({{ $apiKey->id }})"
                                        title="{{ $apiKey->is_active ? 'Nonaktifkan' : 'Aktifkan' }}"
                                    >{{ $apiKey->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>

                                    @if (filled($apiKey->value))
                                        <button
                                            class="icon-action icon-action--warning"
                                            wire:click="clearValue({{ $apiKey->id }})"
                                            wire:confirm="Hapus nilai API key [{{ $apiKey->label }}]?"
                                            title="Hapus value"
                                        >Clear</button>
                                    @endif

                                    <button
                                        class="icon-action icon-action--danger"
                                        wire:click="delete({{ $apiKey->id }})"
                                        wire:confirm="Hapus API key [{{ $apiKey->label }}] secara permanen?"
                                        title="Hapus"
                                    >Hapus</button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        {{-- ─── Sidebar ─────────────────────────────────────────────────── --}}
        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Panduan</p>
                        <h3>Cara kerja API Key</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Identifier unik (name)</h4>
                        <p>
                            Setiap key memiliki <code>name</code> seperti <code>downloader_provider</code> atau <code>openai</code>.
                            Service mengambil key dengan <code>ApiKey::valueByName('name')</code>.
                        </p>
                    </article>

                    <article>
                        <h4>Hanya value, bukan base URL</h4>
                        <p>
                            Base URL dikelola langsung di masing-masing service class.
                            Di sini hanya disimpan API key value-nya saja.
                        </p>
                    </article>

                    <article>
                        <h4>Enkripsi otomatis</h4>
                        <p>
                            Semua nilai dienkripsi dengan <code>Crypt::encryptString()</code> sebelum disimpan ke database dan didekripsi saat dibaca.
                        </p>
                    </article>

                    <article>
                        <h4>Nonaktifkan tanpa hapus</h4>
                        <p>
                            Toggle status Aktif/Nonaktif memungkinkan menonaktifkan key sementara tanpa perlu menghapus dan memasukkan ulang.
                        </p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Contoh penggunaan</p>
                        <h3>Di service class</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>DownloaderService</strong>
                        <p>Gunakan <code>name: downloader_provider</code></p>
                    </article>
                    <article>
                        <strong>OpenAI Service</strong>
                        <p>Gunakan <code>name: openai</code></p>
                    </article>
                    <article>
                        <strong>Gemini Service</strong>
                        <p>Gunakan <code>name: gemini</code></p>
                    </article>
                    <article>
                        <strong>Freepik Generation Service</strong>
                        <p>Gunakan <code>name: freepik_provider</code></p>
                    </article>
                    <article>
                        <strong>Custom service lain</strong>
                        <p>Bebas mendefinisikan <code>name</code> sesuai kebutuhan.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>

    <style>
/* ── API Key List ─────────────────────────────────────────── */
.api-key-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1.25rem;
}

.api-key-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: var(--surface-2, rgba(255,255,255,0.04));
    border: 1px solid var(--border-subtle, rgba(255,255,255,0.08));
    border-radius: 0.625rem;
    transition: opacity 0.2s;
}

.api-key-item--inactive {
    opacity: 0.5;
}

.api-key-item__info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.api-key-item__header {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    flex-wrap: wrap;
}

.api-key-item__label {
    font-size: 0.9375rem;
    font-weight: 600;
}

.api-key-item__name {
    font-size: 0.75rem;
    padding: 0.15rem 0.45rem;
    border-radius: 0.3rem;
    background: var(--surface-3, rgba(255,255,255,0.07));
    opacity: 0.75;
}

.api-key-item__desc {
    font-size: 0.8125rem;
    opacity: 0.65;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.api-key-item__meta {
    display: flex;
    gap: 0.4rem;
    flex-wrap: wrap;
}

.api-key-item__actions {
    display: flex;
    gap: 0.4rem;
    flex-shrink: 0;
    flex-wrap: wrap;
    align-self: center;
}

/* ── Icon Actions ─────────────────────────────────────────── */
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

.icon-action--success {
    background: rgba(34, 197, 94, 0.12);
    color: rgb(74, 222, 128);
    border-color: rgba(34, 197, 94, 0.25);
}
.icon-action--success:hover { background: rgba(34, 197, 94, 0.22); }

.icon-action--muted {
    background: rgba(148, 163, 184, 0.08);
    color: rgb(148, 163, 184);
    border-color: rgba(148, 163, 184, 0.2);
}
.icon-action--muted:hover { background: rgba(148, 163, 184, 0.15); }

.icon-action--warning {
    background: rgba(234, 179, 8, 0.10);
    color: rgb(234, 179, 8);
    border-color: rgba(234, 179, 8, 0.25);
}
.icon-action--warning:hover { background: rgba(234, 179, 8, 0.2); }

.icon-action--danger {
    background: rgba(239, 68, 68, 0.10);
    color: rgb(239, 68, 68);
    border-color: rgba(239, 68, 68, 0.25);
}
.icon-action--danger:hover { background: rgba(239, 68, 68, 0.2); }

/* status-pill extra state */
.status-pill--warning {
    background: rgba(234, 179, 8, 0.15);
    color: rgb(234, 179, 8);
}

/* empty state */
.empty-state {
    padding: 2.5rem 1.5rem;
    text-align: center;
    opacity: 0.6;
    font-size: 0.9rem;
}

/* secondary action button */
.secondary-action {
    padding: 0.55rem 1.25rem;
    border-radius: 0.5rem;
    border: 1px solid var(--border-subtle, rgba(255,255,255,0.15));
    background: transparent;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.15s;
}
.secondary-action:hover { background: rgba(255,255,255,0.06); }

/* font-mono helper */
.font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    </style>
</div>
