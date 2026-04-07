<div class="settings-stack">
    <section class="settings-hero">
        <article class="settings-hero__intro">
            <p class="section-kicker">Request configuration</p>
            <h3>Kontrol runtime untuk HTTP executor.</h3>
            <p>
                Atur timeout, retry, dan mode queue dari satu tempat.
                API key dikelola terpisah di tab <strong>API Keys</strong>.
            </p>
        </article>

        <div class="settings-hero__stats">
            <article class="mini-stat">
                <span>Timeout</span>
                <strong>{{ $requestTimeoutSeconds }}s</strong>
            </article>

            <article class="mini-stat">
                <span>Retry</span>
                <strong>{{ $requestRetryTimes }}x</strong>
            </article>

            <article class="mini-stat">
                <span>Queue</span>
                <strong>{{ strtoupper($queueConnection) }}</strong>
            </article>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_22rem]">
        <section class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <h3>Pengaturan koneksi executor</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    Simpan parameter global untuk modul API dan custom script. Nilai di sini menjadi default runtime saat registry endpoint mulai dipasang.
                </p>
            </div>
        </div>

        @if (session('status'))
            <div class="form-alert form-alert--success">
                {{ session('status') }}
            </div>
        @endif

        <form wire:submit="save" class="settings-form">
            <div class="form-grid">
                <div class="form-field">
                    <label for="request_timeout_seconds" class="form-label">Timeout</label>
                    <input
                        id="request_timeout_seconds"
                        type="number"
                        min="1"
                        max="300"
                        wire:model="requestTimeoutSeconds"
                        class="form-input"
                    />
                    <p class="form-help">Dalam detik.</p>
                    @error('requestTimeoutSeconds') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="request_retry_times" class="form-label">Retry count</label>
                    <input
                        id="request_retry_times"
                        type="number"
                        min="0"
                        max="10"
                        wire:model="requestRetryTimes"
                        class="form-input"
                    />
                    <p class="form-help">Jumlah percobaan ulang request.</p>
                    @error('requestRetryTimes') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="request_retry_sleep_ms" class="form-label">Retry delay</label>
                    <input
                        id="request_retry_sleep_ms"
                        type="number"
                        min="0"
                        max="5000"
                        wire:model="requestRetrySleepMs"
                        class="form-input"
                    />
                    <p class="form-help">Dalam milidetik.</p>
                    @error('requestRetrySleepMs') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="queue_connection" class="form-label">Queue connection</label>
                    <select id="queue_connection" wire:model="queueConnection" class="form-input">
                        <option value="database">database</option>
                        <option value="sync">sync</option>
                    </select>
                    <p class="form-help">Dipakai nanti untuk eksekusi async task berat.</p>
                    @error('queueConnection') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-action" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="save">Save settings</span>
                    <span wire:loading wire:target="save">Saving...</span>
                </button>
            </div>
        </form>
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Runtime notes</p>
                        <h3>Default perilaku executor</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Retry dan timeout</h4>
                        <p>Nilai ini dipakai service HTTP agar perilaku request eksternal konsisten di semua modul.</p>
                    </article>

                    <article>
                        <h4>Queue connection</h4>
                        <p>Siapkan <code>database</code> untuk job berat, atau <code>sync</code> jika executor masih dijalankan inline pada tahap awal.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Suggested defaults</p>
                        <h3>Baseline yang aman</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>Timeout 30 detik</strong>
                        <p>Cukup aman untuk request provider yang variatif tanpa terlalu lama menggantung.</p>
                    </article>
                    <article>
                        <strong>Retry 1-2 kali</strong>
                        <p>Cocok untuk provider eksternal yang sesekali lambat, tanpa membuat request duplikat terlalu agresif.</p>
                    </article>
                    <article>
                        <strong>Queue <code>database</code></strong>
                        <p>Layak dipakai sebagai mode awal jika nanti OCR, transcript, atau job berat mulai dijalankan async.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
