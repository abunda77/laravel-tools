<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Search module</p>
            <h3>Youtube Search</h3>
            <p>
                Cari video Youtube lewat endpoint <code>/search/youtube</code>. API key otomatis memakai
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
                <strong>/search/youtube</strong>
            </div>
            <div class="mini-stat">
                <span>View mode</span>
                <strong>{{ strtoupper($displayMode) }}</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Cari video Youtube</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan keyword, pilih mode tampilan hasil, lalu lihat hasil dalam bentuk card atau table.
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
                                <label for="youtube_query" class="form-label">Query</label>
                                <p class="form-help">Contoh: <code>cara mengecat dinding</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>

                        <input
                            id="youtube_query"
                            type="text"
                            wire:model="query"
                            class="form-input"
                            placeholder="cara mengecat dinding"
                            autocomplete="off"
                        />
                        @error('query') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap gap-3">
                    <button
                        type="button"
                        wire:click="setDisplayMode('card')"
                        class="provider-switcher__item max-w-[11rem] {{ $displayMode === 'card' ? 'is-active' : '' }}"
                    >
                        <span>Card View</span>
                        <strong>Thumbnail + meta</strong>
                    </button>
                    <button
                        type="button"
                        wire:click="setDisplayMode('table')"
                        class="provider-switcher__item max-w-[11rem] {{ $displayMode === 'table' ? 'is-active' : '' }}"
                    >
                        <span>Table View</span>
                        <strong>Daftar ringkas</strong>
                    </button>
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
                            <p>{{ $result['total'] }} video ditemukan{{ $result['author'] ? ' | Author: '.$result['author'] : '' }}.</p>
                        </div>
                        <span class="status-pill status-pill--ready">{{ strtoupper($displayMode) }}</span>
                    </div>

                    @if ($displayMode === 'card')
                        <div class="grid gap-4 md:grid-cols-2">
                            @forelse ($result['videos'] as $video)
                                <article wire:key="youtube-card-{{ md5($video['id']) }}" class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.55)]">
                                    <div class="aspect-video overflow-hidden bg-white/70">
                                        @if ($video['thumbnail'])
                                            <img src="{{ $video['thumbnail'] }}" alt="{{ $video['title'] }}" class="h-full w-full object-cover" />
                                        @else
                                            <div class="flex h-full items-center justify-center text-sm text-[rgb(var(--app-muted))]">
                                                Thumbnail tidak tersedia
                                            </div>
                                        @endif
                                    </div>
                                    <div class="space-y-4 p-5">
                                        <div class="space-y-2">
                                            <h4 class="line-clamp-2 text-base font-bold leading-6 text-[rgb(var(--app-ink))]">{{ $video['title'] ?: '-' }}</h4>
                                            <p class="text-sm text-[rgb(var(--app-muted))]">{{ $video['author'] ?: '-' }}</p>
                                        </div>
                                        <div class="grid gap-3 rounded-[1.1rem] border border-[rgb(var(--app-line))] bg-white/70 p-4 text-sm text-[rgb(var(--app-muted))] sm:grid-cols-3">
                                            <div class="space-y-1">
                                                <span class="block text-xs font-semibold uppercase tracking-[0.2em]">Duration</span>
                                                <strong class="text-[rgb(var(--app-ink))]">{{ $video['duration'] ?: '-' }}</strong>
                                            </div>
                                            <div class="space-y-1">
                                                <span class="block text-xs font-semibold uppercase tracking-[0.2em]">Views</span>
                                                <strong class="text-[rgb(var(--app-ink))]">{{ \Illuminate\Support\Number::format($video['views']) }}</strong>
                                            </div>
                                            <div class="space-y-1">
                                                <span class="block text-xs font-semibold uppercase tracking-[0.2em]">Upload</span>
                                                <strong class="text-[rgb(var(--app-ink))]">{{ $video['uploadDate'] ?: '-' }}</strong>
                                            </div>
                                        </div>
                                        <a href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer" class="primary-action w-full justify-center">
                                            Buka di Youtube
                                        </a>
                                    </div>
                                </article>
                            @empty
                                <div class="form-alert form-alert--danger md:col-span-2">
                                    Tidak ada video yang dikembalikan oleh provider.
                                </div>
                            @endforelse
                        </div>
                    @else
                        <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                    <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                        <tr>
                                            <th class="px-4 py-3 font-semibold">Video</th>
                                            <th class="px-4 py-3 font-semibold">Author</th>
                                            <th class="px-4 py-3 font-semibold">Duration</th>
                                            <th class="px-4 py-3 font-semibold">Views</th>
                                            <th class="px-4 py-3 font-semibold">Upload</th>
                                            <th class="px-4 py-3 font-semibold">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                        @forelse ($result['videos'] as $video)
                                            <tr wire:key="youtube-row-{{ md5($video['id']) }}">
                                                <td class="px-4 py-4">
                                                    <div class="flex min-w-[20rem] items-center gap-3">
                                                        @if ($video['thumbnail'])
                                                            <img src="{{ $video['thumbnail'] }}" alt="{{ $video['title'] }}" class="h-14 w-20 rounded-2xl object-cover" />
                                                        @endif
                                                        <div>
                                                            <p class="font-semibold text-[rgb(var(--app-ink))]">{{ $video['title'] ?: '-' }}</p>
                                                            <p class="mt-1 break-all font-mono text-xs text-[rgb(var(--app-muted))]">{{ $video['url'] ?: '-' }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-ink))]">{{ $video['author'] ?: '-' }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $video['duration'] ?: '-' }}</td>
                                                <td class="px-4 py-4 font-semibold text-[rgb(var(--app-brand-deep))]">{{ \Illuminate\Support\Number::format($video['views']) }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $video['uploadDate'] ?: '-' }}</td>
                                                <td class="px-4 py-4">
                                                    <a href="{{ $video['url'] }}" target="_blank" rel="noopener noreferrer" class="text-sm font-semibold text-[rgb(var(--app-accent))] hover:underline">
                                                        Buka
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">
                                                    Tidak ada video yang dikembalikan oleh provider.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

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
                        <p>Keyword pencarian video Youtube, contoh <code>cara mengecat dinding</code>.</p>
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
                        <h4>Mode card</h4>
                        <p>Menampilkan thumbnail, judul, author, durasi, views, dan usia upload secara ringkas.</p>
                    </article>
                    <article>
                        <h4>Mode table</h4>
                        <p>Memudahkan scan hasil banyak item dengan kolom yang tetap informatif di layar lebar.</p>
                    </article>
                    <article>
                        <h4>Raw JSON</h4>
                        <p>Isi <code>result</code> tetap tersedia untuk verifikasi payload dari provider.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
