<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Apify Scraper</p>
            <h3>GMaps 1.0</h3>
            <p>
                Jalankan actor Apify <code>{{ \App\Services\Apify\GmapsScraperService::ACTOR_ID }}</code> untuk scrape data bisnis dari Google Maps.
                API key otomatis memakai <code>apify_provider</code> dari Settings.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Actor</span>
                <strong>GMaps 1.0</strong>
            </div>
            <div class="mini-stat">
                <span>Exports</span>
                <strong>CSV / XLSX / PDF</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Scrape Google Maps</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Isi query dan URL Google Maps, lalu hasil akan tampil sebagai tabel dinamis sesuai payload actor.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label for="apify_search_query" class="form-label">Search Query</label>
                                <p class="form-help">Contoh: <code>dentist</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                                {{ $hasSavedApiKey ? 'Key ready' : 'No key' }}
                            </span>
                        </div>

                        <input
                            id="apify_search_query"
                            type="text"
                            wire:model="searchQuery"
                            class="form-input"
                            placeholder="dentist"
                            autocomplete="off"
                        />
                        @error('searchQuery') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field form-field--wide">
                        <label for="apify_gmaps_url" class="form-label">Google Maps URL</label>
                        <textarea
                            id="apify_gmaps_url"
                            wire:model="gmapsUrl"
                            class="form-input"
                            rows="3"
                            placeholder="https://www.google.com/maps/search/dentist/..."
                        ></textarea>
                        @error('gmapsUrl') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="apify_latitude" class="form-label">Latitude</label>
                        <input id="apify_latitude" type="text" wire:model="latitude" class="form-input" placeholder="-7.7811233" />
                        @error('latitude') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="apify_longitude" class="form-label">Longitude</label>
                        <input id="apify_longitude" type="text" wire:model="longitude" class="form-input" placeholder="110.3877011" />
                        @error('longitude') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="apify_area_width" class="form-label">Area Width</label>
                        <input id="apify_area_width" type="number" wire:model="areaWidth" class="form-input" min="1" max="1000" placeholder="20" />
                        @error('areaWidth') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="apify_area_height" class="form-label">Area Height</label>
                        <input id="apify_area_height" type="number" wire:model="areaHeight" class="form-input" min="1" max="1000" placeholder="20" />
                        @error('areaHeight') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="apify_max_results" class="form-label">Max Results</label>
                        <input id="apify_max_results" type="number" wire:model="maxResults" class="form-input" min="1" max="5000" placeholder="500" />
                        @error('maxResults') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        API key: <code>apify_provider</code>
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Jalankan scraper</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($hasResults)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Response summary</p>
                            <h4>{{ $searchQuery }}</h4>
                            <p>{{ $resultsCount }} baris data berhasil diambil dari actor Apify.</p>
                        </div>
                        <span class="status-pill status-pill--ready">{{ $resultsCount }} item</span>
                    </div>

                    <div class="export-toolbar">
                        <button type="button" class="export-chip export-chip--csv" wire:click="exportCsv">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="export-chip__icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15" />
                            </svg>
                            <span class="export-chip__meta">
                                <strong>CSV</strong>
                                <span>Download data</span>
                            </span>
                        </button>
                        <button type="button" class="export-chip export-chip--xlsx" wire:click="exportXlsx">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="export-chip__icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15" />
                            </svg>
                            <span class="export-chip__meta">
                                <strong>XLSX</strong>
                                <span>Spreadsheet</span>
                            </span>
                        </button>
                        <button type="button" class="export-chip export-chip--pdf" wire:click="exportPdf">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="export-chip__icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 16.5V4.5m0 12 4.5-4.5M12 16.5 7.5 12M4.5 19.5h15" />
                            </svg>
                            <span class="export-chip__meta">
                                <strong>PDF</strong>
                                <span>Printable</span>
                            </span>
                        </button>
                    </div>

                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        @foreach ($columns as $column)
                                            <th class="px-4 py-3 font-semibold">{{ \Illuminate\Support\Str::headline($column) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @foreach ($results as $index => $row)
                                        <tr wire:key="apify-gmaps-row-{{ $index }}">
                                            @foreach ($columns as $column)
                                                <td class="px-4 py-4 align-top text-[rgb(var(--app-ink))]">
                                                    @php($value = $row[$column] ?? null)

                                                    @if ($this->isUrlValue($value))
                                                        <a href="{{ $value }}" target="_blank" rel="noopener noreferrer" class="min-w-[18rem] break-all font-mono text-xs leading-6 text-[rgb(var(--app-accent))] hover:underline">
                                                            {{ $value }}
                                                        </a>
                                                    @else
                                                        <span class="block min-w-[10rem] break-words">{{ $value === null || $value === '' ? '-' : $value }}</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Data JSON</span>
                            <strong>{{ $resultsCount }} item</strong>
                        </div>
                        <pre>{{ $this->prettyJson }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Parameter</p>
                        <h3>Input actor</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>search_query</strong>
                        <p>Parameter utama yang wajib diisi untuk menentukan jenis bisnis atau keyword yang ingin discrape.</p>
                    </article>
                    <article>
                        <strong>gmaps_url</strong>
                        <p>Opsional. Bisa diisi jika ingin mengarahkan actor ke halaman hasil Google Maps tertentu.</p>
                    </article>
                    <article>
                        <strong>latitude / longitude</strong>
                        <p>Opsional. Gunakan jika ingin membantu actor mengunci area pencarian secara lebih spesifik.</p>
                    </article>
                    <article>
                        <strong>area_width / area_height</strong>
                        <p>Opsional. Jika dikosongkan, sistem memakai default <code>20 x 20</code>.</p>
                    </article>
                    <article>
                        <strong>max_results</strong>
                        <p>Opsional. Jika dikosongkan, sistem memakai default <code>500</code> hasil.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Output</p>
                        <h3>Tabel dinamis</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Kolom fleksibel</h4>
                        <p>Header tabel dibentuk otomatis dari semua key yang muncul pada response actor.</p>
                    </article>
                    <article>
                        <h4>Input ringan</h4>
                        <p>Hanya <code>search_query</code> yang wajib. Parameter lain bisa dibiarkan kosong dan akan memakai fallback yang aman.</p>
                    </article>
                    <article>
                        <h4>Export langsung</h4>
                        <p>Data yang sedang tampil bisa langsung diunduh dalam format CSV, XLSX, atau PDF.</p>
                    </article>
                    <article>
                        <h4>Tanpa penyimpanan</h4>
                        <p>Hasil hanya diproses di halaman saat ini dan tidak disimpan ke database.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
    <style>
    .export-toolbar {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .export-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.8rem;
        border: 1px solid rgba(var(--app-line), 0.95);
        border-radius: 999px;
        padding: 0.7rem 1rem;
        background: rgba(255, 255, 255, 0.78);
        color: rgb(var(--app-ink));
        box-shadow: 0 16px 30px -24px rgba(15, 23, 42, 0.35);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease, background 0.18s ease;
    }

    .export-chip:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 36px -22px rgba(15, 23, 42, 0.42);
    }

    .export-chip__icon {
        width: 1.1rem;
        height: 1.1rem;
        flex-shrink: 0;
    }

    .export-chip__meta {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        line-height: 1.1;
    }

    .export-chip__meta strong {
        font-size: 0.83rem;
        letter-spacing: 0.08em;
    }

    .export-chip__meta span {
        font-size: 0.72rem;
        color: rgb(var(--app-muted));
    }

    .export-chip--csv {
        border-color: rgba(16, 185, 129, 0.24);
        background: linear-gradient(135deg, rgba(236, 253, 245, 0.95), rgba(255, 255, 255, 0.9));
        color: rgb(5, 150, 105);
    }

    .export-chip--xlsx {
        border-color: rgba(14, 116, 144, 0.22);
        background: linear-gradient(135deg, rgba(240, 249, 255, 0.95), rgba(255, 255, 255, 0.9));
        color: rgb(3, 105, 161);
    }

    .export-chip--pdf {
        border-color: rgba(239, 68, 68, 0.2);
        background: linear-gradient(135deg, rgba(254, 242, 242, 0.96), rgba(255, 255, 255, 0.92));
        color: rgb(220, 38, 38);
    }
    </style>
</div>
