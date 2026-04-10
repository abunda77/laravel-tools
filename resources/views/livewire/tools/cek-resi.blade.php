<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Tools module</p>
            <h3>Cek Resi</h3>
            <p>
                Lacak posisi paket dari nomor resi dan ekspedisi. API key otomatis memakai <code>downloader_provider</code> dari Settings.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Endpoint</span>
                <strong>/tools/cekresi</strong>
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
                    <h3>Tracking paket</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan nomor resi dan slug ekspedisi sesuai dokumentasi provider, lalu jalankan request.
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
                    <div class="form-field">
                        <label for="cek_resi_number" class="form-label">Nomor resi</label>
                        <input
                            id="cek_resi_number"
                            type="text"
                            wire:model="resi"
                            class="form-input font-mono"
                            placeholder="SPXID054330680586"
                            autocomplete="off"
                        />
                        @error('resi') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <div class="flex items-start justify-between gap-4">
                            <label for="cek_resi_expedition" class="form-label">Ekspedisi</label>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>
                        <input
                            id="cek_resi_expedition"
                            type="text"
                            wire:model="ekspedisi"
                            class="form-input"
                            placeholder="shopee-express"
                            autocomplete="off"
                        />
                        <p class="form-help">Contoh: <code>shopee-express</code>.</p>
                        @error('ekspedisi') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        API key: <code>downloader_provider</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cek resi</span>
                        <span wire:loading wire:target="run">Melacak...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Status paket</p>
                            <h4>{{ $result['status'] ?: 'Status tidak tersedia' }}</h4>
                            @if ($result['message'])
                                <p>{{ $result['message'] }}</p>
                            @endif
                        </div>

                        @if ($result['shareLink'])
                            <a href="{{ $result['shareLink'] }}" target="_blank" rel="noopener noreferrer" class="primary-action">
                                Buka share link
                            </a>
                        @endif
                    </div>

                    <div class="parcel-data-grid">
                        <article>
                            <span>Resi</span>
                            <strong>{{ $result['resi'] }}</strong>
                        </article>
                        <article>
                            <span>Ekspedisi</span>
                            <strong>{{ $result['ekspedisi'] ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>Kode</span>
                            <strong>{{ $result['ekspedisiCode'] ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>Tanggal kirim</span>
                            <strong>{{ $result['tanggalKirim'] ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>Customer service</span>
                            <strong>{{ $result['customerService'] ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>Posisi terakhir</span>
                            <strong>{{ $result['lastPosition'] ?: '-' }}</strong>
                        </article>
                    </div>

                    <section class="parcel-timeline">
                        <div class="surface-panel__header">
                            <div>
                                <p class="section-kicker">History pengiriman</p>
                                <h3>Timeline paket</h3>
                            </div>
                        </div>

                        <div class="parcel-timeline__list">
                            @forelse ($result['history'] as $history)
                                <article wire:key="cek-resi-history-{{ $loop->index }}">
                                    <div class="parcel-timeline__marker">
                                        <span></span>
                                    </div>
                                    <div class="parcel-timeline__content">
                                        <time>{{ $history['tanggal'] ?: '-' }}</time>
                                        <p>{{ $history['keterangan'] ?: '-' }}</p>
                                    </div>
                                </article>
                            @empty
                                <div class="form-alert form-alert--danger">
                                    History pengiriman tidak tersedia pada response.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Data JSON</span>
                            <strong>{{ $result['ekspedisiCode'] ?: $result['ekspedisiSlug'] }}</strong>
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
                        <strong>resi</strong>
                        <p>Nomor resi paket.</p>
                    </article>
                    <article>
                        <strong>ekspedisi</strong>
                        <p>Slug ekspedisi, contoh <code>shopee-express</code>.</p>
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
                        <h4>Ringkasan paket</h4>
                        <p>Menampilkan resi, ekspedisi, status, tanggal kirim, customer service, posisi terakhir, dan share link.</p>
                    </article>
                    <article>
                        <h4>Timeline vertikal</h4>
                        <p>Riwayat pengiriman dirapikan dari key <code>data.history</code>.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
