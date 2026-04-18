<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Subdomain Lookup API</h3>
            <p>
                Enumerasi subdomain domain target melalui <code>{{ \App\Services\ApiFreaks\SubdomainLookupService::ENDPOINT }}</code>.
                Tabel utama menampilkan <code>subdomain</code>, <code>first_seen</code>, <code>last_seen</code>, dan <code>inactive_from</code>.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\SubdomainLookupService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Lookup subdomains</h3>
                    <p class="surface-panel__text surface-panel__text--tight">Gunakan root domain seperti <code>stock-bill.com</code> untuk mengambil daftar subdomain dari provider.</p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">{{ $errorMessage }}</div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label for="subdomain_lookup_domain" class="form-label">Domain</label>
                                <p class="form-help">Contoh: <code>stock-bill.com</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">{{ $hasSavedApiKey ? 'Key ready' : 'No key' }}</span>
                        </div>

                        <input id="subdomain_lookup_domain" type="text" wire:model="domain" class="form-input font-mono" placeholder="stock-bill.com" autocomplete="off" />
                        @error('domain') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">Parameter: <code>domain</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cari subdomain</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="grid gap-3 md:grid-cols-4">
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Domain</span><strong class="mt-2 block text-base font-bold">{{ $result['domain'] }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Query Time</span><strong class="mt-2 block text-base font-bold">{{ $result['query_time'] ?: '-' }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Total Records</span><strong class="mt-2 block text-base font-bold">{{ $result['total_records'] }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Pages</span><strong class="mt-2 block text-base font-bold">{{ $result['current_page'] }} / {{ $result['total_pages'] }}</strong></article>
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr><th class="px-4 py-3 font-semibold">Subdomain</th><th class="px-4 py-3 font-semibold">First Seen</th><th class="px-4 py-3 font-semibold">Last Seen</th><th class="px-4 py-3 font-semibold">Inactive From</th></tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['subdomains'] as $subdomain)
                                        <tr wire:key="subdomain-row-{{ $subdomain['subdomain'] }}">
                                            <td class="px-4 py-4 font-mono text-[rgb(var(--app-ink))]">{{ $subdomain['subdomain'] ?? '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $subdomain['first_seen'] ?? '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $subdomain['last_seen'] ?? '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $subdomain['inactive_from'] ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">Tidak ada subdomain yang dikembalikan.</td></tr>
                                    @endforelse
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
                    <div><p class="section-kicker">Response</p><h3>Data yang ditampilkan</h3></div>
                </div>
                <div class="feature-list">
                    <article><h4>Status aktif vs inactive</h4><p>Kolom <code>inactive_from</code> memudahkan identifikasi subdomain yang sudah tidak aktif.</p></article>
                    <article><h4>Timeline observasi</h4><p><code>first_seen</code> dan <code>last_seen</code> ditampilkan per baris.</p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
