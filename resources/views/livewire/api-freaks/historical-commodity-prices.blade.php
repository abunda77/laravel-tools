<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Historical Commodity Prices API</h3>
            <p>
                Ambil harga histori komoditas dari <code>{{ \App\Services\ApiFreaks\HistoricalCommodityPricesService::ENDPOINT }}</code>
                untuk tanggal tertentu dengan output open, high, low, dan close.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\HistoricalCommodityPricesService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Fetch historical commodity rates</h3>
                    <p class="surface-panel__text surface-panel__text--tight">Masukkan satu atau lebih symbol komoditas, lalu pilih tanggal dalam format <code>YYYY-MM-DD</code>.</p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">{{ $errorMessage }}</div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <label for="historical_commodity_symbols" class="form-label">Symbols</label>
                        <input id="historical_commodity_symbols" type="text" wire:model="symbols" class="form-input font-mono" placeholder="XAU,WTIOIL-SPOT" autocomplete="off" />
                        @error('symbols') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="historical_commodity_date" class="form-label">Date</label>
                        <input id="historical_commodity_date" type="date" wire:model="date" class="form-input" />
                        @error('date') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">Contoh: <code>2026-04-18</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Ambil histori harga</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="grid gap-3 md:grid-cols-2">
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Symbols</span><strong class="mt-2 block text-base font-bold">{{ $result['symbols'] }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Date</span><strong class="mt-2 block text-base font-bold">{{ $result['date'] }}</strong></article>
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr><th class="px-4 py-3 font-semibold">Symbol</th><th class="px-4 py-3 font-semibold">Date</th><th class="px-4 py-3 font-semibold">Open</th><th class="px-4 py-3 font-semibold">High</th><th class="px-4 py-3 font-semibold">Low</th><th class="px-4 py-3 font-semibold">Close</th></tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['rows'] as $row)
                                        <tr wire:key="historical-commodity-{{ $row['symbol'] }}">
                                            <td class="px-4 py-4 font-mono text-[rgb(var(--app-ink))]">{{ $row['symbol'] }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $row['date'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $row['open'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $row['high'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $row['low'] ?: '-' }}</td>
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-brand-deep))]">{{ $row['close'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">Tidak ada data histori yang dikembalikan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Raw JSON response</span>
                            <strong>{{ count($result['rows']) }} rows</strong>
                        </div>
                        <pre>{{ $this->prettyResponse }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div><p class="section-kicker">Response</p><h3>Data OHLC</h3></div>
                </div>
                <div class="feature-list">
                    <article><h4>Open High Low Close</h4><p>Seluruh field utama historis ditampilkan langsung per symbol.</p></article>
                    <article><h4>Audit payload</h4><p>Raw JSON tetap tersedia untuk pemeriksaan struktur provider.</p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
