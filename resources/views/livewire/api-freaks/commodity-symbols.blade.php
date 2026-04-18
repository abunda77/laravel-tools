<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Commodity Symbols</h3>
            <p>
                Ambil daftar symbol komoditas dari <code>{{ \App\Services\ApiFreaks\CommoditySymbolsService::ENDPOINT }}</code>.
                Tabel menampilkan symbol, category, currency, unit, status, dan update interval.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\CommoditySymbolsService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Load commodity symbols</h3>
                    <p class="surface-panel__text surface-panel__text--tight">Endpoint ini tidak memerlukan parameter tambahan selain API key.</p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">{{ $errorMessage }}</div>
            @endif

            <div class="form-actions form-actions--split">
                <div class="form-inline-note">Identifier key: <code>apifreaks_provider</code></div>
                <button type="button" wire:click="run" class="primary-action" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="run">Ambil symbols</span>
                    <span wire:loading wire:target="run">Memproses...</span>
                </button>
            </div>

            @if ($result)
                <div class="result-stack">
                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr><th class="px-4 py-3 font-semibold">Symbol</th><th class="px-4 py-3 font-semibold">Name</th><th class="px-4 py-3 font-semibold">Category</th><th class="px-4 py-3 font-semibold">Currency</th><th class="px-4 py-3 font-semibold">Unit</th><th class="px-4 py-3 font-semibold">Status</th><th class="px-4 py-3 font-semibold">Update Interval</th></tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['symbols'] as $symbol)
                                        <tr wire:key="commodity-symbol-{{ $symbol['symbol'] ?? md5(json_encode($symbol)) }}">
                                            <td class="px-4 py-4 font-mono text-[rgb(var(--app-ink))]">{{ $symbol['symbol'] ?? '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-ink))]">{{ $symbol['name'] ?? '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $symbol['category'] ?? '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $symbol['currency']['code'] ?? '-' }} {{ $symbol['currency']['symbol'] ?? '' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $symbol['unit']['symbol'] ?? '-' }}{{ isset($symbol['unit']['name']) ? ' - '.$symbol['unit']['name'] : '' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $symbol['status'] ?? '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $symbol['updateInterval'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="7" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">Tidak ada symbol yang dikembalikan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Raw JSON response</span>
                            <strong>{{ $result['total'] }} symbols</strong>
                        </div>
                        <pre>{{ $this->prettyResponse }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div><p class="section-kicker">Response focus</p><h3>Kolom utama</h3></div>
                </div>
                <div class="feature-list">
                    <article><h4>Market metadata</h4><p>Currency code dan unit commodity ditampilkan langsung dalam tabel utama.</p></article>
                    <article><h4>Status symbol</h4><p>Kolom status dan update interval memudahkan filter symbol aktif.</p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
