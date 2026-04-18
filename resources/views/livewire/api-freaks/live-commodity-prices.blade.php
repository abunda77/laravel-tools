<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Live Commodity Prices API</h3>
            <p>
                Ambil harga komoditas live dari <code>{{ \App\Services\ApiFreaks\LiveCommodityPricesService::ENDPOINT }}</code>
                berdasarkan daftar symbol, update period, dan quote currency opsional.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\LiveCommodityPricesService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Fetch live commodity rates</h3>
                    <p class="surface-panel__text surface-panel__text--tight">Gunakan beberapa symbol dipisahkan koma, lalu pilih update <code>1m</code> atau <code>10m</code>.</p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">{{ $errorMessage }}</div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <label for="live_commodity_symbols" class="form-label">Symbols</label>
                        <input id="live_commodity_symbols" type="text" wire:model="symbols" class="form-input font-mono" placeholder="XAU,WTIOIL-SPOT" autocomplete="off" />
                        @error('symbols') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="live_commodity_updates" class="form-label">Updates</label>
                        <select id="live_commodity_updates" wire:model="updates" class="form-input">
                            <option value="1m">1m</option>
                            <option value="10m">10m</option>
                        </select>
                        @error('updates') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="live_commodity_quote" class="form-label">Quote</label>
                        <input id="live_commodity_quote" type="text" wire:model="quote" class="form-input font-mono" placeholder="USD" autocomplete="off" />
                        @error('quote') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">Contoh symbol: <code>XAU</code>, <code>WTIOIL-SPOT</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Ambil harga live</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="grid gap-3 md:grid-cols-3">
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Symbols</span><strong class="mt-2 block text-base font-bold">{{ $result['symbols'] }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Updates</span><strong class="mt-2 block text-base font-bold">{{ strtoupper($result['updates']) }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Timestamp</span><strong class="mt-2 block text-base font-bold">{{ $result['timestamp'] ?: '-' }}</strong></article>
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr><th class="px-4 py-3 font-semibold">Symbol</th><th class="px-4 py-3 font-semibold">Rate</th><th class="px-4 py-3 font-semibold">Unit</th><th class="px-4 py-3 font-semibold">Quote</th></tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['rows'] as $row)
                                        <tr wire:key="live-commodity-{{ $row['symbol'] }}">
                                            <td class="px-4 py-4 font-mono text-[rgb(var(--app-ink))]">{{ $row['symbol'] }}</td>
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-brand-deep))]">{{ $row['rate'] }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $row['unit'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $row['quote'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">Tidak ada rate yang dikembalikan.</td></tr>
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
                    <div><p class="section-kicker">Parameter</p><h3>Request</h3></div>
                </div>
                <div class="settings-checklist">
                    <article><strong>symbols</strong><p>Comma separated symbol list, contoh <code>XAU,WTIOIL-SPOT</code>.</p></article>
                    <article><strong>updates</strong><p><code>1m</code> atau <code>10m</code>.</p></article>
                    <article><strong>quote</strong><p>Kode mata uang target, opsional.</p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
