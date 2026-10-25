@php
    $systemInfo = $this->systemInfo();
    $memory = $systemInfo->phpMemoryUsage();
    $composer = $systemInfo->composerPackages();
    $modules = $systemInfo->phpModules();
    $unloaded = $systemInfo->unloadedExtensions();
    $moduleCounts = $systemInfo->moduleCountsByCategory();
    $ram = $systemInfo->memorySnapshot();
    $disk = $systemInfo->diskSnapshot();
    $maxModules = max([1, ...array_values($moduleCounts)]);

    $badgeClasses = [
        'Core' => 'sysinfo-badge sysinfo-badge--core',
        'Zend' => 'sysinfo-badge sysinfo-badge--zend',
        'Ekstensi' => 'sysinfo-badge sysinfo-badge--ext',
        'Belum aktif' => 'sysinfo-badge sysinfo-badge--idle',
        'Production' => 'sysinfo-badge sysinfo-badge--core',
        'Development' => 'sysinfo-badge sysinfo-badge--dev',
    ];
@endphp

<div class="settings-stack">
    <section class="settings-hero">
        <article class="settings-hero__intro">
            <p class="section-kicker">Operations</p>
            <h3>Info System</h3>
            <p>
                Kondisi server, konfigurasi PHP, modul yang aktif, dan paket Composer. Halaman ini hanya
                menampilkan nilai konfigurasi terpilih, bukan keluaran mentah <code>phpinfo()</code>, sehingga
                path sensitif dan variabel environment tidak ikut terbuka.
            </p>
        </article>

        <div class="settings-hero__stats">
            <article class="mini-stat">
                <span>PHP</span>
                <strong>{{ PHP_VERSION }}</strong>
            </article>
            <article class="mini-stat">
                <span>RAM Terpakai</span>
                <strong>{{ $systemInfo->formatPercent($ram['percent']) }}</strong>
            </article>
            <article class="mini-stat">
                <span>Disk Bebas</span>
                <strong>{{ $systemInfo->formatBytes($disk['free']) }}</strong>
            </article>
        </div>
    </section>

    @if (session('system_info_status'))
        <div class="form-alert form-alert--success">
            {{ session('system_info_status') }}
        </div>
    @endif

    <div class="sysinfo-toolbar">
        <nav class="sysinfo-tabs" role="tablist" aria-label="Info System tabs">
            @foreach ($this->tabs() as $key => $label)
                <button
                    type="button"
                    role="tab"
                    aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                    class="sysinfo-tabs__tab @if ($activeTab === $key) sysinfo-tabs__tab--active @endif"
                    wire:click="setTab('{{ $key }}')"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="sysinfo-toolbar__actions">
            <label class="checkbox-row">
                <input type="checkbox" wire:model.live="autoRefresh">
                Auto refresh 30 detik
            </label>

            <button
                type="button"
                class="sysinfo-refresh"
                wire:click="refresh"
                wire:loading.attr="disabled"
                wire:target="refresh"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-4">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16.023 9.348h4.992V4.356m-4.992 4.992 3.181-3.183a8.25 8.25 0 0 0-13.803 3.7M4.031 9.865v4.99m0 0h4.992m-4.992 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7" />
                </svg>
                <span wire:loading.remove wire:target="refresh">Refresh</span>
                <span wire:loading wire:target="refresh">Memproses...</span>
            </button>
        </div>
    </div>

    <div @if ($autoRefresh) wire:poll.30s @endif class="space-y-6">
        @if ($activeTab === 'overview')
            <section class="sysinfo-cards">
                @foreach ($systemInfo->overviewCards() as $card)
                    <article class="sysinfo-card">
                        <p class="sysinfo-card__label">{{ $card['label'] }}</p>
                        <p class="sysinfo-card__value">{{ $card['value'] }}</p>
                        @if (filled($card['sub']))
                            <p class="sysinfo-card__sub">{{ $card['sub'] }}</p>
                        @endif
                    </article>
                @endforeach
            </section>

            <section class="sysinfo-charts">
                <article class="surface-panel">
                    <p class="section-kicker">RAM Fisik</p>

                    <div class="sysinfo-chart">
                        <div class="sysinfo-donut" style="--value: {{ $ram['percent'] ?? 0 }}">
                            <span>{{ $systemInfo->formatPercent($ram['percent']) }}</span>
                        </div>

                        <ul class="sysinfo-chart__legend">
                            <li>
                                <span class="sysinfo-dot sysinfo-dot--brand"></span>
                                Terpakai <strong>{{ $systemInfo->formatBytes($ram['used']) }}</strong>
                            </li>
                            <li>
                                <span class="sysinfo-dot sysinfo-dot--muted"></span>
                                Bebas <strong>{{ $systemInfo->formatBytes($ram['free']) }}</strong>
                            </li>
                            <li>
                                Total <strong>{{ $systemInfo->formatBytes($ram['total']) }}</strong>
                            </li>
                        </ul>
                    </div>
                </article>

                <article class="surface-panel">
                    <p class="section-kicker">Disk Aplikasi</p>

                    <div class="sysinfo-chart">
                        <div class="sysinfo-donut sysinfo-donut--accent" style="--value: {{ $disk['percent'] ?? 0 }}">
                            <span>{{ $systemInfo->formatPercent($disk['percent']) }}</span>
                        </div>

                        <ul class="sysinfo-chart__legend">
                            <li>
                                <span class="sysinfo-dot sysinfo-dot--accent"></span>
                                Terpakai <strong>{{ $systemInfo->formatBytes($disk['used']) }}</strong>
                            </li>
                            <li>
                                <span class="sysinfo-dot sysinfo-dot--muted"></span>
                                Bebas <strong>{{ $systemInfo->formatBytes($disk['free']) }}</strong>
                            </li>
                            <li>
                                Total <strong>{{ $systemInfo->formatBytes($disk['total']) }}</strong>
                            </li>
                        </ul>
                    </div>
                </article>

                <article class="surface-panel">
                    <p class="section-kicker">Modul PHP per Kategori</p>

                    <ul class="sysinfo-bars">
                        @foreach ($moduleCounts as $category => $count)
                            <li>
                                <div class="sysinfo-bars__label">
                                    <span>{{ $category }}</span>
                                    <strong>{{ $count }}</strong>
                                </div>
                                <div class="sysinfo-bar">
                                    <div class="sysinfo-bar__fill" style="width: {{ ($count / $maxModules) * 100 }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </article>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Catatan</p>
                        <h3>Cara data dikumpulkan</h3>
                    </div>
                </div>

                <p class="surface-panel__text">
                    Data perangkat keras di-cache selama satu jam dan bisa diambil ulang dengan tombol Refresh.
                    Setiap tab dimuat terpisah sehingga hanya tab aktif yang dihitung, dan hasil probe disimpan
                    per request agar perintah sistem tidak dijalankan berulang kali.
                </p>
            </section>
        @endif

        @if ($activeTab === 'os')
            @include('livewire.operations.partials.info-rows', [
                'heading' => 'Detail Sistem Operasi',
                'rows' => $systemInfo->osInfo(),
            ])
        @endif

        @if ($activeTab === 'php')
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Memori PHP</p>
                        <h3>Pemakaian Memori PHP</h3>
                    </div>
                </div>

                <div class="sysinfo-memory">
                    <div class="sysinfo-memory__meta">
                        <span>{{ $memory['usage_label'] }} dari {{ $memory['limit_label'] }}</span>
                        <span>{{ $memory['percent_label'] }}</span>
                    </div>

                    <div class="sysinfo-bar">
                        <div class="sysinfo-bar__fill" style="width: {{ $memory['percent'] }}%"></div>
                    </div>

                    <p class="sysinfo-memory__note">Puncak pemakaian: {{ $memory['peak_label'] }}</p>
                </div>
            </section>

            @include('livewire.operations.partials.info-rows', [
                'heading' => 'Konfigurasi PHP',
                'rows' => $systemInfo->phpInfo(),
            ])
        @endif

        @if ($activeTab === 'modules')
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Modul PHP</p>
                        <h3>Modul PHP Aktif ({{ count($modules) }})</h3>
                    </div>
                </div>

                <div class="sysinfo-chips">
                    @foreach ($moduleCounts as $category => $count)
                        <span class="sysinfo-chip">{{ $category }}: {{ $count }}</span>
                    @endforeach
                </div>

                @include('livewire.operations.partials.data-table', [
                    'rows' => $modules,
                    'columns' => ['name' => 'Modul', 'version' => 'Versi', 'category' => 'Kategori'],
                    'badgeColumns' => ['category'],
                    'badgeClasses' => $badgeClasses,
                    'perPage' => 15,
                    'placeholder' => 'Cari modul...',
                    'emptyText' => 'Tidak ada modul yang cocok.',
                ])
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Direktori ekstensi</p>
                        <h3>Tersedia di Direktori Ekstensi ({{ count($unloaded) }})</h3>
                        <p class="surface-panel__text surface-panel__text--tight">
                            Ekstensi berikut ada di <code>{{ ini_get('extension_dir') }}</code> tetapi belum
                            dimuat oleh <code>php.ini</code>.
                        </p>
                    </div>
                </div>

                @include('livewire.operations.partials.data-table', [
                    'rows' => $unloaded,
                    'columns' => ['name' => 'Modul', 'category' => 'Status'],
                    'badgeColumns' => ['category'],
                    'badgeClasses' => $badgeClasses,
                    'perPage' => 15,
                    'placeholder' => 'Cari modul...',
                    'emptyText' => 'Semua ekstensi yang tersedia sudah aktif, atau direktori ekstensi tidak dapat dibaca.',
                ])
            </section>
        @endif

        @if ($activeTab === 'composer')
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Composer</p>
                        <h3>Ringkasan Composer</h3>
                    </div>
                </div>

                <div class="sysinfo-chips">
                    <span class="sysinfo-chip">Total: {{ $composer['counts']['total'] }} paket</span>
                    <span class="sysinfo-chip">Produksi: {{ $composer['counts']['production'] }}</span>
                    <span class="sysinfo-chip">Development: {{ $composer['counts']['development'] }}</span>
                    @if (filled($composer['php_constraint']))
                        <span class="sysinfo-chip">Requirement PHP: {{ $composer['php_constraint'] }}</span>
                    @endif
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">composer.lock</p>
                        <h3>Paket Terpasang</h3>
                    </div>
                </div>

                @include('livewire.operations.partials.data-table', [
                    'rows' => $composer['packages'],
                    'columns' => ['name' => 'Paket', 'version' => 'Versi', 'license' => 'Lisensi', 'type' => 'Tipe'],
                    'badgeColumns' => ['type'],
                    'badgeClasses' => $badgeClasses,
                    'perPage' => 20,
                    'placeholder' => 'Cari paket, lisensi, atau tipe...',
                    'emptyText' => 'composer.lock tidak ditemukan atau tidak berisi paket.',
                ])
            </section>
        @endif

        @if ($activeTab === 'infra')
            @foreach ($systemInfo->infraGroups() as $group)
                @include('livewire.operations.partials.info-rows', [
                    'heading' => $group['heading'],
                    'rows' => $group['rows'],
                ])
            @endforeach
        @endif
    </div>
<style>
    .sysinfo-toolbar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    @media (min-width: 1024px) {
        .sysinfo-toolbar {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .sysinfo-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.25rem;
        border: 1px solid rgb(var(--app-line));
        border-radius: 0.6rem;
        background-color: rgb(var(--app-surface-overlay));
        width: fit-content;
    }

    .sysinfo-tabs__tab {
        padding: 0.45rem 1.05rem;
        border: none;
        border-radius: 0.45rem;
        background: transparent;
        cursor: pointer;
        color: rgb(var(--app-ink));
        font-size: 0.8125rem;
        font-weight: 600;
        opacity: 0.65;
        transition: background-color 150ms ease, color 150ms ease, opacity 150ms ease;
    }

    .sysinfo-tabs__tab:hover {
        opacity: 0.9;
    }

    .sysinfo-tabs__tab--active {
        background-color: rgb(var(--app-brand-deep));
        color: #fff;
        opacity: 1;
    }

    .sysinfo-toolbar__actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .sysinfo-refresh {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        border: 1px solid rgb(var(--app-line));
        border-radius: 0.65rem;
        background-color: rgb(var(--app-surface));
        color: rgb(var(--app-ink));
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: border-color 150ms ease, background-color 150ms ease;
    }

    .sysinfo-refresh:hover {
        border-color: rgb(var(--app-brand) / 0.5);
        background-color: rgb(var(--app-surface-overlay));
    }

    .sysinfo-refresh:disabled {
        cursor: not-allowed;
        opacity: 0.6;
    }

    .sysinfo-cards {
        display: grid;
        gap: 1rem;
    }

    @media (min-width: 768px) {
        .sysinfo-cards {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .sysinfo-card {
        border: 1px solid rgb(var(--app-line));
        border-radius: 0.9rem;
        background-color: rgb(var(--app-surface));
        padding: 1rem;
        transition: background-color var(--app-transition), border-color var(--app-transition);
    }

    .sysinfo-card__label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: rgb(var(--app-muted));
    }

    .sysinfo-card__value {
        margin-top: 0.35rem;
        font-size: 1.15rem;
        font-weight: 800;
        color: rgb(var(--app-ink));
        overflow-wrap: anywhere;
    }

    .sysinfo-card__sub {
        margin-top: 0.25rem;
        font-size: 0.78rem;
        color: rgb(var(--app-muted));
        overflow-wrap: anywhere;
    }

    .sysinfo-charts {
        display: grid;
        gap: 1rem;
    }

    @media (min-width: 768px) {
        .sysinfo-charts {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    .sysinfo-chart {
        display: flex;
        align-items: center;
        gap: 1.15rem;
        margin-top: 0.75rem;
    }

    .sysinfo-donut {
        position: relative;
        display: grid;
        place-items: center;
        flex: 0 0 auto;
        width: 7.5rem;
        height: 7.5rem;
        border-radius: 50%;
        --sysinfo-donut-color: rgb(var(--app-brand));
        background: conic-gradient(var(--sysinfo-donut-color) calc(var(--value) * 1%), rgb(var(--app-line)) 0);
    }

    .sysinfo-donut::after {
        content: '';
        position: absolute;
        inset: 1rem;
        border-radius: 50%;
        background-color: rgb(var(--app-surface));
    }

    .sysinfo-donut span {
        position: relative;
        z-index: 1;
        font-size: 1.05rem;
        font-weight: 800;
        color: rgb(var(--app-ink));
    }

    .sysinfo-donut--accent {
        --sysinfo-donut-color: rgb(var(--app-accent));
    }

    .sysinfo-chart__legend {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
        font-size: 0.8rem;
        color: rgb(var(--app-muted));
    }

    .sysinfo-chart__legend li {
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }

    .sysinfo-chart__legend strong {
        color: rgb(var(--app-ink));
        font-weight: 700;
    }

    .sysinfo-dot {
        display: inline-block;
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 9999px;
    }

    .sysinfo-dot--brand {
        background-color: rgb(var(--app-brand));
    }

    .sysinfo-dot--accent {
        background-color: rgb(var(--app-accent));
    }

    .sysinfo-dot--muted {
        background-color: rgb(var(--app-line));
    }

    .sysinfo-bars {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        margin-top: 1rem;
    }

    .sysinfo-bars__label {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 0.35rem;
        font-size: 0.8rem;
        color: rgb(var(--app-muted));
    }

    .sysinfo-bars__label strong {
        color: rgb(var(--app-ink));
    }

    .sysinfo-bar {
        height: 0.5rem;
        border-radius: 9999px;
        background-color: rgb(var(--app-line));
        overflow: hidden;
    }

    .sysinfo-bar__fill {
        height: 0.5rem;
        border-radius: 9999px;
        background-color: rgb(var(--app-brand));
    }

    .sysinfo-memory {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }

    .sysinfo-memory__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        font-size: 0.82rem;
        color: rgb(var(--app-muted));
    }

    .sysinfo-memory__note {
        font-size: 0.78rem;
        color: rgb(var(--app-muted));
    }

    .sysinfo-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin: 0.75rem 0 1rem;
    }

    .sysinfo-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid rgb(var(--app-line));
        border-radius: 0.5rem;
        padding: 0.2rem 0.6rem;
        background-color: rgb(var(--app-surface-overlay));
        color: rgb(var(--app-ink));
        font-size: 0.75rem;
        font-weight: 600;
    }

    .sysinfo-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 0.375rem;
        padding: 0.1rem 0.5rem;
        font-size: 0.6875rem;
        font-weight: 700;
        background-color: rgb(37 99 235 / 0.12);
        color: rgb(30 64 175);
    }

    .sysinfo-badge--core {
        background-color: rgb(37 99 235 / 0.12);
        color: rgb(30 64 175);
    }

    .sysinfo-badge--zend {
        background-color: rgb(139 92 246 / 0.14);
        color: rgb(91 33 182);
    }

    .sysinfo-badge--ext {
        background-color: rgb(22 163 74 / 0.14);
        color: rgb(22 101 52);
    }

    .sysinfo-badge--idle {
        background-color: rgb(245 158 11 / 0.16);
        color: rgb(146 64 14);
    }

    .sysinfo-badge--dev {
        background-color: rgb(239 68 68 / 0.14);
        color: rgb(153 27 27);
    }

    [data-theme="dark"] .sysinfo-badge--core {
        background-color: rgb(59 130 246 / 0.2);
        color: rgb(147 197 253);
    }

    [data-theme="dark"] .sysinfo-badge--zend {
        background-color: rgb(139 92 246 / 0.2);
        color: rgb(196 181 253);
    }

    [data-theme="dark"] .sysinfo-badge--ext {
        background-color: rgb(34 197 94 / 0.2);
        color: rgb(134 239 172);
    }

    [data-theme="dark"] .sysinfo-badge--idle {
        background-color: rgb(245 158 11 / 0.2);
        color: rgb(252 211 77);
    }

    [data-theme="dark"] .sysinfo-badge--dev {
        background-color: rgb(239 68 68 / 0.2);
        color: rgb(252 165 165);
    }

    .sysinfo-rows {
        display: flex;
        flex-direction: column;
        margin-top: 0.75rem;
    }

    .sysinfo-rows__row {
        display: grid;
        gap: 0.25rem;
        padding: 0.6rem 0;
        border-bottom: 1px solid rgb(var(--app-line));
    }

    .sysinfo-rows__row:last-child {
        border-bottom: none;
    }

    @media (min-width: 768px) {
        .sysinfo-rows__row {
            grid-template-columns: minmax(0, 1fr) minmax(0, 2fr);
            gap: 1rem;
        }
    }

    .sysinfo-rows__row dt {
        font-size: 0.8rem;
        font-weight: 600;
        color: rgb(var(--app-muted));
    }

    .sysinfo-rows__row dd {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.8rem;
        color: rgb(var(--app-ink));
        overflow-wrap: anywhere;
    }

    .sysinfo-table-wrap {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 0.85rem;
    }

    .sysinfo-table-toolbar {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    @media (min-width: 1024px) {
        .sysinfo-table-toolbar {
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
        }
    }

    .sysinfo-table-search {
        width: 100%;
        max-width: 20rem;
        border: 1px solid rgb(var(--app-line));
        border-radius: 0.6rem;
        background-color: rgb(var(--app-surface));
        padding: 0.5rem 0.75rem;
        color: rgb(var(--app-ink));
        font-size: 0.8125rem;
    }

    .sysinfo-table-pager {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.8rem;
        color: rgb(var(--app-muted));
    }

    .sysinfo-table-btn {
        border: 1px solid rgb(var(--app-line));
        border-radius: 0.5rem;
        background-color: rgb(var(--app-surface));
        padding: 0.3rem 0.7rem;
        color: rgb(var(--app-ink));
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: border-color 150ms ease, background-color 150ms ease;
    }

    .sysinfo-table-btn:hover:not(:disabled) {
        border-color: rgb(var(--app-brand) / 0.5);
        background-color: rgb(var(--app-surface-overlay));
    }

    .sysinfo-table-btn:disabled {
        cursor: not-allowed;
        opacity: 0.45;
    }

    .sysinfo-table-scroll {
        overflow-x: auto;
        border: 1px solid rgb(var(--app-line));
        border-radius: 0.9rem;
        background-color: rgb(var(--app-surface));
    }

    .sysinfo-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 32rem;
    }

    .sysinfo-table th,
    .sysinfo-table td {
        padding: 0.6rem 0.85rem;
        border-bottom: 1px solid rgb(var(--app-line));
        text-align: left;
        font-size: 0.8rem;
    }

    .sysinfo-table th {
        color: rgb(var(--app-muted));
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .sysinfo-table th button {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        border: none;
        background: transparent;
        color: inherit;
        font: inherit;
        letter-spacing: inherit;
        text-transform: inherit;
        cursor: pointer;
    }

    .sysinfo-table tbody tr:last-child td {
        border-bottom: none;
    }

    .sysinfo-table td {
        color: rgb(var(--app-ink));
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        overflow-wrap: anywhere;
    }

    .sysinfo-table-empty {
        padding: 1.75rem 1rem;
        text-align: center;
        font-size: 0.8rem;
        color: rgb(var(--app-muted));
    }
</style>
</div>
