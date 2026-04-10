<div class="grid gap-6 xl:grid-cols-[20rem_minmax(0,1fr)]">
    <aside class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <p class="section-kicker">Chat sessions</p>
                <h3>Riwayat ChatBot</h3>
                <p class="surface-panel__text surface-panel__text--tight">Sesi disimpan per user dan bisa dihapus kapan saja.</p>
            </div>
        </div>

        <button type="button" wire:click="startNewSession" class="primary-action mt-5 w-full">
            New chat
        </button>

        <div class="mt-6 space-y-3">
            @forelse ($sessions as $session)
                <article class="rounded-2xl border border-[rgb(var(--app-line))] bg-white/75 p-4">
                    <button type="button" wire:click="selectSession({{ $session->id }})" class="block w-full text-left">
                        <span class="block text-sm font-bold text-[rgb(var(--app-ink))]">{{ $session->title }}</span>
                        <span class="mt-2 block text-xs uppercase tracking-[0.18em] text-[rgb(var(--app-muted))]">
                            {{ $session->provider }} / {{ $session->model_name }}
                        </span>
                    </button>

                    <button type="button" wire:click="deleteSession({{ $session->id }})" wire:confirm="Hapus sesi chat ini?" class="mt-3 text-sm font-semibold text-rose-600">
                        Delete
                    </button>
                </article>
            @empty
                <p class="text-sm leading-6 text-[rgb(var(--app-muted))]">Belum ada sesi chat.</p>
            @endforelse
        </div>
    </aside>

    <section class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <p class="section-kicker">Workspace assistant</p>
                <h3>ChatBot multi provider</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    Pilih provider dan model, lampirkan dokumen atau image, lalu gunakan pencarian web untuk jawaban dengan source.
                </p>
            </div>
        </div>

        @if (session('chatbot_status'))
            <div class="form-alert form-alert--success">{{ session('chatbot_status') }}</div>
        @endif

        @if (session('chatbot_error'))
            <div class="form-alert form-alert--danger">{{ session('chatbot_error') }}</div>
        @endif

        <div class="mt-6 space-y-4">
            @forelse ($messages as $message)
                <article class="rounded-2xl border border-[rgb(var(--app-line))] bg-[rgb(var(--app-bg))]/55 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <strong class="text-sm uppercase tracking-[0.18em] text-[rgb(var(--app-ink))]">{{ $message->role }}</strong>
                        @if ($message->model_name)
                            <span class="text-xs uppercase tracking-[0.18em] text-[rgb(var(--app-muted))]">{{ $message->provider }} / {{ $message->model_name }}</span>
                        @endif
                    </div>

                    <div class="mt-3 whitespace-pre-wrap text-sm leading-7 text-[rgb(var(--app-ink))]">{{ $message->content }}</div>

                    @if ($message->attachments->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($message->attachments as $attachment)
                                <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-[rgb(var(--app-muted))]">
                                    {{ $attachment->original_name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    @if ($message->citations->isNotEmpty())
                        <div class="mt-5 space-y-2">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-[rgb(var(--app-muted))]">Sources</p>
                            @foreach ($message->citations as $citation)
                                <a href="{{ $citation->url }}" target="_blank" rel="noreferrer" class="block rounded-2xl bg-white/80 px-4 py-3 text-sm text-[rgb(var(--app-ink))] underline decoration-[rgb(var(--app-brand))]/40 underline-offset-4">
                                    [{{ $citation->position }}] {{ $citation->title ?: $citation->url }}
                                    @if ($citation->snippet)
                                        <span class="mt-1 block text-xs leading-5 text-[rgb(var(--app-muted))]">{{ $citation->snippet }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-[rgb(var(--app-line))] p-6 text-sm leading-7 text-[rgb(var(--app-muted))]">
                    Mulai chat baru. Citation akan tampil di bawah jawaban saat web search aktif atau provider mengembalikan source.
                </div>
            @endforelse
        </div>

        <form wire:submit="send" class="settings-form">
            <div class="form-grid">
                <div class="form-field">
                    <label for="chat_provider" class="form-label">Provider</label>
                    <select id="chat_provider" wire:model.live="provider" class="form-input">
                        <option value="openai">OpenAI</option>
                        <option value="gemini">Gemini</option>
                        <option value="anthropic">Claude</option>
                        <option value="perplexity">Perplexity</option>
                    </select>
                    @error('provider') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="chat_model" class="form-label">Model</label>
                    <select id="chat_model" wire:model="modelId" class="form-input">
                        <option value="">Pilih model</option>
                        @foreach (($modelsByProvider[$provider] ?? collect()) as $model)
                            <option value="{{ $model->id }}">{{ $model->label }} ({{ $model->name }})</option>
                        @endforeach
                    </select>
                    @error('modelId') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field form-field--wide">
                    <label for="chat_prompt" class="form-label">Message</label>
                    <textarea id="chat_prompt" wire:model="prompt" rows="5" class="form-input" placeholder="Tulis pertanyaan atau instruksi analisis..."></textarea>
                    @error('prompt') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="chat_uploads" class="form-label">Dokumen / Image</label>
                    <input id="chat_uploads" type="file" wire:model="uploads" class="form-input" multiple>
                    <p class="form-help">PDF, DOC, DOCX, TXT, CSV, JSON, JPG, PNG, WEBP. Maks 12 MB per file.</p>
                    @error('uploads.*') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label class="form-label">Citation</label>
                    <label class="checkbox-row mt-3">
                        <input type="checkbox" wire:model="webSearch">
                        <span>Gunakan web search untuk source</span>
                    </label>
                    <p class="form-help">OpenAI, Gemini, dan Claude memakai Perplexity Search sebagai source context. Perplexity memakai Sonar citations.</p>
                </div>
            </div>

            <div class="form-actions form-actions--split">
                <p class="form-inline-note">
                    Simpan API key dengan name <code>openai</code>, <code>gemini</code>, <code>anthropic</code>, dan <code>perplexity</code>.
                </p>
                <button type="submit" class="primary-action" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="send">Send</span>
                    <span wire:loading wire:target="send">Processing...</span>
                </button>
            </div>
        </form>
    </section>
</div>
