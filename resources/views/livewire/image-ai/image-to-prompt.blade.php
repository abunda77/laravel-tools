<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Image AI module</p>
            <h3>Image2Prompt</h3>
            <p>
                Upload gambar atau masukkan URL gambar untuk menganalisis dan menghasilkan prompt deskriptif.
                File upload via Freeimage.host, API key dari <code>freeimage_host</code> di Settings.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Endpoint</span>
                <strong>/tools/img2prompt</strong>
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
                    <h3>Buat Prompt dari Gambar</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Gunakan salah satu input: URL gambar publik atau upload file gambar. File upload via Freeimage.host.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="generate" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <div class="flex items-start justify-between gap-4">
                            <label for="imageUrl" class="form-label">URL Gambar</label>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>
                        <input
                            id="imageUrl"
                            wire:model="imageUrl"
                            type="url"
                            class="form-input"
                            placeholder="https://example.com/sample-image.jpg"
                            autocomplete="off"
                        />
                        @error('imageUrl') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field form-field--wide">
                        <label for="imageFile" class="form-label">Upload Gambar</label>
                        <input
                            id="imageFile"
                            wire:model="imageFile"
                            type="file"
                            accept="image/*"
                            class="form-input"
                        />
                        <p class="form-help">Maksimal 5 MB. File diupload via Freeimage.host, URL publik dikirim ke API Image2Prompt.</p>
                        @error('imageFile') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        API key: <code>downloader_provider</code> + <code>freeimage_host</code>
                    </div>

                    <div class="flex items-center gap-3">
                        @if($result || $errorMessage)
                            <button type="button" wire:click="clearResult" class="app-sidebar__logout w-auto">
                                Reset
                            </button>
                        @endif

                        <button type="submit" class="primary-action" wire:loading.attr="disabled" wire:target="generate,imageFile">
                            <span wire:loading.remove wire:target="generate">Generate Prompt</span>
                            <span wire:loading wire:target="generate">Menganalisis...</span>
                        </button>
                    </div>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="surface-panel__header">
                        <p class="section-kicker">Result</p>
                        <div class="flex items-center justify-between">
                            <h3>Generated Prompt</h3>
                            <button
                                type="button"
                                x-data="{ copied: false }"
                                x-on:click="
                                    navigator.clipboard.writeText(@js($result));
                                    copied = true;
                                    setTimeout(() => copied = false, 2000);
                                "
                                class="primary-action text-sm"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                                </svg>
                                <span x-text="copied ? 'Copied!' : 'Copy'"></span>
                            </button>
                        </div>
                    </div>

                    <article class="rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-white/80 p-5">
                        <p class="whitespace-pre-wrap text-sm leading-7 text-[rgb(var(--app-ink))]">{{ $result }}</p>
                    </article>
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
                        <strong>link</strong>
                        <p>URL gambar publik yang akan dianalisis.</p>
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
                        <h4>Prompt deskriptif</h4>
                        <p>Hasil analisis gambar berupa teks prompt yang mendeskripsikan konten gambar secara detail.</p>
                    </article>
                    <article>
                        <h4>Copy button</h4>
                        <p>Tombol copy untuk menyalin teks prompt ke clipboard.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
