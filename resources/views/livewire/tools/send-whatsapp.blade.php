<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Tools module</p>
            <h3>Kirim WA / Send Whatsapp</h3>
            <p>
                Kirim pesan WhatsApp lewat endpoint provider dengan basic auth dari environment aplikasi.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Auth config</span>
                <strong>{{ $hasConfiguredCredentials ? 'Ready' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Endpoint</span>
                <strong>{{ \App\Services\Tools\SendWhatsappService::ENDPOINT }}</strong>
            </div>
            <div class="mini-stat">
                <span>Method</span>
                <strong>POST</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Form kirim WhatsApp</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Isi target, pesan, dan parameter opsional lalu kirim request ke provider WhatsApp.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="send" class="settings-form">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="send_whatsapp_phone" class="form-label">Phone / JID</label>
                        <input
                            id="send_whatsapp_phone"
                            type="text"
                            wire:model="phone"
                            class="form-input font-mono"
                            placeholder="6281310307754@s.whatsapp.net"
                            autocomplete="off"
                        />
                        <p class="form-help">Format wajib: <code>6281310307754@s.whatsapp.net</code>.</p>
                        @error('phone') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <div class="flex items-start justify-between gap-4">
                            <label for="send_whatsapp_duration" class="form-label">Duration</label>
                            <span class="status-pill {{ $hasConfiguredCredentials ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasConfiguredCredentials ? 'Auth ready' : 'No auth' }}
                            </span>
                        </div>
                        <input
                            id="send_whatsapp_duration"
                            type="number"
                            min="1"
                            wire:model="duration"
                            class="form-input"
                            placeholder="86400"
                        />
                        <p class="form-help">Satuan detik. Default dokumentasi: <code>86400</code>.</p>
                        @error('duration') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field md:col-span-2">
                        <label for="send_whatsapp_message" class="form-label">Message</label>
                        <textarea
                            id="send_whatsapp_message"
                            wire:model="message"
                            rows="5"
                            class="form-input"
                            placeholder="selamat malam bro"
                        ></textarea>
                        @error('message') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="send_whatsapp_reply_message_id" class="form-label">Reply message ID</label>
                        <input
                            id="send_whatsapp_reply_message_id"
                            type="text"
                            wire:model="replyMessageId"
                            class="form-input font-mono"
                            placeholder="Kosongkan jika tidak reply"
                            autocomplete="off"
                        />
                        @error('replyMessageId') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label class="form-label">Flags</label>
                        <label class="flex items-center gap-3 rounded-[1.5rem] border border-[rgba(var(--app-border),0.7)] bg-white/70 px-4 py-3 text-sm text-[rgb(var(--app-ink))]">
                            <input type="checkbox" wire:model="isForwarded" class="size-4 rounded border-slate-300 text-slate-900 focus:ring-slate-400" />
                            <span>Tandai sebagai forwarded</span>
                        </label>
                        @error('isForwarded') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        Basic auth: <code>WHATSAPP_API_USERNAME</code> + <code>WHATSAPP_API_PASSWORD</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="send">Kirim WhatsApp</span>
                        <span wire:loading wire:target="send">Mengirim...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Status pengiriman</p>
                            <h4>{{ $result['code'] }}</h4>
                            @if ($result['message'])
                                <p>{{ $result['message'] }}</p>
                            @endif
                        </div>

                        <span class="status-pill status-pill--ready">{{ $result['status'] ?: 'Sent' }}</span>
                    </div>

                    <div class="parcel-data-grid">
                        <article>
                            <span>Message ID</span>
                            <strong>{{ $result['messageId'] ?: '-' }}</strong>
                        </article>
                        <article>
                            <span>Phone</span>
                            <strong>{{ $result['request']['phone'] }}</strong>
                        </article>
                        <article>
                            <span>Forwarded</span>
                            <strong>{{ $result['request']['is_forwarded'] ? 'Yes' : 'No' }}</strong>
                        </article>
                        <article>
                            <span>Duration</span>
                            <strong>{{ $result['request']['duration'] }} detik</strong>
                        </article>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Response JSON</span>
                            <strong>{{ $result['code'] }}</strong>
                        </div>
                        <pre>{{ $this->prettyResponse }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Dokumentasi</p>
                        <h3>Request body</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>phone</strong>
                        <p>Nomor tujuan dalam format JID WhatsApp.</p>
                    </article>
                    <article>
                        <strong>message</strong>
                        <p>Isi pesan yang akan dikirim.</p>
                    </article>
                    <article>
                        <strong>reply_message_id</strong>
                        <p>Opsional, isi jika ingin reply ke pesan tertentu.</p>
                    </article>
                    <article>
                        <strong>is_forwarded</strong>
                        <p>Boolean untuk menandai pesan sebagai forwarded.</p>
                    </article>
                    <article>
                        <strong>duration</strong>
                        <p>Waktu hidup request dalam detik.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Environment</p>
                        <h3>Konfigurasi</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Base URL</h4>
                        <p><code>{{ config('tools.whatsapp.base_url') }}</code></p>
                    </article>
                    <article>
                        <h4>Username</h4>
                        <p><code>WHATSAPP_API_USERNAME</code></p>
                    </article>
                    <article>
                        <h4>Password</h4>
                        <p><code>WHATSAPP_API_PASSWORD</code></p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
