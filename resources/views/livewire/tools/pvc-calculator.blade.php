<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Tools module</p>
            <h3>Calculator PVC</h3>
            <p>
                Hitung estimasi kebutuhan lembar PVC berdasarkan panjang dan lebar bidang, lalu konversi ke perkiraan biaya per lembar.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Preset ukuran</span>
                <strong>{{ count($this->presets()) - 1 }} standar</strong>
            </div>
            <div class="mini-stat">
                <span>Satuan bidang</span>
                <strong>Meter / cm</strong>
            </div>
            <div class="mini-stat">
                <span>Cadangan</span>
                <strong>{{ $wastePercentage }}%</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Form perhitungan PVC</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Pilih ukuran produk PVC, isi dimensi bidang, lalu sistem akan menghitung kebutuhan lembar dasar dan rekomendasi plus cadangan potongan.
                    </p>
                </div>
            </div>

            <form wire:submit="calculate" class="settings-form">
                <div class="form-grid">
                    <div class="form-field md:col-span-2">
                        <label for="pvc_preset" class="form-label">Tipe / ukuran PVC</label>
                        <select id="pvc_preset" wire:model.live="productPreset" class="form-input">
                            @foreach ($this->presets() as $presetKey => $preset)
                                <option value="{{ $presetKey }}">{{ $preset['label'] }}</option>
                            @endforeach
                        </select>
                        <p class="form-help">{{ $this->presets()[$productPreset]['price_note'] ?? 'Pilih ukuran panel atau board yang paling mendekati produk Anda.' }}</p>
                        @error('productPreset') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="field_length" class="form-label">Panjang bidang</label>
                        <div class="grid grid-cols-[minmax(0,1fr)_6.5rem] gap-3">
                            <input id="field_length" type="number" min="0.01" step="0.01" wire:model="fieldLength" class="form-input" placeholder="3">
                            <select wire:model="fieldLengthUnit" class="form-input">
                                <option value="m">Meter</option>
                                <option value="cm">cm</option>
                            </select>
                        </div>
                        @error('fieldLength') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="field_width" class="form-label">Lebar bidang</label>
                        <div class="grid grid-cols-[minmax(0,1fr)_6.5rem] gap-3">
                            <input id="field_width" type="number" min="0.01" step="0.01" wire:model="fieldWidth" class="form-input" placeholder="3">
                            <select wire:model="fieldWidthUnit" class="form-input">
                                <option value="m">Meter</option>
                                <option value="cm">cm</option>
                            </select>
                        </div>
                        @error('fieldWidth') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="sheet_width_cm" class="form-label">Lebar per lembar</label>
                        <div class="relative">
                            <input id="sheet_width_cm" type="number" min="0.01" step="0.01" wire:model="sheetWidthCm" class="form-input pr-14" placeholder="20">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-[rgb(var(--app-muted))]">cm</span>
                        </div>
                        @error('sheetWidthCm') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="sheet_length_cm" class="form-label">Panjang per lembar</label>
                        <div class="relative">
                            <input id="sheet_length_cm" type="number" min="0.01" step="0.01" wire:model="sheetLengthCm" class="form-input pr-14" placeholder="300">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-[rgb(var(--app-muted))]">cm</span>
                        </div>
                        @error('sheetLengthCm') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="sheet_thickness_mm" class="form-label">Tebal</label>
                        <div class="relative">
                            <input id="sheet_thickness_mm" type="number" min="0.01" step="0.01" wire:model="sheetThicknessMm" class="form-input pr-14" placeholder="8">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-[rgb(var(--app-muted))]">mm</span>
                        </div>
                        @error('sheetThicknessMm') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="price_per_sheet" class="form-label">Harga per lembar</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-[rgb(var(--app-muted))]">Rp</span>
                            <input id="price_per_sheet" type="number" min="0" step="1" wire:model="pricePerSheet" class="form-input pl-12" placeholder="40000">
                        </div>
                        @error('pricePerSheet') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <div class="flex items-start justify-between gap-4">
                            <label for="waste_percentage" class="form-label">Cadangan potongan</label>
                            <span class="status-pill status-pill--ready">Opsional</span>
                        </div>
                        <div class="relative">
                            <input id="waste_percentage" type="number" min="0" max="100" step="1" wire:model="wastePercentage" class="form-input pr-14" placeholder="10">
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-[rgb(var(--app-muted))]">%</span>
                        </div>
                        <p class="form-help">Default 10% untuk toleransi sisa potongan dan pemasangan.</p>
                        @error('wastePercentage') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">
                        Cocok untuk panel strip maupun PVC board lembar penuh.
                    </div>

                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="calculate">Hitung kebutuhan</span>
                        <span wire:loading wire:target="calculate">Menghitung...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    <div class="parcel-summary">
                        <div>
                            <p class="section-kicker">Ringkasan hasil</p>
                            <h4>{{ $result['preset_label'] }}</h4>
                            <p>{{ $result['product_type'] }} dengan tebal {{ rtrim(rtrim(number_format($result['sheet_thickness_mm'], 2, '.', ''), '0'), '.') }} mm.</p>
                        </div>

                        <span class="status-pill status-pill--ready">{{ $result['recommended_sheets'] }} lembar</span>
                    </div>

                    <div class="parcel-data-grid">
                        <article>
                            <span>Luas bidang</span>
                            <strong>{{ number_format($result['field_area_square_meters'], 2, ',', '.') }} m²</strong>
                        </article>
                        <article>
                            <span>Luas per lembar</span>
                            <strong>{{ number_format($result['sheet_area_square_meters'], 4, ',', '.') }} m²</strong>
                        </article>
                        <article>
                            <span>Kebutuhan dasar</span>
                            <strong>{{ $result['base_sheets'] }} lembar</strong>
                        </article>
                        <article>
                            <span>Rekomendasi + cadangan</span>
                            <strong>{{ $result['recommended_sheets'] }} lembar</strong>
                        </article>
                        <article>
                            <span>Harga per lembar</span>
                            <strong>{{ $this->formattedCurrency($result['price_per_sheet']) }}</strong>
                        </article>
                        <article>
                            <span>Cadangan luas</span>
                            <strong>{{ number_format($result['coverage_margin_square_meters'], 2, ',', '.') }} m²</strong>
                        </article>
                    </div>

                    <section class="surface-panel border border-[rgba(var(--app-border),0.8)] bg-[rgba(var(--app-surface),0.75)]">
                        <div class="surface-panel__header">
                            <div>
                                <p class="section-kicker">Estimasi biaya</p>
                                <h3>Total material PVC</h3>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <article class="rounded-[1.5rem] border border-[rgba(var(--app-border),0.8)] bg-white/80 p-5">
                                <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-[rgb(var(--app-muted))]">Tanpa cadangan</span>
                                <strong class="mt-2 block text-2xl font-black text-[rgb(var(--app-ink))]">{{ $this->formattedCurrency($result['base_total_cost']) }}</strong>
                                <p class="mt-2 text-sm text-[rgb(var(--app-muted))]">{{ $result['base_sheets'] }} lembar x {{ $this->formattedCurrency($result['price_per_sheet']) }}</p>
                            </article>

                            <article class="rounded-[1.5rem] border border-[rgba(var(--app-border),0.8)] bg-[rgba(var(--app-accent),0.08)] p-5">
                                <span class="block text-xs font-semibold uppercase tracking-[0.24em] text-[rgb(var(--app-muted))]">Dengan cadangan {{ rtrim(rtrim(number_format($result['waste_percentage'], 2, '.', ''), '0'), '.') }}%</span>
                                <strong class="mt-2 block text-2xl font-black text-[rgb(var(--app-ink))]">{{ $this->formattedCurrency($result['recommended_total_cost']) }}</strong>
                                <p class="mt-2 text-sm text-[rgb(var(--app-muted))]">{{ $result['recommended_sheets'] }} lembar x {{ $this->formattedCurrency($result['price_per_sheet']) }}</p>
                            </article>
                        </div>
                    </section>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Referensi harga</p>
                        <h3>Rata-rata pasar umum</h3>
                    </div>
                </div>

                <div class="feature-list">
                    <article>
                        <h4>Panel strip 20 x 300 cm</h4>
                        <p>Dipatok default sekitar Rp40.000/lembar, mengacu pada kisaran artikel harga plafon PVC dan listing marketplace umum.</p>
                    </article>
                    <article>
                        <h4>Panel strip 25 x 300 cm</h4>
                        <p>Dipatok default sekitar Rp50.000/lembar karena luasnya 0,75 m² per lembar dan lazim dijual sedikit di atas varian 20 cm.</p>
                    </article>
                    <article>
                        <h4>PVC board 122 x 244 cm</h4>
                        <p>Preset harga board memakai titik tengah kisaran umum per ketebalan 3 mm sampai 10 mm.</p>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Catatan</p>
                        <h3>Cara baca hasil</h3>
                    </div>
                </div>

                <div class="settings-checklist">
                    <article>
                        <strong>Kebutuhan dasar</strong>
                        <p>Jumlah minimum lembar jika pemasangan ideal tanpa salah potong.</p>
                    </article>
                    <article>
                        <strong>Rekomendasi</strong>
                        <p>Jumlah lembar setelah ditambah cadangan untuk waste, sambungan, dan trimming di lapangan.</p>
                    </article>
                    <article>
                        <strong>Harga preset</strong>
                        <p>Silakan ganti manual jika toko, motif, ketebalan, atau wilayah Anda berbeda.</p>
                    </article>
                    <article>
                        <strong>Belum termasuk</strong>
                        <p>Rangka, lis, lem, sekrup, ongkir, dan ongkos pasang.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
