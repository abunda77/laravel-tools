<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Search module</p>
            <h3>Quotes Anime</h3>
            <p>
                Ambil quote anime random lewat endpoint <code>/random/animequote</code>. API key otomatis memakai
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
                <strong>/random/animequote</strong>
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
                    <h3>Generate Quotes Anime</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Klik tombol untuk mengambil quote random, lalu hasil array <code>result</code> ditampilkan dalam tabel.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        API key: <code>downloader_provider</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Ambil quote</span>
                        <span wire:loading wire:target="run">Mengambil...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Response summary</p>
                            <h4>{{ $result['total'] }} quote ditemukan</h4>
                            <p>{{ $result['author'] ? 'Author: '.$result['author'] : 'Provider tidak mengirim author.' }}</p>
                        </div>
                        <span class="status-pill status-pill--ready">{{ $result['total'] }} item</span>
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Char</th>
                                        <th class="px-4 py-3 font-semibold">From Anime</th>
                                        <th class="px-4 py-3 font-semibold">Episode</th>
                                        <th class="px-4 py-3 font-semibold">Quote</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['quotes'] as $quote)
                                        <tr wire:key="anime-quote-row-{{ md5($quote['char'].$quote['from_anime'].$quote['episode'].$quote['quote']) }}">
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $quote['char'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $quote['from_anime'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $quote['episode'] ?: '-' }}</td>
                                            <td class="min-w-[24rem] px-4 py-4 leading-6 text-[rgb(var(--app-ink))]">{{ $quote['quote'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">
                                                Tidak ada quote yang dikembalikan oleh provider.
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
                        <strong>apikey</strong>
                        <p>Diambil dari <code>downloader_provider</code> dan dikirim sebagai query parameter.</p>
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
                        <h4>Table response</h4>
                        <p>Array <code>result</code> ditampilkan sebagai tabel dengan kolom <code>char</code>, <code>from_anime</code>, <code>episode</code>, dan <code>quote</code>.</p>
                    </article>
                    <article>
                        <h4>Raw JSON</h4>
                        <p>Payload asli tetap tersedia untuk inspeksi ketika struktur provider berubah.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
