<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Internet module</p>
            <h3>Kurs mata uang real-time dari API.co.id.</h3>
            <p>
                Modul ini memanggil endpoint <code>/currency/exchange-rate</code> dengan header <code>x-api-co-id</code>
                dan parameter query <code>pair</code> seperti <code>USDIDR</code> atau <code>EURIDR</code>.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Method</span>
                <strong>GET</strong>
            </div>
            <div class="mini-stat">
                <span>Endpoint</span>
                <strong>/currency/exchange-rate</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Currency exchange runner</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan pair mata uang 6 huruf, lalu jalankan request. API key diambil dari Settings dengan identifier
                        <code>apicoid_provider</code>.
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
                                <label for="currency_pair" class="form-label">Currency pair</label>
                                <p class="form-help">Format 6 huruf tanpa pemisah, contoh <code>USDIDR</code>, <code>SGDIDR</code>, atau <code>EURUSD</code>.</p>
                            </div>

                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Saved key ready' : 'No saved key' }}
                            </span>
                        </div>

                        <input
                            id="currency_pair"
                            type="text"
                            wire:model="pair"
                            class="form-input font-mono"
                            placeholder="USDIDR"
                            autocomplete="off"
                            spellcheck="false"
                        />
                        @error('pair') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        Header auth: <code>x-api-co-id</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cek kurs</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                @php
                    $updatedAt = filled($result['updatedAt'] ?? null) ? \Illuminate\Support\Carbon::createFromTimestampMs((int) $result['updatedAt'])->format('d M Y H:i:s') : null;
                    $lastDataUpdatedAt = filled($result['lastDataUpdatedAt'] ?? null) ? \Illuminate\Support\Carbon::createFromTimestampMs((int) $result['lastDataUpdatedAt'])->format('d M Y H:i:s') : null;
                @endphp

                <div class="result-stack">
                    <div class="result-summary">
                        <div class="result-summary__copy">
                            <p class="section-kicker">Exchange result</p>
                            <h4>{{ $result['pair'] }}</h4>
                            <p>{{ $result['message'] }}</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-4">
                            <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                    <span>Pair</span>
                                    <strong class="mt-2 block text-xl font-bold tracking-tight">{{ $result['pair'] }}</strong>
                                </article>
                                <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                    <span>Rate</span>
                                    <strong class="mt-2 block text-xl font-bold tracking-tight">{{ is_numeric($result['rate']) ? number_format((float) $result['rate'], 2) : $result['rate'] }}</strong>
                                </article>
                                @if ($updatedAt)
                                    <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                        <span>Updated At</span>
                                        <strong class="mt-2 block text-base font-bold leading-7">{{ $updatedAt }}</strong>
                                    </article>
                                @endif
                                @if ($lastDataUpdatedAt)
                                    <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                        <span>Last Data Updated</span>
                                        <strong class="mt-2 block text-base font-bold leading-7">{{ $lastDataUpdatedAt }}</strong>
                                    </article>
                                @endif
                        </div>

                        <div class="json-viewer w-full">
                            <div class="json-viewer__header">
                                <span>Raw JSON response</span>
                                <strong class="break-all text-right">{{ $result['endpoint'] }}</strong>
                            </div>
                            <pre class="min-h-[20rem] w-full whitespace-pre-wrap break-words">{{ $this->prettyResponse }}</pre>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Request spec</p>
                        <h3>Kontrak endpoint</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>Base URL</strong>
                        <p><code>https://use.api.co.id</code></p>
                    </article>
                    <article>
                        <strong>Endpoint</strong>
                        <p><code>/currency/exchange-rate</code></p>
                    </article>
                    <article>
                        <strong>Header</strong>
                        <p><code>x-api-co-id</code></p>
                    </article>
                    <article>
                        <strong>Identifier API key</strong>
                        <p><code>apicoid_provider</code></p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Contoh pair</p>
                        <h3>Yang umum dipakai</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>USDIDR</h4>
                        <p>Dolar Amerika ke Rupiah Indonesia.</p>
                    </article>
                    <article>
                        <h4>SGDIDR</h4>
                        <p>Dolar Singapura ke Rupiah Indonesia.</p>
                    </article>
                    <article>
                        <h4>EURUSD</h4>
                        <p>Euro ke Dolar Amerika.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
