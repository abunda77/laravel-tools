<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Search module</p>
            <h3>Freepik Image</h3>
            <p>
                Cari stock image dan template Freepik via Magnific API dengan API key <code>freepik_provider</code>.
                Tool ini memakai tiga flow endpoint: search resources, detail resource, dan download per format.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Base URL</span>
                <strong>api.magnific.com</strong>
            </div>
            <div class="mini-stat">
                <span>Auth header</span>
                <strong>x-magnific-api-key</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Cari resource Freepik</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Hasil ditampilkan sebagai card dan table. Klik salah satu resource untuk memuat detail dan daftar format download yang tersedia.
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
                                <label for="freepik_query" class="form-label">Query</label>
                                <p class="form-help">Contoh: <code>white t-shirt mockup</code>, <code>modern business card</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>

                        <input
                            id="freepik_query"
                            type="text"
                            wire:model="query"
                            class="form-input"
                            placeholder="white t-shirt mockup"
                            autocomplete="off"
                        />
                        @error('query') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="freepik_limit" class="form-label">Limit</label>
                        <input id="freepik_limit" type="number" min="1" max="50" wire:model="limit" class="form-input" />
                        @error('limit') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="freepik_order" class="form-label">Order</label>
                        <select id="freepik_order" wire:model="order" class="form-input">
                            <option value="relevance">relevance</option>
                            <option value="recent">recent</option>
                        </select>
                        @error('order') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        API key: <code>freepik_provider</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cari resource</span>
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
                            <p>
                                {{ $result['total'] }} resource ditemukan.
                                Halaman {{ $result['pagination']['currentPage'] }} dari {{ $result['pagination']['lastPage'] }}.
                            </p>
                        </div>
                        <span class="status-pill status-pill--ready">{{ count($result['resources']) }} item di halaman ini</span>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        @forelse ($result['resources'] as $resource)
                            <article wire:key="freepik-image-card-{{ $resource['id'] }}" class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.55)]">
                                <div class="aspect-[4/3] overflow-hidden bg-white/70">
                                    @if ($resource['imageUrl'])
                                        <img src="{{ $resource['imageUrl'] }}" alt="{{ $resource['title'] }}" class="h-full w-full object-cover" />
                                    @else
                                        <div class="flex h-full items-center justify-center text-sm text-[rgb(var(--app-muted))]">
                                            Preview tidak tersedia
                                        </div>
                                    @endif
                                </div>
                                <div class="space-y-3 p-5">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-[0.2em] text-[rgb(var(--app-muted))]">
                                            <span>{{ $resource['imageType'] ?: 'resource' }}</span>
                                            <span>{{ $resource['imageOrientation'] ?: '-' }}</span>
                                            @if ($resource['isNew'])
                                                <span class="rounded-full bg-[rgb(var(--app-accent))] px-2 py-1 text-white">new</span>
                                            @endif
                                        </div>
                                        <h4 class="text-base font-bold leading-6 text-[rgb(var(--app-ink))]">
                                            @if ($resource['url'])
                                                <a href="{{ $resource['url'] }}" target="_blank" rel="noopener noreferrer" class="hover:underline">
                                                    {{ $resource['title'] ?: '-' }}
                                                </a>
                                            @else
                                                {{ $resource['title'] ?: '-' }}
                                            @endif
                                        </h4>
                                        <p class="text-sm text-[rgb(var(--app-muted))]">
                                            {{ $resource['authorName'] ?: '-' }} · {{ number_format($resource['downloads']) }} downloads · {{ number_format($resource['likes']) }} likes
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        @forelse ($resource['availableFormats'] as $format => $formatData)
                                            <span class="rounded-full border border-[rgb(var(--app-line))] bg-white/80 px-3 py-1 text-xs font-semibold uppercase text-[rgb(var(--app-muted))]">
                                                {{ $format }} · {{ $formatData['total'] }}
                                            </span>
                                        @empty
                                            <span class="rounded-full border border-[rgb(var(--app-line))] bg-white/80 px-3 py-1 text-xs font-semibold uppercase text-[rgb(var(--app-muted))]">
                                                format tidak tersedia
                                            </span>
                                        @endforelse
                                    </div>

                                    <div class="grid gap-3 rounded-[1.1rem] border border-[rgb(var(--app-line))] bg-white/70 p-4 text-sm text-[rgb(var(--app-muted))]">
                                        <div>
                                            <span class="block">Filename</span>
                                            <strong class="mt-1 block break-all font-mono text-xs text-[rgb(var(--app-ink))]">{{ $resource['filename'] ?: '-' }}</strong>
                                        </div>
                                        <div>
                                            <span class="block">Preview size</span>
                                            <strong class="mt-1 block text-[rgb(var(--app-ink))]">{{ $resource['imageSize'] ?: '-' }}</strong>
                                        </div>
                                    </div>

                                    <button type="button" wire:click="selectResource({{ $resource['id'] }})" class="primary-action w-full justify-center" wire:loading.attr="disabled">
                                        Muat detail & format
                                    </button>
                                </div>
                            </article>
                        @empty
                            <div class="form-alert form-alert--danger md:col-span-2">
                                Tidak ada resource yang dikembalikan oleh provider.
                            </div>
                        @endforelse
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Thumbnail</th>
                                        <th class="px-4 py-3 font-semibold">Title</th>
                                        <th class="px-4 py-3 font-semibold">Type</th>
                                        <th class="px-4 py-3 font-semibold">Author</th>
                                        <th class="px-4 py-3 font-semibold">Downloads</th>
                                        <th class="px-4 py-3 font-semibold">Likes</th>
                                        <th class="px-4 py-3 font-semibold">Published</th>
                                        <th class="px-4 py-3 font-semibold">Formats</th>
                                        <th class="px-4 py-3 font-semibold">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['resources'] as $resource)
                                        <tr wire:key="freepik-image-row-{{ $resource['id'] }}">
                                            <td class="px-4 py-4">
                                                @if ($resource['imageUrl'])
                                                    <img src="{{ $resource['imageUrl'] }}" alt="{{ $resource['title'] }}" class="h-16 w-16 rounded-2xl object-cover" />
                                                @else
                                                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-white/70 text-xs text-[rgb(var(--app-muted))]">N/A</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4">
                                                @if ($resource['url'])
                                                    <a href="{{ $resource['url'] }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-[rgb(var(--app-ink))] hover:underline">
                                                        {{ $resource['title'] ?: '-' }}
                                                    </a>
                                                @else
                                                    <span class="font-semibold text-[rgb(var(--app-ink))]">{{ $resource['title'] ?: '-' }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $resource['imageType'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $resource['authorName'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ number_format($resource['downloads']) }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ number_format($resource['likes']) }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $resource['publishedAt'] ?: '-' }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex min-w-[12rem] flex-wrap gap-2">
                                                    @foreach ($resource['availableFormats'] as $format => $formatData)
                                                        <span class="rounded-full border border-[rgb(var(--app-line))] px-2 py-1 text-xs uppercase text-[rgb(var(--app-muted))]">
                                                            {{ $format }} ({{ $formatData['total'] }})
                                                        </span>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <button type="button" wire:click="selectResource({{ $resource['id'] }})" class="primary-action justify-center">
                                                    Pilih
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">
                                                Tidak ada resource yang dikembalikan oleh provider.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Search JSON</span>
                            <strong>{{ count($result['resources']) }} item</strong>
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
                        <p class="section-kicker">Endpoints</p>
                        <h3>Request flow</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>GET /v1/resources</strong>
                        <p>Cari resource dengan <code>term</code>, <code>page</code>, <code>limit</code>, dan <code>order</code>.</p>
                    </article>
                    <article>
                        <strong>GET /v1/resources/{id}</strong>
                        <p>Muat detail lengkap resource terpilih.</p>
                    </article>
                    <article>
                        <strong>GET /v1/resources/{id}/download/{format}</strong>
                        <p>Ambil download untuk format tertentu seperti <code>jpg</code> atau <code>png</code>.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Detail panel</p>
                        <h3>Resource terpilih</h3>
                    </div>
                </div>

                @if ($selectedResource)
                    <div class="feature-list">
                        <article>
                            <h4>{{ $selectedResource['title'] ?: '-' }}</h4>
                            <p>{{ $selectedResource['authorName'] ?: '-' }} · {{ $selectedResource['imageType'] ?: '-' }} · {{ $selectedResource['imageOrientation'] ?: '-' }}</p>
                        </article>
                    </div>

                    <div class="mt-5 space-y-3">
                        <p class="section-kicker">Available formats</p>
                        <div class="flex flex-wrap gap-2">
                            @forelse ($selectedFormats as $format => $formatData)
                                <button type="button" wire:click="downloadFormat('{{ $format }}')" class="rounded-full border border-[rgb(var(--app-line))] bg-white px-3 py-2 text-xs font-semibold uppercase text-[rgb(var(--app-ink))]" wire:loading.attr="disabled">
                                    {{ $format }} · {{ $formatData['total'] }}
                                </button>
                            @empty
                                <span class="text-sm text-[rgb(var(--app-muted))]">Format download tidak tersedia.</span>
                            @endforelse
                        </div>
                    </div>

                    @if ($selectedFormatDownload)
                        <div class="json-viewer mt-5">
                            <div class="json-viewer__header">
                                <span>Download format {{ $selectedFormat ?: '-' }}</span>
                                <strong>{{ $selectedFormatDownload['filename'] ?? 'ready' }}</strong>
                            </div>
                            <pre>{{ json_encode($selectedFormatDownload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>
                    @endif

                    <div class="json-viewer mt-5">
                        <div class="json-viewer__header">
                            <span>Detail JSON</span>
                            <strong>#{{ $selectedResource['id'] }}</strong>
                        </div>
                        <pre>{{ $this->prettySelectedResource }}</pre>
                    </div>
                @else
                    <div class="feature-list">
                        <article>
                            <h4>Belum ada resource terpilih</h4>
                            <p>Pilih salah satu hasil pencarian untuk memuat detail dan format download.</p>
                        </article>
                    </div>
                @endif
            </section>
        </aside>
    </div>
</div>
