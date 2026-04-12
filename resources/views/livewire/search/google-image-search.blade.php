<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Search module</p>
            <h3>Google Image Search</h3>
            <p>
                Cari gambar Google lewat endpoint <code>/search/gimage</code>. API key otomatis memakai
                <code>downloader_provider</code> dari Settings.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Endpoint</span>
                <strong>/search/gimage</strong>
            </div>
            <div class="mini-stat">
                <span>Method</span>
                <strong>GET</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Cari gambar Google</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan keyword pencarian, lalu hasil akan ditampilkan sebagai kartu gambar, tabel sumber, dan raw JSON.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label for="gimage_query" class="form-label">Query</label>
                                <p class="form-help">Contoh: <code>burung perkutut</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>

                        <input
                            id="gimage_query"
                            type="text"
                            wire:model="query"
                            class="form-input"
                            placeholder="burung perkutut"
                            autocomplete="off"
                        />
                        @error('query') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        API key: <code>downloader_provider</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cari gambar</span>
                        <span wire:loading wire:target="run">Mencari...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Response summary</p>
                            <h4>{{ $result['query'] }}</h4>
                            <p>{{ $result['total'] }} gambar ditemukan{{ $result['author'] ? ' · Author: '.$result['author'] : '' }}.</p>
                        </div>
                        <span class="status-pill status-pill--ready">{{ $result['total'] }} item</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @forelse ($result['images'] as $image)
                            <article wire:key="gimage-card-{{ md5($image['url'].$image['image']) }}" class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.55)]">
                                <div class="aspect-[4/3] overflow-hidden bg-white/70">
                                    @if ($image['image'])
                                        <img src="{{ $image['image'] }}" alt="{{ $image['title'] }}" class="h-full w-full object-cover" />
                                    @else
                                        <div class="flex h-full items-center justify-center text-sm text-[rgb(var(--app-muted))]">
                                            Gambar tidak tersedia
                                        </div>
                                    @endif
                                </div>
                                <div class="space-y-3 p-5">
                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[rgb(var(--app-muted))]">Google image result</p>
                                        <h4 class="text-base font-bold leading-6 text-[rgb(var(--app-ink))]">{{ $image['title'] ?: '-' }}</h4>
                                    </div>
                                    <div class="grid gap-3 rounded-[1.1rem] border border-[rgb(var(--app-line))] bg-white/70 p-4 text-sm text-[rgb(var(--app-muted))]">
                                        <div>
                                            <span class="block">Source URL</span>
                                            <strong class="mt-1 block break-all font-mono text-xs text-[rgb(var(--app-ink))]">{{ $image['url'] ?: '-' }}</strong>
                                        </div>
                                        <div>
                                            <span class="block">Image URL</span>
                                            <strong class="mt-1 block break-all font-mono text-xs text-[rgb(var(--app-ink))]">{{ $image['image'] ?: '-' }}</strong>
                                        </div>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <a href="{{ $image['url'] }}" target="_blank" rel="noopener noreferrer" class="primary-action w-full justify-center">
                                            Buka sumber
                                        </a>
                                        <a href="{{ $image['image'] }}" target="_blank" rel="noopener noreferrer" class="primary-action w-full justify-center">
                                            Buka gambar
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="form-alert form-alert--danger md:col-span-2">
                                Tidak ada gambar yang dikembalikan oleh provider.
                            </div>
                        @endforelse
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Title</th>
                                        <th class="px-4 py-3 font-semibold">Source URL</th>
                                        <th class="px-4 py-3 font-semibold">Image URL</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['images'] as $image)
                                        <tr wire:key="gimage-row-{{ md5($image['url'].$image['image']) }}">
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $image['title'] ?: '-' }}</td>
                                            <td class="px-4 py-4">
                                                <a href="{{ $image['url'] }}" target="_blank" rel="noopener noreferrer" class="min-w-[18rem] break-all font-mono text-xs leading-6 text-[rgb(var(--app-accent))] hover:underline">
                                                    {{ $image['url'] ?: '-' }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-4">
                                                <a href="{{ $image['image'] }}" target="_blank" rel="noopener noreferrer" class="min-w-[18rem] break-all font-mono text-xs leading-6 text-[rgb(var(--app-accent))] hover:underline">
                                                    {{ $image['image'] ?: '-' }}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">
                                                Tidak ada gambar yang dikembalikan oleh provider.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Data JSON</span>
                            <strong>{{ $result['total'] }} item</strong>
                        </div>
                        <pre>{{ $this->prettyData }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Parameter</p>
                        <h3>Request</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>query</strong>
                        <p>Keyword pencarian Google Image, contoh <code>burung perkutut</code>.</p>
                    </article>
                    <article>
                        <strong>apikey</strong>
                        <p>Diambil dari <code>downloader_provider</code>.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Response</p>
                        <h3>Data yang ditampilkan</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Preview gambar</h4>
                        <p>Setiap hasil menampilkan thumbnail gambar, judul, tautan sumber, dan tautan image langsung.</p>
                    </article>
                    <article>
                        <h4>Tabel URL</h4>
                        <p>Semua source URL dan image URL juga dirender dalam tabel agar mudah discan saat hasilnya banyak.</p>
                    </article>
                    <article>
                        <h4>Raw JSON</h4>
                        <p>Isi array <code>result</code> tetap ditampilkan untuk verifikasi payload provider.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
