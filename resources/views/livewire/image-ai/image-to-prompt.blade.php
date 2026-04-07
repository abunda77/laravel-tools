<div class="space-y-6">
    <section class="hero-panel">
        <div>
            <p class="section-kicker">Freepik API</p>
            <h3 class="hero-panel__title">Image2Prompt</h3>
            <p class="hero-panel__text">
                Upload gambar atau masukkan URL gambar untuk membuat prompt deskriptif melalui Freepik Image-to-Prompt.
                Hasil diproses sebagai task dan akan dipolling otomatis sampai prompt tersedia.
            </p>
        </div>

        <div class="hero-panel__grid">
            <article class="stat-tile stat-tile--emerald">
                <span>Endpoint</span>
                <strong>image-to-prompt</strong>
            </article>
            <article class="stat-tile stat-tile--amber">
                <span>Input</span>
                <strong>URL / Upload</strong>
            </article>
        </div>
    </section>

    <section class="surface-panel">
        <div class="surface-panel__header">
            <h3>Buat Prompt dari Gambar</h3>
            <p class="surface-panel__text surface-panel__text--tight">
                Gunakan salah satu input: URL gambar publik atau file gambar lokal. Jika keduanya diisi, URL akan diprioritaskan.
            </p>
        </div>

        <form wire:submit.prevent="generatePrompt" class="settings-form">
            <div class="form-grid">
                <div class="form-field form-field--wide">
                    <label for="imageUrl" class="form-label">URL Gambar</label>
                    <input
                        id="imageUrl"
                        wire:model="imageUrl"
                        type="url"
                        class="form-input"
                        placeholder="https://example.com/sample-image.jpg"
                    >
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
                    >
                    <p class="form-help">Maksimal 5 MB. File akan dikirim ke Freepik sebagai base64.</p>
                    @error('imageFile') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field form-field--wide">
                    <label for="webhookUrl" class="form-label">Webhook URL Opsional</label>
                    <input
                        id="webhookUrl"
                        wire:model="webhookUrl"
                        type="url"
                        class="form-input"
                        placeholder="https://your-app.com/webhooks/image-to-prompt"
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

                    <button type="submit" class="primary-action" wire:loading.attr="disabled" wire:target="generatePrompt,imageFile">
                        <span wire:loading.remove wire:target="generatePrompt">Generate Prompt</span>
                        <span wire:loading wire:target="generatePrompt">Mengirim...</span>
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
            <div class="surface-panel__header">
                <p class="section-kicker">Result</p>
                <h3>Generated Prompt</h3>
            </div>

            <div class="result-stack">
                @foreach($generatedPrompts as $prompt)
                    <article wire:key="generated-prompt-{{ $loop->index }}" class="rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-white/80 p-5">
                        <p class="whitespace-pre-wrap text-sm leading-7 text-[rgb(var(--app-ink))]">{{ $prompt }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
