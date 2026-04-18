<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Domain Search API</h3>
            <p>
                Cek availability domain dengan endpoint <code>{{ \App\Services\ApiFreaks\DomainSearchService::ENDPOINT }}</code>
                dan source <code>dns</code> atau <code>whois</code>.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\DomainSearchService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Run domain availability check</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Pilih source, masukkan domain target, lalu lihat hasil status availability dalam tabel satu baris.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">{{ $errorMessage }}</div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="domain_search_domain" class="form-label">Domain</label>
                        <input id="domain_search_domain" type="text" wire:model="domain" class="form-input font-mono" placeholder="apifreaks.com" autocomplete="off" />
                        @error('domain') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="domain_search_source" class="form-label">Source</label>
                        <select id="domain_search_source" wire:model="source" class="form-input">
                            <option value="dns">dns</option>
                            <option value="whois">whois</option>
                        </select>
                        @error('source') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">Identifier key: <code>apifreaks_provider</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cek domain</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr><th class="px-4 py-3 font-semibold">Domain</th><th class="px-4 py-3 font-semibold">Source</th><th class="px-4 py-3 font-semibold">Available</th><th class="px-4 py-3 font-semibold">Message</th></tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    <tr>
                                        <td class="px-4 py-4 font-mono text-[rgb(var(--app-ink))]">{{ $result['domain'] }}</td>
                                        <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ strtoupper($result['source']) }}</td>
                                        <td class="px-4 py-4 font-semibold {{ $result['domainAvailability'] ? 'text-[rgb(var(--app-brand-deep))]' : 'text-[rgb(var(--app-accent))]' }}">{{ $result['domainAvailability'] ? 'Yes' : 'No' }}</td>
                                        <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $result['message'] ?: '-' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Raw JSON response</span>
                            <strong>{{ $result['domain'] }}</strong>
                        </div>
                        <pre>{{ $this->prettyResponse }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div><p class="section-kicker">Request</p><h3>Parameter</h3></div>
                </div>
                <div class="settings-checklist">
                    <article><strong>domain</strong><p>Nama domain yang ingin dicek.</p></article>
                    <article><strong>source</strong><p><code>dns</code> atau <code>whois</code>.</p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
