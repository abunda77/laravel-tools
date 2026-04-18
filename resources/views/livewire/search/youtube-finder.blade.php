<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Search module</p>
            <h3>Youtube Finder</h3>
            <p>
                Cari video YouTube berdasarkan keyword memakai <code>YouTube Data API v3</code> dengan API key
                <code>youtubeapi_provider</code>, lalu lihat hasil pentingnya dalam tabel yang siap discan.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Provider</span>
                <strong>Google</strong>
            </div>
            <div class="mini-stat">
                <span>Region</span>
                <strong>{{ $regionCode !== '' ? $regionCode : '-' }}</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Temukan video YouTube</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan keyword, kirim request ke YouTube, lalu telusuri hasil dengan data thumbnail, channel, views, durasi, release, dan link.
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
                                <label for="youtube_finder_query" class="form-label">Keyword</label>
                                <p class="form-help">Contoh: <code>laravel tutorial</code>, <code>belajar livewire</code>, atau <code>review iphone 16</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>

                        <input
                            id="youtube_finder_query"
                            type="text"
                            wire:model="query"
                            class="form-input"
                            placeholder="laravel tutorial"
                            autocomplete="off"
                        />
                        @error('query') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">API key: <code>youtubeapi_provider</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cari video</span>
                        <span wire:loading wire:target="run">Mencari...</span>
                    </button>
                </div>
            </form>

            @if ($hasSearched)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Response summary</p>
                            <h4>{{ $query }}</h4>
                            <p>Menampilkan <strong>{{ count($videos) }}</strong> dari sekitar <strong>{{ \Illuminate\Support\Number::format($totalResults) }}</strong> hasil.</p>
                        </div>
                        <span class="status-pill {{ $nextPageToken ? 'status-pill--pending' : 'status-pill--ready' }}">
                            {{ $nextPageToken ? 'Ada halaman berikutnya' : 'Semua hasil termuat' }}
                        </span>
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">#</th>
                                        <th class="px-4 py-3 font-semibold">Video</th>
                                        <th class="px-4 py-3 font-semibold">Channel</th>
                                        <th class="px-4 py-3 font-semibold">Views</th>
                                        <th class="px-4 py-3 font-semibold">Durasi</th>
                                        <th class="px-4 py-3 font-semibold">Release</th>
                                        <th class="px-4 py-3 font-semibold">Meta</th>
                                        <th class="px-4 py-3 font-semibold">Link</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($videos as $index => $video)
                                        <tr wire:key="youtube-finder-row-{{ $video['videoId'] }}-{{ $index }}">
                                            <td class="px-4 py-4 text-xs text-[rgb(var(--app-muted))]">{{ $index + 1 }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex min-w-[22rem] items-start gap-3">
                                                    @if ($video['thumbnail'])
                                                        <img
                                                            src="{{ $video['thumbnail'] }}"
                                                            alt="{{ $video['title'] }}"
                                                            class="h-14 w-24 rounded-2xl object-cover"
                                                            loading="lazy"
                                                        />
                                                    @else
                                                        <div class="flex h-14 w-24 items-center justify-center rounded-2xl bg-white/70 text-xs text-[rgb(var(--app-muted))]">-</div>
                                                    @endif
                                                    <div class="space-y-1">
                                                        <a
                                                            href="{{ $video['url'] }}"
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            class="line-clamp-2 block font-semibold leading-5 text-[rgb(var(--app-ink))] hover:underline"
                                                        >
                                                            {{ $video['title'] ?: '-' }}
                                                        </a>
                                                        <p class="line-clamp-2 max-w-sm text-xs text-[rgb(var(--app-muted))]">{{ $video['description'] ?: '-' }}</p>
                                                        <p class="font-mono text-[11px] text-[rgb(var(--app-muted))]">{{ $video['videoId'] }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="min-w-[10rem]">
                                                    <p class="font-semibold text-[rgb(var(--app-ink))]">{{ $video['channelTitle'] ?: '-' }}</p>
                                                    <p class="mt-1 font-mono text-xs text-[rgb(var(--app-muted))]">{{ $video['channelId'] ?: '-' }}</p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-brand-deep))]">
                                                {{ \Illuminate\Support\Number::format($video['views']) }}
                                            </td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $video['duration'] ?: '-' }}</td>
                                            <td class="whitespace-nowrap px-4 py-4 text-[rgb(var(--app-muted))]">
                                                {{ $video['publishedAt'] ? \Carbon\Carbon::parse($video['publishedAt'])->format('d M Y H:i') : '-' }}
                                            </td>
                                            <td class="px-4 py-4 text-xs text-[rgb(var(--app-muted))]">
                                                <div class="space-y-1">
                                                    <p>Like: {{ \Illuminate\Support\Number::format($video['likes']) }}</p>
                                                    <p>Komen: {{ \Illuminate\Support\Number::format($video['comments']) }}</p>
                                                    <p>{{ strtoupper($video['definition'] ?: '-') }} / {{ strtoupper($video['dimension'] ?: '-') }}</p>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <a
                                                    href="{{ $video['url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-sm font-semibold text-[rgb(var(--app-accent))] hover:underline"
                                                >
                                                    Buka video
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="px-4 py-8 text-center text-[rgb(var(--app-muted))]">
                                                Tidak ada video yang ditemukan untuk keyword ini.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($nextPageToken)
                        <div class="mt-4 flex justify-center">
                            <button
                                type="button"
                                wire:click="loadMore"
                                wire:loading.attr="disabled"
                                class="primary-action"
                            >
                                <span wire:loading.remove wire:target="loadMore">Muat hasil berikutnya</span>
                                <span wire:loading wire:target="loadMore">Memuat...</span>
                            </button>
                        </div>
                    @endif

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Data JSON</span>
                            <strong>{{ count($videos) }} item</strong>
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
                        <strong>keyword</strong>
                        <p>Query pencarian video YouTube. Finder fokus ke hasil <code>type=video</code>.</p>
                    </article>
                    <article>
                        <strong>API key</strong>
                        <p>Diambil dari <code>youtubeapi_provider</code> di Settings -> API Keys.</p>
                    </article>
                    <article>
                        <strong>Pagination</strong>
                        <p>Jika YouTube mengembalikan <code>nextPageToken</code>, tombol load more akan muncul.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Columns</p>
                        <h3>Data penting</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Video identity</h4>
                        <p>Thumbnail, judul, deskripsi singkat, video ID, dan link langsung ke YouTube.</p>
                    </article>
                    <article>
                        <h4>Channel & performance</h4>
                        <p>Menampilkan channel, views, likes, comments, serta kualitas video bila tersedia.</p>
                    </article>
                    <article>
                        <h4>Release time</h4>
                        <p>Kolom release memakai <code>publishedAt</code> dari YouTube agar mudah disortir secara visual.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
