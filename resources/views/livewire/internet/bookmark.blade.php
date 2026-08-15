<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Internet module</p>
            <h3>Bookmark</h3>
            <p>
                Simpan tautan dari internet dan media sosial. Sistem otomatis mengambil title, gambar, dan deskripsi — seperti link preview Facebook/WhatsApp.
            </p>
        </article>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Tambah bookmark baru</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Tempelkan URL untuk melihat preview otomatis, lalu simpan.
                    </p>
                </div>
            </div>

            @if ($successMessage)
                <div class="form-alert form-alert--success">
                    {{ $successMessage }}
                </div>
            @endif

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="save" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <label for="bookmark_url" class="form-label">Link URL</label>
                        <input
                            id="bookmark_url"
                            type="url"
                            wire:model="url"
                            class="form-input"
                            placeholder="https://example.com/artikel"
                            autocomplete="off"
                            wire:loading.class="opacity-50"
                        />
                        @error('url') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    @if ($previewLoading)
                        <div class="form-field form-field--wide">
                            <div class="flex items-center gap-3 rounded-2xl bg-white/60 p-4">
                                <svg class="size-5 animate-spin text-[rgb(var(--app-accent))]" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                <span>Mengambil informasi link...</span>
                            </div>
                        </div>
                    @elseif ($previewError)
                        <div class="form-field form-field--wide">
                            <div class="form-alert form-alert--danger">
                                {{ $previewError }}
                            </div>
                        </div>
                    @elseif ($preview)
                        <div class="form-field form-field--wide">
                            <div class="overflow-hidden rounded-2xl border border-[rgb(var(--app-line))] bg-white/80">
                                @if ($preview['image_url'])
                                    <img
                                        src="{{ $preview['image_url'] }}"
                                        alt="{{ $preview['title'] }}"
                                        class="h-48 w-full object-cover"
                                        onerror="this.style.display='none'"
                                    />
                                @endif
                                <div class="space-y-2 p-4">
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <p class="text-xs font-medium text-[rgb(var(--app-muted))]">{{ $preview['domain'] }}</p>
                                            <h4 class="font-bold">{{ $preview['title'] ?? 'Tanpa judul' }}</h4>
                                        </div>
                                        @if ($preview['already_saved'] ?? false)
                                            <span class="shrink-0 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700">Sudah disimpan</span>
                                        @endif
                                    </div>
                                    @if ($preview['description'])
                                        <p class="text-sm text-[rgb(var(--app-muted))] line-clamp-2">{{ $preview['description'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="form-field form-field--wide">
                        <label for="bookmark_category" class="form-label">Kategori <span class="text-[rgb(var(--app-muted))]">(opsional)</span></label>
                        <div class="flex items-center gap-2">
                            <select wire:model="categoryId" id="bookmark_category" class="form-input">
                                <option value="">— Tanpa kategori —</option>
                                @foreach ($this->categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" wire:click="$toggle('showCategoryForm')" class="shrink-0 rounded-xl border border-[rgb(var(--app-line))] bg-white/60 px-3 py-2 text-sm hover:bg-white">
                                {{ $showCategoryForm ? 'Batal' : '+ Baru' }}
                            </button>
                        </div>
                    </div>

                    @if ($showCategoryForm)
                        <div class="form-field form-field--wide rounded-2xl border border-[rgb(var(--app-line))] bg-white/40 p-4">
                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="form-label">Nama kategori</label>
                                    <input type="text" wire:model="categoryName" class="form-input" placeholder="Coding, Berita, ..." />
                                    @error('categoryName') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="w-20">
                                    <label class="form-label">Warna</label>
                                    <input type="color" wire:model="categoryColor" class="h-10 w-full cursor-pointer rounded-xl border border-[rgb(var(--app-line))]" />
                                </div>
                                <button type="button" wire:click="saveCategory" class="primary-action shrink-0">Simpan kategori</button>
                            </div>
                        </div>
                    @endif

                    <div class="form-field form-field--wide">
                        <label for="bookmark_description" class="form-label">Deskripsi <span class="text-[rgb(var(--app-muted))]">(opsional, timpa hasil fetch)</span></label>
                        <textarea
                            id="bookmark_description"
                            wire:model="description"
                            class="form-input"
                            rows="2"
                            placeholder="Catatan pribadi tentang link ini..."
                        ></textarea>
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="save">Simpan bookmark</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Kategori</p>
                        <h3>Daftar kategori</h3>
                    </div>
                </div>

                <div class="space-y-1">
                    <button
                        wire:click="$set('filterCategoryId', null)"
                        class="flex w-full items-center justify-between rounded-2xl px-3 py-2 text-left text-sm transition hover:bg-white/50 {{ $filterCategoryId === null ? 'bg-white font-bold text-[rgb(var(--app-ink))]' : 'text-[rgb(var(--app-muted))]' }}"
                    >
                        <span>Semua bookmark</span>
                        <span class="text-xs">{{ $bookmarks->total() }}</span>
                    </button>
                    @foreach ($this->categories as $cat)
                        <div class="group flex items-center gap-1">
                            <button
                                wire:click="$set('filterCategoryId', {{ $cat->id }})"
                                class="flex flex-1 items-center justify-between rounded-2xl px-3 py-2 text-left text-sm transition hover:bg-white/50 {{ $filterCategoryId === $cat->id ? 'bg-white font-bold text-[rgb(var(--app-ink))]' : 'text-[rgb(var(--app-muted))]' }}"
                            >
                                <span class="flex items-center gap-2">
                                    @if ($cat->color)
                                        <span class="inline-block size-3 rounded-full" style="background-color: {{ $cat->color }}"></span>
                                    @endif
                                    {{ $cat->name }}
                                </span>
                                <span class="text-xs">{{ $cat->bookmarks_count }}</span>
                            </button>
                            <button
                                wire:click="deleteCategory({{ $cat->id }})"
                                wire:confirm="Hapus kategori '{{ $cat->name }}'? Bookmark di dalamnya akan menjadi tanpa kategori."
                                class="hidden px-2 py-2 text-xs text-red-500 hover:text-red-700 group-hover:block"
                            >
                                Hapus
                            </button>
                        </div>
                    @endforeach
                </div>
            </section>
        </aside>
    </div>

    <section class="surface-panel mt-6">
        <div class="surface-panel__header">
            <div>
                <h3>Daftar bookmark</h3>
            </div>
            <div class="flex items-center gap-3">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    class="form-input w-64"
                    placeholder="Cari bookmark..."
                />
            </div>
        </div>

        @if ($bookmarks->isEmpty())
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <svg class="mb-4 size-12 text-[rgb(var(--app-muted))]" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                </svg>
                <p class="text-[rgb(var(--app-muted))]">Belum ada bookmark.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-[rgb(var(--app-line))] text-left text-sm font-medium text-[rgb(var(--app-muted))]">
                            <th class="px-4 py-3">Link</th>
                            <th class="px-4 py-3">Title</th>
                            <th class="px-4 py-3 hidden md:table-cell">Kategori</th>
                            <th class="px-4 py-3 hidden lg:table-cell">Tanggal</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bookmarks as $bookmark)
                            <tr class="border-b border-[rgb(var(--app-line))] last:border-0 hover:bg-white/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($bookmark->favicon_url)
                                            <img src="{{ $bookmark->favicon_url }}" alt="" class="size-5 rounded" onerror="this.style.display='none'" />
                                        @endif
                                        <div class="min-w-0 max-w-xs">
                                            <a href="{{ $bookmark->url }}" target="_blank" rel="noopener noreferrer" class="truncate block text-sm text-[rgb(var(--app-accent))] hover:underline">
                                                {{ $bookmark->domain ?? $bookmark->url }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="min-w-0 max-w-sm">
                                        <p class="truncate font-medium">{{ $bookmark->title ?? 'Tanpa judul' }}</p>
                                        @if ($bookmark->description)
                                            <p class="truncate text-xs text-[rgb(var(--app-muted))]">{{ $bookmark->description }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    @if ($bookmark->category)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/70 px-2.5 py-0.5 text-xs font-medium">
                                            @if ($bookmark->category->color)
                                                <span class="inline-block size-2 rounded-full" style="background-color: {{ $bookmark->category->color }}"></span>
                                            @endif
                                            {{ $bookmark->category->name }}
                                        </span>
                                    @else
                                        <span class="text-xs text-[rgb(var(--app-muted))]">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 hidden lg:table-cell">
                                    <span class="text-sm text-[rgb(var(--app-muted))]">{{ $bookmark->created_at->format('d M Y') }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button
                                        wire:click="delete({{ $bookmark->id }})"
                                        wire:confirm="Hapus bookmark '{{ $bookmark->title ?? $bookmark->url }}'?"
                                        class="text-sm text-red-500 hover:text-red-700"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($bookmarks->hasPages())
                <div class="border-t border-[rgb(var(--app-line))] px-4 py-3">
                    {{ $bookmarks->links() }}
                </div>
            @endif
        @endif
    </section>
</div>