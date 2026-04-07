<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Downloader module</p>
            <h3>Instagram, TikTok, dan Facebook downloader dalam satu workbench.</h3>
            <p>
                Modul pertama ini memakai parameter `link` dan `apikey`, dengan API key bisa mengambil default dari halaman Settings atau diisi manual sebagai override.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Providers</span>
                <strong>3 active</strong>
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
                    <h3>Downloader runner</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Pilih provider, masukkan link target, lalu jalankan request. Jika response mengandung URL video, tombol download akan otomatis tersedia.
                    </p>
                </div>
            </div>

            <div class="provider-switcher">
                @foreach ($providers as $providerKey => $provider)
                    <button
                        type="button"
                        wire:click="selectProvider('{{ $providerKey }}')"
                        class="provider-switcher__item {{ $selectedProvider === $providerKey ? 'is-active' : '' }}"
                    >
                        <span>{{ $provider['label'] }}</span>
                        <strong>{{ strtoupper($providerKey) }}</strong>
                    </button>
                @endforeach
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <label for="download_link" class="form-label">Link target</label>
                        <input
                            id="download_link"
                            type="url"
                            wire:model="link"
                            class="form-input"
                            placeholder="https://www.instagram.com/... atau https://vt.tiktok.com/..."
                        />
                        <p class="form-help">Masukkan link Instagram, TikTok, atau Facebook sesuai provider yang dipilih.</p>
                        @error('link') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field form-field--wide">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label for="api_key_override" class="form-label">API key override</label>
                                <p class="form-help">Opsional. Jika kosong, sistem akan memakai API key yang tersimpan di Settings.</p>
                            </div>

                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Saved key ready' : 'No saved key' }}
                            </span>
                        </div>

                        <input
                            id="api_key_override"
                            type="password"
                            wire:model="apiKeyOverride"
                            class="form-input"
                            placeholder="Kosongkan untuk menggunakan API key dari Settings"
                            autocomplete="off"
                        />
                        @error('apiKeyOverride') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        Endpoint aktif: <code>{{ $providers[$selectedProvider]['endpoint'] }}</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Run downloader</span>
                        <span wire:loading wire:target="run">Running...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="result-summary">
                        <div class="result-summary__copy">
                            <p class="section-kicker">Response summary</p>
                            <h4>{{ $result['title'] }}</h4>
                            @if ($result['authorName'])
                                <p>Author: {{ $result['authorName'] }}</p>
                            @endif
                        </div>

                        <div class="result-summary__actions">
                            @if (! empty($result['downloadOptions']))
                                @foreach ($result['downloadOptions'] as $download)
                                    <a
                                        href="{{ $download['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="primary-action"
                                    >
                                        {{ $download['label'] }}
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="result-preview">
                        @if ($result['coverUrl'])
                            <div class="result-preview__media">
                                <img src="{{ $result['coverUrl'] }}" alt="Preview cover" />
                            </div>
                        @endif

                        <div class="result-preview__meta">
                            @if (! empty($result['stats']))
                                <div class="result-stat-grid">
                                    @foreach ($result['stats'] as $label => $value)
                                        <article>
                                            <span>{{ $label }}</span>
                                            <strong>{{ is_numeric($value) ? number_format((float) $value) : $value }}</strong>
                                        </article>
                                    @endforeach
                                </div>
                            @endif

                            <div class="json-viewer">
                                <div class="json-viewer__header">
                                    <span>Raw JSON response</span>
                                    <strong>{{ $result['providerLabel'] }}</strong>
                                </div>
                                <pre>{{ $this->prettyResponse }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Supported endpoints</p>
                        <h3>Downloader sample</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    @foreach ($providers as $provider)
                        <article>
                            <strong>{{ $provider['label'] }}</strong>
                            <p><code>{{ $provider['endpoint'] }}</code></p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Response handling</p>
                        <h3>Behavior sekarang</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Download button otomatis</h4>
                        <p>Tombol akan muncul sesuai key video yang tersedia. Instagram memakai `dlink`, sedangkan Facebook bisa menampilkan `Download HD` dan `Download SD` sekaligus.</p>
                    </article>
                    <article>
                        <h4>Raw JSON tetap terlihat</h4>
                        <p>Seluruh payload response ditampilkan agar mudah dibandingkan saat nanti registry endpoint diperluas.</p>
                    </article>
                    <article>
                        <h4>API key fleksibel</h4>
                        <p>Anda bisa memakai saved setting atau override langsung untuk test provider yang berbeda.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
