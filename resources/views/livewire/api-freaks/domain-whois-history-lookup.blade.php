<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Domain WHOIS History Lookup API</h3>
            <p>
                Audit histori WHOIS domain melalui <code>{{ \App\Services\ApiFreaks\DomainWhoisHistoryLookupService::ENDPOINT }}</code>.
                Response array historinya ditampilkan dalam tabel ringkas per record.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\DomainWhoisHistoryLookupService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Lookup WHOIS history</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Jalankan request untuk melihat histori registrar, tanggal, dan perubahan status domain.
                    </p>
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
                                <label for="whois_history_domain" class="form-label">Domain</label>
                                <p class="form-help">Contoh: <code>apifreaks.com</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">{{ $hasSavedApiKey ? 'Key ready' : 'No key' }}</span>
                        </div>

                        <input id="whois_history_domain" type="text" wire:model="domain" class="form-input font-mono" placeholder="apifreaks.com" autocomplete="off" />
                        @error('domain') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">Parameter: <code>domainName</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cek histori WHOIS</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="grid gap-3 md:grid-cols-3">
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Domain</span><strong class="mt-2 block text-base font-bold">{{ $result['domain'] }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Total records</span><strong class="mt-2 block text-base font-bold">{{ $result['total_records'] }}</strong></article>
                        <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4"><span>Rows loaded</span><strong class="mt-2 block text-base font-bold">{{ count($result['records']) }}</strong></article>
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">#</th>
                                        <th class="px-4 py-3 font-semibold">Domain</th>
                                        <th class="px-4 py-3 font-semibold">Query Time</th>
                                        <th class="px-4 py-3 font-semibold">Create</th>
                                        <th class="px-4 py-3 font-semibold">Update</th>
                                        <th class="px-4 py-3 font-semibold">Expiry</th>
                                        <th class="px-4 py-3 font-semibold">Registrar</th>
                                        <th class="px-4 py-3 font-semibold">Registrant</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @forelse ($result['records'] as $record)
                                        <tr wire:key="whois-history-row-{{ $record['num'] }}">
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-brand-deep))]">{{ $record['num'] }}</td>
                                            <td class="px-4 py-4 font-mono text-[rgb(var(--app-ink))]">{{ $record['domain_name'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $record['query_time'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $record['create_date'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $record['update_date'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $record['expiry_date'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-ink))]">{{ $record['registrar_name'] ?: '-' }}</td>
                                            <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ $record['registrant_name'] ?: '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="8" class="px-4 py-6 text-center text-[rgb(var(--app-muted))]">Tidak ada record histori.</td></tr>
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
                    <div><p class="section-kicker">Response focus</p><h3>Kolom utama</h3></div>
                </div>
                <div class="feature-list">
                    <article><h4>Registrar timeline</h4><p>Memudahkan scan pergantian registrar dan tanggal update penting.</p></article>
                    <article><h4>Registrant snapshot</h4><p>Nama registrant tiap snapshot histori ditampilkan pada tabel utama.</p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
