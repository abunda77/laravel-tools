<div class="space-y-6">
    <section class="hero-panel">
        <div>
            <p class="section-kicker">Z-Image API</p>
            <h3 class="hero-panel__title">Generate Image Z</h3>
            <p class="hero-panel__text">
                Buat gambar dari deskripsi teks menggunakan Z-Image API. Masukkan prompt detail untuk hasil terbaik.
                Aspect ratio dan NSFW checker dapat disesuaikan sesuai kebutuhan.
            </p>
        </div>

        <div class="hero-panel__grid">
            <article class="stat-tile stat-tile--emerald">
                <span>Endpoint</span>
                <strong>/api/v1/jobs/createTask</strong>
            </article>
            <article class="stat-tile stat-tile--amber">
                <span>Model</span>
                <strong>z-image</strong>
            </article>
        </div>
    </section>

    <section class="surface-panel">
        <div class="surface-panel__header">
            <h3>Generate Gambar</h3>
            <p class="surface-panel__text surface-panel__text--tight">
                Masukkan prompt deskriptif, pilih aspect ratio, dan atur NSFW checker. Prompt minimal 3 karakter, maksimal 1000 karakter.
            </p>
        </div>

        <form wire:submit.prevent="generateImage" class="settings-form">
            <div class="form-grid">
                <div class="form-field form-field--wide">
                    <label for="prompt" class="form-label">Prompt</label>
                    <textarea
                        id="prompt"
                        wire:model="prompt"
                        rows="5"
                        class="form-input"
                        placeholder="A hyper-realistic, close-up portrait of a 30-year-old mixed-heritage French-Italian woman drinking coffee from a cup that says 'Z-Image × Kie AI.' Natural light. Shot on a Leica M6 with a Kodak Portra 400 film-grain aesthetic."
                    ></textarea>
                    <p class="form-help">Deskripsi detail gambar yang ingin dibuat (3-1000 karakter).</p>
                    @error('prompt') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label for="aspectRatio" class="form-label">Aspect Ratio</label>
                    <select id="aspectRatio" wire:model="aspectRatio" class="form-input">
                        <option value="1:1">1:1 (Square)</option>
                        <option value="4:3">4:3</option>
                        <option value="3:4">3:4</option>
                        <option value="16:9">16:9</option>
                        <option value="9:16">9:16</option>
                    </select>
                    @error('aspectRatio') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-field">
                    <label class="form-label">NSFW Checker</label>
                    <div class="flex items-center gap-3 mt-2">
                        <input
                            type="checkbox"
                            id="nsfwChecker"
                            wire:model="nsfwChecker"
                            class="form-checkbox"
                        />
                        <label for="nsfwChecker" class="text-sm text-[rgb(var(--app-muted))]">
                            Aktifkan filter konten NSFW
                        </label>
                    </div>
                    <p class="form-help">Centang untuk memfilter konten tidak pantas.</p>
                    @error('nsfwChecker') <p class="form-error">{{ $message }}</p> @enderror
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
                    @if($resultUrls !== [] || $taskState !== '')
                        <button type="button" wire:click="clearResult" class="app-sidebar__logout w-auto">
                            Reset
                        </button>
                    @endif

                    <button type="submit" class="primary-action" wire:loading.attr="disabled" wire:target="generateImage">
                        <span wire:loading.remove wire:target="generateImage">Generate Image</span>
                        <span wire:loading wire:target="generateImage">Mengirim...</span>
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
                    <h3 class="text-xl font-bold text-[rgb(var(--app-ink))]">Status: {{ $taskState ?: 'waiting' }}</h3>
                    <p class="mt-2 font-mono text-sm text-[rgb(var(--app-muted))]">{{ $taskId }}</p>
                </div>
                <span class="status-pill status-pill--pending">Polling</span>
            </div>
        </section>
    @endif

    @if($resultUrls !== [])
        <section class="surface-panel">
            <div class="surface-panel__header">
                <h3>Hasil Generate</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    {{ count($resultUrls) }} gambar berhasil dibuat.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($resultUrls as $index => $url)
                    <article
                        wire:key="result-url-{{ $index }}"
                        x-data="clipboardButton(@js($url))"
                        class="rounded-[1.6rem] border border-[rgb(var(--app-line))] bg-white/80 p-4 group"
                    >
                        <div class="aspect-square bg-gray-100 rounded-xl overflow-hidden mb-4">
                            <img
                                src="{{ $url }}"
                                alt="Generated image {{ $index + 1 }}"
                                class="w-full h-full object-cover"
                            />
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-xs text-[rgb(var(--app-muted))] truncate flex-1" title="{{ $url }}">
                                {{ $url }}
                            </p>
                            <button
                                type="button"
                                class="app-sidebar__logout w-auto px-4 py-2 text-xs"
                                x-on:click="copy()"
                            >
                                <span x-show="!copied">Copy URL</span>
                                <span x-show="copied" x-cloak>Copied</span>
                            </button>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
