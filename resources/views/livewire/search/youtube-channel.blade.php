<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Search module</p>
            <h3>Youtube Channel</h3>
            <p>
                Tampilkan informasi channel YouTube dan daftar video menggunakan
                <code>YouTube Data API v3</code>. Masukkan Channel ID (<code>UCxxx</code>)
                atau handle (<code>@NamaChannel</code>).
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>API</span>
                <strong>YouTube Data v3</strong>
            </div>
            <div class="mini-stat">
                <span>Provider</span>
                <strong>Google</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Cari Channel YouTube</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan Channel ID atau handle, lalu tekan <strong>Tampilkan Channel</strong>.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            {{-- Channel Input Form --}}
            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label for="channel_input" class="form-label">Channel ID atau Handle</label>
                                <p class="form-help">Contoh: <code>@Google</code> atau <code>UCVHdiysqBjVRSBHRODX2p0Q</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>
                        <input
                            id="channel_input"
                            type="text"
                            wire:model="channelInput"
                            class="form-input"
                            placeholder="@Google"
                            autocomplete="off"
                        />
                        @error('channelInput') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">API key: <code>youtubeapi_provider</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Tampilkan Channel</span>
                        <span wire:loading wire:target="run">Memuat channel...</span>
                    </button>
                </div>
            </form>

            @if ($channel)
                {{-- Channel Info Card --}}
                <article class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.55)]">
                    <div class="flex flex-col gap-5 p-6 sm:flex-row sm:items-start">
                        @if ($channel['thumbnail'])
                            <img
                                src="{{ $channel['thumbnail'] }}"
                                alt="{{ $channel['title'] }}"
                                class="h-20 w-20 flex-shrink-0 rounded-full object-cover ring-4 ring-white/80"
                            />
                        @endif
                        <div class="min-w-0 flex-1 space-y-3">
                            <div>
                                <h4 class="text-lg font-bold text-[rgb(var(--app-ink))]">{{ $channel['title'] }}</h4>
                                <p class="font-mono text-xs text-[rgb(var(--app-muted))]">{{ $channel['id'] }}</p>
                            </div>
                            @if ($channel['description'])
                                <p class="line-clamp-2 text-sm leading-relaxed text-[rgb(var(--app-muted))]">{{ $channel['description'] }}</p>
                            @endif
                            <div class="grid gap-3 rounded-[1.1rem] border border-[rgb(var(--app-line))] bg-white/70 p-4 text-sm sm:grid-cols-3">
                                <div class="space-y-1">
                                    <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-[rgb(var(--app-muted))]">Subscriber</span>
                                    <strong class="text-[rgb(var(--app-ink))]">
                                        {{ $channel['hiddenSubscriberCount'] ? 'Disembunyikan' : \Illuminate\Support\Number::format((int) $channel['subscriberCount']) }}
                                    </strong>
                                </div>
                                <div class="space-y-1">
                                    <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-[rgb(var(--app-muted))]">Total Views</span>
                                    <strong class="text-[rgb(var(--app-ink))]">{{ \Illuminate\Support\Number::format((int) $channel['viewCount']) }}</strong>
                                </div>
                                <div class="space-y-1">
                                    <span class="block text-xs font-semibold uppercase tracking-[0.2em] text-[rgb(var(--app-muted))]">Total Video</span>
                                    <strong class="text-[rgb(var(--app-ink))]">{{ \Illuminate\Support\Number::format((int) $channel['videoCount']) }}</strong>
                                </div>
                            </div>
                            <a href="https://www.youtube.com/channel/{{ $channel['id'] }}" target="_blank" rel="noopener noreferrer" class="primary-action inline-flex">
                                Buka di YouTube
                            </a>
                        </div>
                    </div>
                </article>

                {{-- Search Within Channel --}}
                <div class="rounded-[1.4rem] border border-[rgb(var(--app-line))] bg-white/60 p-4">
                    <form wire:submit="runSearch" class="flex items-center gap-3">
                        <div class="flex-1">
                            <label for="search_keyword" class="sr-only">Cari video di channel ini</label>
                            <input
                                id="search_keyword"
                                type="text"
                                wire:model="searchKeyword"
                                class="form-input"
                                placeholder="Cari video di channel ini... (misal: tutorial)"
                                autocomplete="off"
                            />
                            @error('searchKeyword') <p class="form-error mt-1">{{ $message }}</p> @enderror
                        </div>
                        <button type="submit" class="primary-action flex-shrink-0" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="runSearch">Cari</span>
                            <span wire:loading wire:target="runSearch">Mencari...</span>
                        </button>
                        @if ($isSearchMode)
                            <button type="button" wire:click="clearSearch" class="primary-action flex-shrink-0" wire:loading.attr="disabled" style="background: rgb(var(--app-muted) / 0.15); color: rgb(var(--app-ink));">
                                <span wire:loading.remove wire:target="clearSearch">✕ Reset</span>
                                <span wire:loading wire:target="clearSearch">Loading...</span>
                            </button>
                        @endif
                    </form>
                    @if ($isSearchMode)
                        <p class="mt-2 text-xs text-[rgb(var(--app-muted))]">
                            ⚠️ Mode pencarian menggunakan <strong>100 quota unit/request</strong> dari YouTube API.
                            Menampilkan <strong>{{ count($videos) }}</strong> dari ~<strong>{{ \Illuminate\Support\Number::format($searchTotalResults) }}</strong> hasil untuk "<em>{{ $searchKeyword }}</em>".
                        </p>
                    @endif
                </div>

                {{-- Video Table --}}
                <div>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <p class="text-sm text-[rgb(var(--app-muted))]">
                            @if ($isSearchMode)
                                Hasil pencarian "<strong>{{ $searchKeyword }}</strong>": menampilkan <strong>{{ count($videos) }}</strong> video
                            @else
                                Menampilkan <strong>{{ count($videos) }}</strong> dari <strong>{{ \Illuminate\Support\Number::format((int) $channel['videoCount']) }}</strong> video
                            @endif
                        </p>
                        @if ($nextPageToken)
                            <span class="status-pill status-pill--pending">Ada video berikutnya</span>
                        @else
                            <span class="status-pill status-pill--ready">Semua tampil</span>
                        @endif
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">#</th>
                                        <th class="px-4 py-3 font-semibold">Thumbnail</th>
                                        <th class="px-4 py-3 font-semibold">Judul Video</th>
                                        <th class="px-4 py-3 font-semibold">Tanggal</th>
                                        <th class="px-4 py-3 font-semibold">Link</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($videos as $i => $video)
                                        <tr wire:key="yt-row-{{ $video['videoId'] }}-{{ $i }}">
                                            <td class="px-4 py-3 text-xs text-[rgb(var(--app-muted))]">{{ $i + 1 }}</td>
                                            <td class="px-4 py-3">
                                                @if ($video['thumbnail'])
                                                    <img
                                                        src="{{ $video['thumbnail'] }}"
                                                        alt="{{ $video['title'] }}"
                                                        class="h-12 w-20 rounded-xl object-cover"
                                                        loading="lazy"
                                                    />
                                                @else
                                                    <div class="flex h-12 w-20 items-center justify-center rounded-xl bg-white/70 text-xs text-[rgb(var(--app-muted))]">—</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="max-w-xs font-semibold leading-5 text-[rgb(var(--app-ink))]">{{ $video['title'] ?: '-' }}</p>
                                                <p class="mt-0.5 font-mono text-xs text-[rgb(var(--app-muted))]">{{ $video['videoId'] }}</p>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-[rgb(var(--app-muted))]">
                                                {{ $video['publishedAt'] ? \Carbon\Carbon::parse($video['publishedAt'])->format('d M Y') : '-' }}
                                            </td>
                                            <td class="px-4 py-3">
                                                <a
                                                    href="{{ $video['url'] }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    class="text-sm font-semibold text-[rgb(var(--app-accent))] hover:underline"
                                                >
                                                    Tonton ↗
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-8 text-center text-[rgb(var(--app-muted))]">
                                                Tidak ada video yang ditemukan.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Load More Button --}}
                    @if ($nextPageToken)
                        <div class="mt-4 flex justify-center">
                            <button
                                type="button"
                                wire:click="loadMore"
                                wire:loading.attr="disabled"
                                class="primary-action"
                            >
                                <span wire:loading.remove wire:target="loadMore">Muat 50 Video Berikutnya</span>
                                <span wire:loading wire:target="loadMore">Memuat...</span>
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Raw JSON --}}
                <div class="json-viewer">
                    <div class="json-viewer__header">
                        <span>Channel JSON</span>
                        <strong>channel info</strong>
                    </div>
                    <pre>{{ $this->prettyData }}</pre>
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
                        <strong>Channel ID</strong>
                        <p>ID channel diawali <code>UC</code>, contoh <code>UCVHdiysqBjVRSBHRODX2p0Q</code>.</p>
                    </article>
                    <article>
                        <strong>Handle</strong>
                        <p>Handle dengan <code>@</code>, contoh <code>@Google</code>.</p>
                    </article>
                    <article>
                        <strong>API Key</strong>
                        <p>Diambil dari <code>youtubeapi_provider</code> di Settings → API Keys.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Quota Info</p>
                        <h3>Limit API</h3>
                    </div>
                </div>
                <div class="feature-list">
                    <article>
                        <h4>Browse video <span class="text-xs font-normal text-[rgb(var(--app-muted))]">1 unit/request</span></h4>
                        <p>Muat 50 video per klik menggunakan <code>playlistItems.list</code>. Sangat hemat quota.</p>
                    </article>
                    <article>
                        <h4>Cari dalam channel <span class="text-xs font-normal text-[rgb(var(--app-muted))]">100 unit/request</span></h4>
                        <p>Menggunakan <code>search.list</code>. Jatah harian 10.000 unit = max ~100 kali pencarian.</p>
                    </article>
                    <article>
                        <h4>maxResults</h4>
                        <p>YouTube membatasi 50 item per request. Tekan "Muat Berikutnya" untuk lanjut.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
