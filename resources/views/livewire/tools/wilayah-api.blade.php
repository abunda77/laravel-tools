<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Tools module</p>
            <h3>Wilayah administrasi Indonesia dari API.co.id.</h3>
            <p>
                Modul ini menelusuri hierarki wilayah (provinsi &rarr; kabupaten/kota &rarr; kecamatan &rarr; desa/kelurahan)
                melalui endpoint <code>/regional/indonesia</code> dengan header <code>x-api-co-id</code> dari API key
                <code>downloader_provider</code>.
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
                        <h3>1. Provinsi</h3>
                        <p class="surface-panel__text surface-panel__text--tight">
                            Tampilkan 34 provinsi di Indonesia, atau filter berdasarkan nama.
                        </p>
                    </div>
                </div>

                <form wire:submit="loadProvinces" class="settings-form">
                    <div class="form-grid">
                        <div class="form-field form-field--wide">
                            <label for="province_name_filter" class="form-label">Filter nama provinsi (opsional)</label>
                            <input
                                id="province_name_filter"
                                type="text"
                                wire:model="provinceNameFilter"
                                class="form-input"
                                placeholder="Misal: jawa"
                                autocomplete="off"
                            />
                        </div>
                    </div>

                    <div class="form-actions form-actions--split">
                        <div class="form-inline-note">
                            Endpoint: <code>/regional/indonesia/provinces</code>
                        </div>

                        <button type="submit" class="primary-action" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="loadProvinces">Tampilkan provinsi</span>
                            <span wire:loading wire:target="loadProvinces">Memproses...</span>
                        </button>
                    </div>
                </form>

                @if ($provinces)
                    <div class="result-stack">
                        <div class="result-summary">
                            <div class="result-summary__copy">
                                <p class="section-kicker">Province list</p>
                                <h4>{{ $provinces['total'] }} provinsi ditemukan.</h4>
                            </div>
                        </div>

                        @if ($provinces['total'] > 0)
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($provinces['items'] as $province)
                                    <article wire:key="province-{{ $loop->index }}" class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                        <div class="flex items-start justify-between gap-4">
                                            <div>
                                                <strong class="block text-base font-bold leading-7">{{ $province['name'] }}</strong>
                                                <span class="text-sm text-[rgb(var(--app-muted))]">Kode: {{ $province['code'] }}</span>
                                            </div>
                                            <button
                                                type="button"
                                                class="ghost-action"
                                                wire:click="selectProvince('{{ $province['code'] }}', '{{ $province['name'] }}')"
                                            >
                                                Pilih
                                            </button>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="form-alert">
                                Tidak ada provinsi yang cocok dengan filter.
                            </div>
                        @endif

                        <div class="json-viewer w-full">
                            <div class="json-viewer__header">
                                <span>Raw JSON response</span>
                                <strong class="break-all text-right">{{ $provinces['endpoint'] }}</strong>
                            </div>
                            <pre class="min-h-[12rem] w-full whitespace-pre-wrap break-words">{{ $this->provincesJson }}</pre>
                        </div>
                    </div>
                @endif
            </section>

            @if ($provinceCode)
                <section class="surface-panel">
                    <div class="surface-panel__header">
                        <div>
                            <h3>2. Kabupaten / Kota</h3>
                            <p class="surface-panel__text surface-panel__text--tight">
                                Wilayah terpilih: <strong>{{ $provinceName }} ({{ $provinceCode }})</strong>.
                            </p>
                        </div>
                    </div>

                    <form wire:submit="loadRegencies" class="settings-form">
                        <div class="form-grid">
                            <div class="form-field form-field--wide">
                                <label for="regency_name_filter" class="form-label">Filter nama kabupaten/kota (opsional)</label>
                                <input
                                    id="regency_name_filter"
                                    type="text"
                                    wire:model="regencyNameFilter"
                                    class="form-input"
                                    placeholder="Misal: sleman"
                                    autocomplete="off"
                                />
                            </div>
                        </div>

                        <div class="form-actions form-actions--split">
                            <div class="form-inline-note">
                                Endpoint: <code>/regional/indonesia/provinces/{{ $provinceCode }}/regencies</code>
                            </div>

                            <button type="submit" class="primary-action" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="loadRegencies">Tampilkan kabupaten/kota</span>
                                <span wire:loading wire:target="loadRegencies">Memproses...</span>
                            </button>
                        </div>
                    </form>

                    @if ($regencies)
                        <div class="result-stack">
                            <div class="result-summary">
                                <div class="result-summary__copy">
                                    <p class="section-kicker">Regency list</p>
                                    <h4>{{ $regencies['total'] }} kabupaten/kota ditemukan.</h4>
                                </div>
                            </div>

                            @if ($regencies['total'] > 0)
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($regencies['items'] as $regency)
                                        <article wire:key="regency-{{ $loop->index }}" class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <strong class="block text-base font-bold leading-7">{{ $regency['name'] }}</strong>
                                                    <span class="text-sm text-[rgb(var(--app-muted))]">Kode: {{ $regency['code'] }}</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="ghost-action"
                                                    wire:click="selectRegency('{{ $regency['code'] }}', '{{ $regency['name'] }}')"
                                                >
                                                    Pilih
                                                </button>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="form-alert">
                                    Tidak ada kabupaten/kota yang cocok dengan filter.
                                </div>
                            @endif

                            <div class="json-viewer w-full">
                                <div class="json-viewer__header">
                                    <span>Raw JSON response</span>
                                    <strong class="break-all text-right">{{ $regencies['endpoint'] }}</strong>
                                </div>
                                <pre class="min-h-[12rem] w-full whitespace-pre-wrap break-words">{{ $this->regenciesJson }}</pre>
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            @if ($regencyCode)
                <section class="surface-panel">
                    <div class="surface-panel__header">
                        <div>
                            <h3>3. Kecamatan</h3>
                            <p class="surface-panel__text surface-panel__text--tight">
                                Wilayah terpilih: <strong>{{ $regencyName }} ({{ $regencyCode }})</strong>.
                            </p>
                        </div>
                    </div>

                    <form wire:submit="loadDistricts" class="settings-form">
                        <div class="form-grid">
                            <div class="form-field form-field--wide">
                                <label for="district_name_filter" class="form-label">Filter nama kecamatan (opsional)</label>
                                <input
                                    id="district_name_filter"
                                    type="text"
                                    wire:model="districtNameFilter"
                                    class="form-input"
                                    placeholder="Misal: tegal"
                                    autocomplete="off"
                                />
                            </div>
                        </div>

                        <div class="form-actions form-actions--split">
                            <div class="form-inline-note">
                                Endpoint: <code>/regional/indonesia/regencies/{{ $regencyCode }}/districts</code>
                            </div>

                            <button type="submit" class="primary-action" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="loadDistricts">Tampilkan kecamatan</span>
                                <span wire:loading wire:target="loadDistricts">Memproses...</span>
                            </button>
                        </div>
                    </form>

                    @if ($districts)
                        <div class="result-stack">
                            <div class="result-summary">
                                <div class="result-summary__copy">
                                    <p class="section-kicker">District list</p>
                                    <h4>{{ $districts['total'] }} kecamatan ditemukan.</h4>
                                </div>
                            </div>

                            @if ($districts['total'] > 0)
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($districts['items'] as $district)
                                        <article wire:key="district-{{ $loop->index }}" class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <strong class="block text-base font-bold leading-7">{{ $district['name'] }}</strong>
                                                    <span class="text-sm text-[rgb(var(--app-muted))]">Kode: {{ $district['code'] }}</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="ghost-action"
                                                    wire:click="selectDistrict('{{ $district['code'] }}', '{{ $district['name'] }}')"
                                                >
                                                    Pilih
                                                </button>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="form-alert">
                                    Tidak ada kecamatan yang cocok dengan filter.
                                </div>
                            @endif

                            <div class="json-viewer w-full">
                                <div class="json-viewer__header">
                                    <span>Raw JSON response</span>
                                    <strong class="break-all text-right">{{ $districts['endpoint'] }}</strong>
                                </div>
                                <pre class="min-h-[12rem] w-full whitespace-pre-wrap break-words">{{ $this->districtsJson }}</pre>
                            </div>
                        </div>
                    @endif
                </section>
            @endif

            @if ($districtCode)
                <section class="surface-panel">
                    <div class="surface-panel__header">
                        <div>
                            <h3>4. Desa / Kelurahan</h3>
                            <p class="surface-panel__text surface-panel__text--tight">
                                Wilayah terpilih: <strong>{{ $districtName }} ({{ $districtCode }})</strong>.
                            </p>
                        </div>
                    </div>

                    <form wire:submit="loadVillages" class="settings-form">
                        <div class="form-grid">
                            <div class="form-field form-field--wide">
                                <label for="village_name_filter" class="form-label">Filter nama desa/kelurahan (opsional)</label>
                                <input
                                    id="village_name_filter"
                                    type="text"
                                    wire:model="villageNameFilter"
                                    class="form-input"
                                    placeholder="Misal: condong"
                                    autocomplete="off"
                                />
                            </div>
                        </div>

                        <div class="form-actions form-actions--split">
                            <div class="form-inline-note">
                                Endpoint: <code>/regional/indonesia/districts/{{ $districtCode }}/villages</code>
                            </div>

                            <button type="submit" class="primary-action" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="loadVillages">Tampilkan desa/kelurahan</span>
                                <span wire:loading wire:target="loadVillages">Memproses...</span>
                            </button>
                        </div>
                    </form>

                    @if ($villages)
                        <div class="result-stack">
                            <div class="result-summary">
                                <div class="result-summary__copy">
                                    <p class="section-kicker">Village list</p>
                                    <h4>{{ $villages['total'] }} desa/kelurahan ditemukan.</h4>
                                </div>
                            </div>

                            @if ($villages['total'] > 0)
                                <div class="grid gap-3 sm:grid-cols-2">
                                    @foreach ($villages['items'] as $village)
                                        <article wire:key="village-{{ $loop->index }}" class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                                            <div class="flex items-start justify-between gap-4">
                                                <div>
                                                    <strong class="block text-base font-bold leading-7">{{ $village['name'] }}</strong>
                                                    <span class="text-sm text-[rgb(var(--app-muted))]">Kode: {{ $village['code'] }}</span>
                                                    <div class="mt-2 flex flex-wrap gap-2">
                                                        @foreach ($village['postal_codes'] as $postalCode)
                                                            <span class="status-pill">Kode pos {{ $postalCode }}</span>
                                                        @endforeach
                                                        @if ($village['is_courier_support'])
                                                            <span class="status-pill status-pill--ready">Courier support</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="form-alert">
                                    Tidak ada desa/kelurahan yang cocok dengan filter.
                                </div>
                            @endif

                            <div class="json-viewer w-full">
                                <div class="json-viewer__header">
                                    <span>Raw JSON response</span>
                                    <strong class="break-all text-right">{{ $villages['endpoint'] }}</strong>
                                </div>
                                <pre class="min-h-[12rem] w-full whitespace-pre-wrap break-words">{{ $this->villagesJson }}</pre>
                            </div>
                        </div>
                    @endif
                </section>
            @endif
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
                        <p><code>/regional/indonesia/provinces</code></p>
                        <p><code>/regional/indonesia/provinces/{code}/regencies</code></p>
                        <p><code>/regional/indonesia/regencies/{code}/districts</code></p>
                        <p><code>/regional/indonesia/districts/{code}/villages</code></p>
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
                        <h3>Cara pakai</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Hierarki</h4>
                        <p>Pilih provinsi &rarr; kabupaten/kota &rarr; kecamatan &rarr; desa/kelurahan secara bertahap.</p>
                    </article>
                    <article>
                        <h4>Kode pos & courier</h4>
                        <p>Tiap desa memuat kode pos dan status <code>is_courier_support</code> untuk cek ongkos kirim.</p>
                    </article>
                    <article>
                        <h4>Filter</h4>
                        <p>Gunakan filter nama pada tiap level untuk mempersempit pencarian.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
