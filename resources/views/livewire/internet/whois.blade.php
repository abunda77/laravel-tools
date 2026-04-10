<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Internet module</p>
            <h3>Whois</h3>
            <p>
                Lihat informasi registrasi domain dari endpoint Whois. API key otomatis memakai <code>downloader_provider</code> dari Settings.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Endpoint</span>
                <strong>/internet/whois</strong>
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
                    <h3>Lookup domain</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan domain tanpa path. Sistem akan membersihkan skema URL jika Anda menempelkan URL lengkap.
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
                                <label for="whois_domain" class="form-label">Domain</label>
                                <p class="form-help">Contoh: <code>produkmastah.com</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>

                        <input
                            id="whois_domain"
                            type="text"
                            wire:model="domain"
                            class="form-input font-mono"
                            placeholder="produkmastah.com"
                            autocomplete="off"
                        />
                        @error('domain') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        API key: <code>downloader_provider</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cek Whois</span>
                        <span wire:loading wire:target="run">Memeriksa...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="whois-summary">
                        <div>
                            <p class="section-kicker">Domain</p>
                            <h4>{{ $result['domain'] }}</h4>
                        </div>
                        <span class="status-pill status-pill--ready">Whois loaded</span>
                    </div>

                    @if (! empty($result['summary']))
                        <div class="whois-facts">
                            @foreach ($result['summary'] as $label => $value)
                                <article wire:key="whois-summary-{{ $label }}">
                                    <span>{{ $label }}</span>
                                    <strong>{{ $value }}</strong>
                                </article>
                            @endforeach
                        </div>
                    @endif

                    <section class="whois-record">
                        <div class="whois-record__header">
                            <div>
                                <p class="section-kicker">WHOIS record</p>
                                <h3>Raw result</h3>
                            </div>
                            <strong>{{ $result['domain'] }}</strong>
                        </div>
                        <pre>{{ $result['result'] }}</pre>
                    </section>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Data JSON</span>
                            <strong>{{ $result['domain'] }}</strong>
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
                        <strong>domain</strong>
                        <p>Domain target, contoh <code>produkmastah.com</code>.</p>
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
                        <h4>Ringkasan domain</h4>
                        <p>Registrar, tanggal registrasi, tanggal kedaluwarsa, DNSSEC, dan name server diringkas dari raw Whois jika tersedia.</p>
                    </article>
                    <article>
                        <h4>Raw Whois</h4>
                        <p>Isi <code>data.result</code> ditampilkan lengkap dengan line break agar mudah dibaca dan diaudit.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
