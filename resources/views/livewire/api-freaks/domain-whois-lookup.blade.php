<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Domain WHOIS Lookup API</h3>
            <p>
                Lookup data WHOIS live domain via <code>{{ \App\Services\ApiFreaks\DomainWhoisLookupService::ENDPOINT }}</code>.
                Hasil ditampilkan sebagai tabel ringkasan, registrar, contact, dan name server.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\DomainWhoisLookupService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Lookup domain WHOIS</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan domain tanpa path. Sistem akan membersihkan skema URL bila Anda menempelkan URL penuh.
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
                                <label for="whois_domain_name" class="form-label">Domain</label>
                                <p class="form-help">Contoh: <code>apifreaks.com</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">{{ $hasSavedApiKey ? 'Key ready' : 'No key' }}</span>
                        </div>

                        <input id="whois_domain_name" type="text" wire:model="domain" class="form-input font-mono" placeholder="apifreaks.com" autocomplete="off" />
                        @error('domain') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">Parameter: <code>domainName</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cek WHOIS</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    @foreach ([
                        ['title' => 'Summary', 'rows' => $result['summary']],
                        ['title' => 'Registrar', 'rows' => $result['registrar']],
                    ] as $table)
                        <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                    <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                        <tr><th class="px-4 py-3 font-semibold">{{ $table['title'] }} Field</th><th class="px-4 py-3 font-semibold">Value</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                        @forelse ($table['rows'] as $field => $value)
                                            <tr wire:key="whois-{{ strtolower($table['title']) }}-{{ $field }}">
                                                <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $field }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ filled($value) ? $value : '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="px-4 py-4 text-center text-[rgb(var(--app-muted))]">Data tidak tersedia.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    @foreach ($result['contacts'] as $label => $contact)
                        <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                    <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                        <tr><th class="px-4 py-3 font-semibold">{{ $label }} Field</th><th class="px-4 py-3 font-semibold">Value</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                        @forelse ($contact as $field => $value)
                                            <tr wire:key="whois-contact-{{ $label }}-{{ $field }}">
                                                <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $field }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">{{ filled($value) ? $value : '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="px-4 py-4 text-center text-[rgb(var(--app-muted))]">{{ $label }} contact tidak tersedia.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    <div class="grid gap-6 lg:grid-cols-2">
                        @foreach ([
                            ['title' => 'Name Servers', 'rows' => $result['name_servers']],
                            ['title' => 'Domain Statuses', 'rows' => $result['domain_statuses']],
                        ] as $table)
                            <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                        <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                            <tr><th class="px-4 py-3 font-semibold">{{ $table['title'] }}</th></tr>
                                        </thead>
                                        <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                            @forelse ($table['rows'] as $row)
                                                <tr wire:key="{{ strtolower(str_replace(' ', '-', $table['title'])) }}-{{ $row }}"><td class="px-4 py-4 text-[rgb(var(--app-ink))]">{{ $row }}</td></tr>
                                            @empty
                                                <tr><td class="px-4 py-4 text-center text-[rgb(var(--app-muted))]">Data tidak tersedia.</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Raw JSON response</span>
                            <strong>{{ $result['summary']['domain_name'] ?? $domain }}</strong>
                        </div>
                        <pre>{{ $this->prettyResponse }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div><p class="section-kicker">Request spec</p><h3>Parameter</h3></div>
                </div>
                <div class="settings-checklist">
                    <article><strong>domainName</strong><p>Domain atau URL target yang akan dibersihkan menjadi root domain.</p></article>
                    <article><strong>Identifier API key</strong><p><code>apifreaks_provider</code></p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
