<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Tools module</p>
            <h3>Jadwal libur nasional Indonesia dari API.co.id.</h3>
            <p>
                Modul ini memanggil endpoint <code>/holidays/indonesia</code>, <code>/holidays/indonesia/check/date</code>,
                dan <code>/holidays/indonesia/upcoming</code> dengan header <code>x-api-co-id</code> dari API key
                <code>apicoid_provider</code>.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Method</span>
                <strong>GET</strong>
            </div>
            <div class="mini-stat">
                <span>Base URL</span>
                <strong>use.api.co.id</strong>
            </div>
        </div>
    </section>

    @if ($errorMessage)
        <div class="form-alert form-alert--danger">
            {{ $errorMessage }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <div class="space-y-6">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <h3>Daftar libur nasional per tahun</h3>
                        <p class="surface-panel__text surface-panel__text--tight">
                            Tampilkan hari libur nasional, cuti bersama, dan hari kebesaran untuk tahun tertentu.
                        </p>
                    </div>
                </div>

                <form wire:submit="loadHolidays" class="settings-form">
                    <div class="form-grid">
                        <div class="form-field form-field--wide">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <label for="holiday_year" class="form-label">Tahun</label>
                                    <p class="form-help">Rentang 2000 sampai 2100.</p>
                                </div>

                                <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                    {{ $hasSavedApiKey ? 'Saved key ready' : 'No saved key' }}
                                </span>
                            </div>

                            <input
                                id="holiday_year"
                                type="number"
                                wire:model="year"
                                class="form-input font-mono"
                                placeholder="{{ now()->year }}"
                                min="2000"
                                max="2100"
                                autocomplete="off"
                            />
                            @error('year') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-actions form-actions--split">
                        <div class="form-inline-note">
                            Endpoint: <code>/holidays/indonesia</code>
                        </div>

                        <button type="submit" class="primary-action" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="loadHolidays">Tampilkan libur</span>
                            <span wire:loading wire:target="loadHolidays">Memproses...</span>
                        </button>
                    </div>
                </form>

                @if ($holidays)
                    <div class="result-stack">
                        <div class="result-summary">
                            <div class="result-summary__copy">
                                <p class="section-kicker">Holiday list</p>
                                <h4>Tahun {{ $holidays['year'] }}</h4>
                                <p>{{ $holidays['total'] }} hari libur ditemukan.</p>
                            </div>
                        </div>

                        @if ($holidays['total'] > 0)
                            <div class="space-y-3">
                                @foreach ($holidays['items'] as $holiday)
                                    <article wire:key="holiday-{{ $loop->index }}" class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <strong class="block text-base font-bold leading-7">{{ $holiday['name'] }}</strong>
                                                <span class="text-sm text-[rgb(var(--app-muted))]">{{ $holiday['date'] }} &middot; {{ $holiday['type'] }}</span>
                                                <div class="mt-2 flex flex-wrap gap-2">
                                                    @if ($holiday['is_joint_holiday'])
                                                        <span class="status-pill status-pill--ready">Cuti bersama</span>
                                                    @endif
                                                    @if ($holiday['is_observance'])
                                                        <span class="status-pill">Hari kebesaran</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="form-alert">
                                Tidak ada data libur untuk tahun {{ $holidays['year'] }}.
                            </div>
                        @endif

                        <div class="json-viewer w-full">
                            <div class="json-viewer__header">
                                <span>Raw JSON response</span>
                                <strong class="break-all text-right">{{ $holidays['endpoint'] }}</strong>
                            </div>
                            <pre class="min-h-[12rem] w-full whitespace-pre-wrap break-words">{{ $this->holidaysJson }}</pre>
                        </div>
                    </div>
                @endif
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <h3>Cek tanggal tertentu</h3>
                        <p class="surface-panel__text surface-panel__text--tight">
                            Cek apakah sebuah tanggal adalah libur, cuti bersama, atau hari kebesaran.
                        </p>
                    </div>
                </div>

                <form wire:submit="verifyDate" class="settings-form">
                    <div class="form-grid">
                        <div class="form-field form-field--wide">
                            <label for="holiday_check_date" class="form-label">Tanggal</label>
                            <input
                                id="holiday_check_date"
                                type="date"
                                wire:model="checkDate"
                                class="form-input font-mono"
                                autocomplete="off"
                            />
                            @error('checkDate') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-actions form-actions--split">
                        <div class="form-inline-note">
                            Endpoint: <code>/holidays/indonesia/check/date</code>
                        </div>

                        <button type="submit" class="primary-action" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="verifyDate">Cek tanggal</span>
                            <span wire:loading wire:target="verifyDate">Memproses...</span>
                        </button>
                    </div>
                </form>

                @if ($checkedDate)
                    <div class="result-stack">
                        <div class="result-summary">
                            <div class="result-summary__copy">
                                <p class="section-kicker">Date check</p>
                                <h4>{{ $checkedDate['date'] }} &middot; {{ $checkedDate['day_of_week'] }}</h4>
                                <p>
                                    @if ($checkedDate['is_holiday'])
                                        <span class="status-pill status-pill--ready">Hari libur</span>
                                    @else
                                        <span class="status-pill status-pill--pending">Bukan libur</span>
                                    @endif
                                    @if ($checkedDate['is_weekend'])
                                        <span class="status-pill">Akhir pekan</span>
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if ($checkedDate['holidays'])
                            <div class="space-y-2">
                                @foreach ($checkedDate['holidays'] as $holiday)
                                    <article wire:key="checked-{{ $loop->index }}" class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-3">
                                        <strong class="block text-base font-bold leading-7">{{ $holiday['name'] }}</strong>
                                    </article>
                                @endforeach
                            </div>
                        @endif

                        <div class="json-viewer w-full">
                            <div class="json-viewer__header">
                                <span>Raw JSON response</span>
                                <strong class="break-all text-right">{{ $checkedDate['endpoint'] }}</strong>
                            </div>
                            <pre class="min-h-[10rem] w-full whitespace-pre-wrap break-words">{{ $this->checkedDateJson }}</pre>
                        </div>
                    </div>
                @endif
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <h3>Libur mendatang</h3>
                        <p class="surface-panel__text surface-panel__text--tight">
                            Tampilkan daftar hari libur dari hari ini ke depan.
                        </p>
                    </div>
                </div>

                <form wire:submit="loadUpcomingHolidays" class="settings-form">
                    <div class="form-grid">
                        <div class="form-field form-field--wide">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <label for="holiday_upcoming_limit" class="form-label">Jumlah maksimal</label>
                                    <p class="form-help">Default 10, maksimal 50.</p>
                                </div>
                            </div>

                            <input
                                id="holiday_upcoming_limit"
                                type="number"
                                wire:model="upcomingLimit"
                                class="form-input font-mono"
                                placeholder="10"
                                min="1"
                                max="50"
                                autocomplete="off"
                            />
                            @error('upcomingLimit') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="form-actions form-actions--split">
                        <div class="form-inline-note">
                            Endpoint: <code>/holidays/indonesia</code>
                        </div>

                        <button type="submit" class="primary-action" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="loadUpcomingHolidays">Tampilkan mendatang</span>
                            <span wire:loading wire:target="loadUpcomingHolidays">Memproses...</span>
                        </button>
                    </div>
                </form>

                @if ($upcoming)
                    <div class="result-stack">
                        <div class="result-summary">
                            <div class="result-summary__copy">
                                <p class="section-kicker">Upcoming holidays</p>
                                <h4>{{ $upcoming['total'] }} libur mendatang</h4>
                            </div>
                        </div>

                        @if ($upcoming['total'] > 0)
                            <div class="space-y-3">
                                @foreach ($upcoming['items'] as $holiday)
                                    <article wire:key="upcoming-{{ $loop->index }}" class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <strong class="block text-base font-bold leading-7">{{ $holiday['name'] }}</strong>
                                                <span class="text-sm text-[rgb(var(--app-muted))]">{{ $holiday['date'] }} &middot; {{ $holiday['type'] }}</span>
                                            </div>
                                            <span class="status-pill status-pill--ready">{{ $holiday['days_until'] }} hari lagi</span>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="form-alert">
                                Tidak ada data libur mendatang untuk limit {{ $upcoming['limit'] }}.
                            </div>
                        @endif

                        <div class="json-viewer w-full">
                            <div class="json-viewer__header">
                                <span>Raw JSON response</span>
                                <strong class="break-all text-right">{{ $upcoming['endpoint'] }}</strong>
                            </div>
                            <pre class="min-h-[12rem] w-full whitespace-pre-wrap break-words">{{ $this->upcomingJson }}</pre>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Request spec</p>
                        <h3>Kontrak endpoint</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>Base URL</strong>
                        <p><code>https://use.api.co.id</code></p>
                    </article>
                    <article>
                        <strong>Endpoint</strong>
                        <p><code>/holidays/indonesia</code></p>
                        <p><code>/holidays/indonesia/check/date</code></p>
                    </article>
                    <article>
                        <strong>Header</strong>
                        <p><code>x-api-co-id</code></p>
                    </article>
                    <article>
                        <strong>Identifier API key</strong>
                        <p><code>apicoid_provider</code></p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Catatan</p>
                        <h3>Yang ditampilkan</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Daftar libur</h4>
                        <p>Nama, tanggal, tipe, status cuti bersama, dan hari kebesaran.</p>
                    </article>
                    <article>
                        <h4>Cek tanggal</h4>
                        <p>Status libur, hari dalam seminggu, akhir pekan, dan nama hari libur.</p>
                    </article>
                    <article>
                        <h4>Libur mendatang</h4>
                        <p>Daftar libur dari hari ini beserta sisa hari (days until).</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
