<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Search module</p>
            <h3>TikTok Video Search</h3>
            <p>
                Cari konten video TikTok lewat endpoint <code>/search/tiktok</code>. API key otomatis memakai
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
                <strong>/search/tiktok</strong>
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
                    <h3>Cari video TikTok</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan keyword pencarian, lalu hasil akan ditampilkan sebagai preview video dan daftar URL.
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
                                <label for="tiktok_query" class="form-label">Query</label>
                                <p class="form-help">Contoh: <code>pargoy</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>

                        <input
                            id="tiktok_query"
                            type="text"
                            wire:model="query"
                            class="form-input"
                            placeholder="pargoy"
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
                        <span wire:loading.remove wire:target="run">Cari video</span>
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
                            <p>{{ $result['total'] }} video ditemukan{{ $result['author'] ? ' · Author: '.$result['author'] : '' }}.</p>
                        </div>
                        <span class="status-pill status-pill--ready">{{ $result['total'] }} video</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @forelse ($result['videos'] as $video)
                            <article wire:key="tiktok-video-card-{{ $video['index'] }}" class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.55)]">
                                <video controls preload="metadata" class="aspect-[9/16] w-full bg-black" src="{{ $video['url'] }}"></video>
                                <div class="space-y-3 p-5">
                                    <div class="space-y-2">
                                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-[rgb(var(--app-muted))]">Video {{ $video['index'] }}</p>
                                        <h4 class="break-all text-sm font-bold leading-6 text-[rgb(var(--app-ink))]">{{ $video['filename'] }}</h4>
                                    </div>
                                    <div class="rounded-[1.1rem] border border-[rgb(var(--app-line))] bg-white/70 p-4">
                                        <p class="break-all font-mono text-xs leading-6 text-[rgb(var(--app-muted))]">{{ $video['url'] }}</p>
                                    </div>
                                    <a href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer" class="primary-action w-full justify-center">
                                        Buka video
                                    </a>
                                </div>
                            </article>
                        @empty
                            <div class="form-alert form-alert--danger md:col-span-2">
                                Tidak ada video yang dikembalikan oleh provider.
                            </div>
                        @endforelse
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">No</th>
                                        <th class="px-4 py-3 font-semibold">Filename</th>
                                        <th class="px-4 py-3 font-semibold">URL</th>
                                        <th class="px-4 py-3 font-semibold">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['videos'] as $video)
                                        <tr wire:key="tiktok-video-row-{{ $video['index'] }}">
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $video['index'] }}</td>
                                            <td class="px-4 py-4 font-mono text-xs text-[rgb(var(--app-muted))]">{{ $video['filename'] }}</td>
                                            <td class="px-4 py-4">
                                                <p class="min-w-[22rem] break-all font-mono text-xs leading-6 text-[rgb(var(--app-muted))]">{{ $video['url'] }}</p>
                                            </td>
                                            <td class="px-4 py-4">
                                                <a href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-[rgb(var(--app-accent))] hover:underline">
                                                    Buka
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">
                                                Tidak ada video yang dikembalikan oleh provider.
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
                        <p>Keyword pencarian video TikTok, contoh <code>pargoy</code>.</p>
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
                        <h4>Preview video</h4>
                        <p>Setiap URL pada <code>result</code> dirender sebagai player video agar bisa dicek langsung.</p>
                    </article>
                    <article>
                        <h4>Tabel URL</h4>
                        <p>Semua video juga disusun ke tabel supaya lebih mudah discan saat hasilnya banyak.</p>
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
