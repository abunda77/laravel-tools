<div class="space-y-6">
    <section class="hero-panel">
        <div>
            <p class="section-kicker">Freepik API</p>
            <h3 class="hero-panel__title">Improve Prompt</h3>
            <p class="hero-panel__text">
                Tingkatkan prompt untuk kebutuhan image atau video generation melalui Freepik Improve Prompt API.
                Prompt kosong tetap diizinkan jika ingin Freepik membuat prompt kreatif dari awal.
            </p>
        </div>

        <div class="hero-panel__grid">
            <article class="stat-tile stat-tile--emerald">
                <span>Endpoint</span>
                <strong>improve-prompt</strong>
            </article>
            <article class="stat-tile stat-tile--amber">
                <span>Mode</span>
                <strong>Image / Video</strong>
            </article>
        </div>
    </section>

    <section class="surface-panel">
        <div class="surface-panel__header">
            <h3>Tingkatkan Prompt</h3>
            <p class="surface-panel__text surface-panel__text--tight">
                Masukkan prompt, pilih tipe output, dan bahasa hasil. Maksimal prompt mengikuti batas Freepik: 2500 karakter.
            </p>
        </div>

        <form wire:submit.prevent="improvePrompt" class="settings-form">
            <div class="form-grid">
                <div class="form-field form-field--wide" x-data="clipboardButton(@entangle('prompt'))">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <label for="prompt" class="form-label mb-0">Prompt</label>
                        <button
                            type="button"
                            class="app-sidebar__logout w-auto px-4 py-2 text-xs"
                            x-on:click="copy()"
                            x-bind:disabled="!value"
                        >
                            <span x-show="!copied">Copy text</span>
                            <span x-show="copied" x-cloak>Copied</span>
                        </button>
                    </div>
                    <textarea
                        id="prompt"
                        wire:model="prompt"
                        rows="7"
                        class="form-input"
                        placeholder="A beautiful landscape"
                    ></textarea>
                    <p class="form-help">Boleh dikosongkan untuk membuat prompt kreatif otomatis.</p>
                    @error('prompt') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="type" class="form-label">Tipe</label>
                    <select id="type" wire:model="type" class="form-input">
                        <option value="image">Image</option>
                        <option value="video">Video</option>
                    </select>
                    @error('type') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="language" class="form-label">Language</label>
                    <input
                        id="language"
                        wire:model="language"
                        type="text"
                        maxlength="2"
                        class="form-input"
                        placeholder="en"
                    >
                    <p class="form-help">Kode ISO 639-1, contoh: en, id, es.</p>
                    @error('language') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field form-field--wide">
                    <label for="webhookUrl" class="form-label">Webhook URL Opsional</label>
                    <input
                        id="webhookUrl"
                        wire:model="webhookUrl"
                        type="url"
                        class="form-input"
                        placeholder="https://your-app.com/webhooks/improve-prompt"
                    >
                    @error('webhookUrl') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="form-actions form-actions--split">
                <div>
                    @if (session()->has('success'))
                        <p class="form-alert form-alert--success">{{ session('success') }}</p>
                    @endif

                    @if (session()->has('error'))
                        <p class="form-alert form-alert--danger">{{ session('error') }}</p>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    @if($generatedPrompts !== [] || $taskStatus !== '')
                        <button type="button" wire:click="clearResult" class="app-sidebar__logout w-auto">
                            Reset
                        </button>
                    @endif

                    <button type="submit" class="primary-action" wire:loading.attr="disabled" wire:target="improvePrompt">
                        <span wire:loading.remove wire:target="improvePrompt">Improve Prompt</span>
                        <span wire:loading wire:target="improvePrompt">Mengirim...</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    @if($taskId)
        <section class="surface-panel" wire:poll.3s="checkTaskStatus">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="section-kicker">Task berjalan</p>
                    <h3 class="text-xl font-bold text-[rgb(var(--app-ink))]">Status: {{ $taskStatus ?: 'CREATED' }}</h3>
                    <p class="mt-2 font-mono text-sm text-[rgb(var(--app-muted))]">{{ $taskId }}</p>
                </div>
                <span class="status-pill status-pill--pending">Polling</span>
            </div>
        </section>
    @endif

    @if($generatedPrompts !== [])
        <section class="surface-panel">
            <div class="result-stack">
                @foreach($generatedPrompts as $prompt)
                    <article
                        wire:key="improved-prompt-{{ $loop->index }}"
                        x-data="clipboardButton(@js($prompt))"
                        class="rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-white/80 p-5"
                    >
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <p class="section-kicker">Result</p>
                                <h3 class="text-2xl font-bold text-[rgb(var(--app-ink))]">Improved Prompt</h3>
                            </div>
                            <button
                                type="button"
                                class="app-sidebar__logout w-auto px-4 py-2 text-xs"
                                x-on:click="copy()"
                            >
                                <span x-show="!copied">Copy text</span>
                                <span x-show="copied" x-cloak>Copied</span>
                            </button>
                        </div>
                        <p class="whitespace-pre-wrap text-sm leading-7 text-[rgb(var(--app-ink))]">{{ $prompt }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
