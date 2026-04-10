<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Internet module</p>
            <h3>Proxy source viewer untuk workflow validate.</h3>
            <p>
                Tool ini mengambil daftar proxy dari repository GitHub publik dengan format
                <code>IP:PORT | PROTOCOL | COUNTRY | ANONYMITY</code>, lalu menampilkan hasil check validitas
                langsung di tabel.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Source</span>
                <strong>{{ $selectedSource }}</strong>
            </div>
            <div class="mini-stat">
                <span>Rows</span>
                <strong>{{ $this->filteredProxyCount }} / {{ $this->proxyCount }}</strong>
            </div>
            <div class="mini-stat">
                <span>Selected</span>
                <strong>{{ $this->selectedCount }}</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.4fr)_22rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Proxy validate dataset</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Pilih source, muat daftar proxy, lalu jalankan check validitas per baris atau untuk semua row.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="fetchProxies" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <label for="proxy_source" class="form-label">Proxy source</label>
                        <select id="proxy_source" wire:model="selectedSource" class="form-input">
                            @foreach ($sourceOptions as $sourceOption)
                                <option value="{{ $sourceOption }}">{{ $sourceOption }}</option>
                            @endforeach
                        </select>
                        @error('selectedSource') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        Source utama: <code>anutmagang/Free-HighQuality-Proxy-Socks</code>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        @if ($this->proxyCount > 0)
                            <button
                                type="button"
                                wire:click="validateSelected"
                                wire:loading.attr="disabled"
                                @class([
                                    'rounded-full border px-5 py-3 text-sm font-semibold transition',
                                    'border-[rgb(var(--app-line))] text-[rgb(var(--app-muted))] cursor-not-allowed' => $this->selectedCount === 0,
                                    'border-[rgb(var(--app-line))] text-[rgb(var(--app-ink))] hover:border-emerald-300 hover:bg-emerald-50' => $this->selectedCount > 0,
                                ])
                                @disabled($this->selectedCount === 0)
                            >
                                <span wire:loading.remove wire:target="validateSelected">Check selected</span>
                                <span wire:loading wire:target="validateSelected">Checking...</span>
                            </button>
                        @endif

                        <button type="submit" class="primary-action" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="fetchProxies">Load proxies</span>
                            <span wire:loading wire:target="fetchProxies">Memuat...</span>
                        </button>
                    </div>
                </div>
            </form>

            @if ($this->proxyCount > 0)
                <div class="mb-4 rounded-[1.2rem] border border-[rgb(var(--app-line))] bg-[rgba(var(--app-surface-strong),0.35)] px-4 py-3 text-xs text-[rgb(var(--app-muted))]">
                    Filter di header tabel akan mempersempit dataset yang terlihat. Gunakan checkbox untuk memilih row tertentu,
                    lalu jalankan <code>Check selected</code> agar proses validasi hanya berjalan pada pilihan user.
                </div>

                <div class="mb-4 grid gap-3 xl:grid-cols-[minmax(0,1fr)_auto]">
                    <div class="rounded-[1.2rem] border border-[rgb(var(--app-line))] bg-white/80 px-4 py-3">
                        <div>{!! $this->progressMarkup() !!}</div>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <button
                            type="button"
                            wire:click="selectVisibleValidOnly"
                            class="rounded-full border border-[rgb(var(--app-line))] px-4 py-2 text-xs font-semibold text-[rgb(var(--app-ink))] transition hover:border-emerald-300 hover:bg-emerald-50"
                        >
                            Select visible valid only
                        </button>
                        <button
                            type="button"
                            wire:click="selectVisibleUncheckedOnly"
                            class="rounded-full border border-[rgb(var(--app-line))] px-4 py-2 text-xs font-semibold text-[rgb(var(--app-ink))] transition hover:border-amber-300 hover:bg-amber-50"
                        >
                            Select visible unchecked only
                        </button>
                        <button
                            type="button"
                            wire:click="exportSelectedCsv"
                            wire:loading.attr="disabled"
                            @class([
                                'rounded-full border px-4 py-2 text-xs font-semibold transition',
                                'border-[rgb(var(--app-line))] text-[rgb(var(--app-muted))] cursor-not-allowed' => $this->selectedCount === 0,
                                'border-[rgb(var(--app-line))] text-[rgb(var(--app-ink))] hover:border-sky-300 hover:bg-sky-50' => $this->selectedCount > 0,
                            ])
                            @disabled($this->selectedCount === 0)
                        >
                            Export CSV
                        </button>
                        <button
                            type="button"
                            wire:click="exportSelectedTxt"
                            wire:loading.attr="disabled"
                            @class([
                                'rounded-full border px-4 py-2 text-xs font-semibold transition',
                                'border-[rgb(var(--app-line))] text-[rgb(var(--app-muted))] cursor-not-allowed' => $this->selectedCount === 0,
                                'border-[rgb(var(--app-line))] text-[rgb(var(--app-ink))] hover:border-sky-300 hover:bg-sky-50' => $this->selectedCount > 0,
                            ])
                            @disabled($this->selectedCount === 0)
                        >
                            Export TXT
                        </button>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[1.75rem] border border-[rgb(var(--app-line))] bg-white/80">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-sm">
                            <thead class="bg-[rgba(var(--app-surface-strong),0.55)]">
                                <tr class="text-left text-xs uppercase tracking-[0.22em] text-[rgb(var(--app-muted))]">
                                    <th class="px-4 py-4 font-semibold">
                                        <button
                                            type="button"
                                            wire:click="toggleSelectAllFiltered"
                                            class="inline-flex size-5 items-center justify-center rounded border border-[rgb(var(--app-line))] bg-white text-[rgb(var(--app-ink))]"
                                            title="Toggle select all filtered rows"
                                        >
                                            @if ($this->allFilteredSelected)
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-3.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
                                                </svg>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-5 py-4 font-semibold">Address</th>
                                    <th class="px-5 py-4 font-semibold">Protocol</th>
                                    <th class="px-5 py-4 font-semibold">Country</th>
                                    <th class="px-5 py-4 font-semibold">Anonymity</th>
                                    <th class="px-5 py-4 font-semibold">Status</th>
                                    <th class="px-5 py-4 font-semibold text-right">Action</th>
                                </tr>
                                <tr class="border-t border-[rgb(var(--app-line))] bg-white/70">
                                    <th class="px-4 py-3"></th>
                                    <th class="px-5 py-3">
                                        <input
                                            type="text"
                                            wire:model.live.debounce.300ms="filterAddress"
                                            class="w-full rounded-xl border border-[rgb(var(--app-line))] bg-white px-3 py-2 text-sm text-[rgb(var(--app-ink))] outline-none transition focus:border-emerald-300"
                                            placeholder="Filter address"
                                        />
                                    </th>
                                    <th class="px-5 py-3">
                                        <select wire:model.live="filterProtocol" class="w-full rounded-xl border border-[rgb(var(--app-line))] bg-white px-3 py-2 text-sm text-[rgb(var(--app-ink))] outline-none transition focus:border-emerald-300">
                                            @foreach ($this->protocolOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-5 py-3">
                                        <select wire:model.live="filterCountry" class="w-full rounded-xl border border-[rgb(var(--app-line))] bg-white px-3 py-2 text-sm text-[rgb(var(--app-ink))] outline-none transition focus:border-emerald-300">
                                            @foreach ($this->countryOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-5 py-3">
                                        <select wire:model.live="filterAnonymity" class="w-full rounded-xl border border-[rgb(var(--app-line))] bg-white px-3 py-2 text-sm text-[rgb(var(--app-ink))] outline-none transition focus:border-emerald-300">
                                            @foreach ($this->anonymityOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-5 py-3">
                                        <select wire:model.live="filterStatus" class="w-full rounded-xl border border-[rgb(var(--app-line))] bg-white px-3 py-2 text-sm text-[rgb(var(--app-ink))] outline-none transition focus:border-emerald-300">
                                            @foreach ($this->statusOptions as $option)
                                                <option value="{{ $option }}">{{ $option }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                    <th class="px-5 py-3 text-right text-xs text-[rgb(var(--app-muted))]">
                                        {{ $this->filteredProxyCount }} visible
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgb(var(--app-line))]">
                                @foreach ($this->filteredProxies as $proxy)
                                    <tr wire:key="proxy-{{ $proxy['address'] }}" class="align-top">
                                        <td class="px-4 py-4">
                                            <label class="inline-flex cursor-pointer items-center justify-center">
                                                <input
                                                    type="checkbox"
                                                    wire:model.live="selectedAddresses"
                                                    value="{{ $proxy['address'] }}"
                                                    class="size-4 rounded border-[rgb(var(--app-line))] text-emerald-600 focus:ring-emerald-400"
                                                />
                                            </label>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="space-y-1">
                                                <p class="font-mono text-sm font-semibold text-[rgb(var(--app-ink))]">{{ $proxy['address'] }}</p>
                                                <p class="text-xs text-[rgb(var(--app-muted))]">{{ $proxy['host'] }} - Port {{ $proxy['port'] }}</p>
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-[rgb(var(--app-ink))]">{{ $proxy['protocol'] }}</td>
                                        <td class="px-5 py-4 text-[rgb(var(--app-ink))]">{{ $proxy['country'] }}</td>
                                        <td class="px-5 py-4 text-[rgb(var(--app-ink))]">{{ $proxy['anonymity'] }}</td>
                                        <td class="px-5 py-4">
                                            <div class="space-y-2">
                                                <span class="status-pill {{ $this->statusPillClass($proxy['status']) }}">{{ $proxy['status'] }}</span>

                                                @if ($proxy['response_time_ms'])
                                                    <p class="text-xs text-[rgb(var(--app-muted))]">{{ $proxy['response_time_ms'] }} ms</p>
                                                @endif

                                                @if ($proxy['detected_ip'])
                                                    <p class="text-xs text-[rgb(var(--app-muted))]">IP {{ $proxy['detected_ip'] }}</p>
                                                @endif

                                                @if ($proxy['error_message'])
                                                    <p class="max-w-[16rem] text-xs text-rose-600">{{ $proxy['error_message'] }}</p>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-4">
                                            <div class="flex justify-end gap-2" x-data="clipboardButton(@js($proxy['address']))">
                                                <button
                                                    type="button"
                                                    wire:click="validateProxy(@js($proxy['address']))"
                                                    wire:loading.attr="disabled"
                                                    class="inline-flex items-center rounded-xl border border-[rgb(var(--app-line))] p-2 text-[rgb(var(--app-muted))] transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700"
                                                    title="Check {{ $proxy['address'] }}"
                                                >
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 12S5.25 5.25 12 5.25 21.75 12 21.75 12 18.75 18.75 12 18.75 2.25 12 2.25 12Z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15.75a3.75 3.75 0 1 0 0-7.5 3.75 3.75 0 0 0 0 7.5Z" />
                                                    </svg>
                                                </button>

                                                <button
                                                    type="button"
                                                    x-on:click="copy()"
                                                    class="inline-flex items-center rounded-xl border border-[rgb(var(--app-line))] p-2 text-[rgb(var(--app-muted))] transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700"
                                                    title="Copy {{ $proxy['address'] }}"
                                                >
                                                    <svg x-show="! copied" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-4">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125H4.875A1.125 1.125 0 0 1 3.75 20.625V7.125C3.75 6.504 4.254 6 4.875 6H8.25m7.5 11.25h3.375c.621 0 1.125-.504 1.125-1.125V3.375A1.125 1.125 0 0 0 19.125 2.25H9.375A1.125 1.125 0 0 0 8.25 3.375V6" />
                                                    </svg>
                                                    <svg x-show="copied" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-4 text-emerald-700">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4.5 12.75 6 6 9-13.5" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @elseif ($hasLoaded)
                <div class="rounded-[1.5rem] border border-dashed border-[rgb(var(--app-line))] px-6 py-10 text-center text-sm text-[rgb(var(--app-muted))]">
                    Tidak ada baris proxy valid yang berhasil diparse dari source terpilih.
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Reference</p>
                        <h3>Cakupan awal</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>Input format</strong>
                        <p><code>IP:PORT | PROTOCOL | COUNTRY | ANONYMITY</code></p>
                    </article>
                    <article>
                        <strong>Supported protocol</strong>
                        <p><code>HTTP</code>, <code>SOCKS4</code>, <code>SOCKS5</code></p>
                    </article>
                    <article>
                        <strong>Action</strong>
                        <p>Gunakan quick select, bulk check, lalu export row terpilih ke CSV atau TXT.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Status meaning</p>
                        <h3>Interpretasi hasil</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Valid</h4>
                        <p>Proxy berhasil menerima response dari endpoint uji dalam batas timeout.</p>
                    </article>
                    <article>
                        <h4>Invalid</h4>
                        <p>Proxy gagal connect, timeout, atau endpoint uji tidak mengembalikan response sukses.</p>
                    </article>
                    <article>
                        <h4>Unchecked</h4>
                        <p>Belum pernah dijalankan check validitas sejak data source dimuat.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
