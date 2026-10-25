<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Internet module</p>
            <h3>Harga komoditi emas (XAU) per gram.</h3>
            <p>
                Mengambil harga emas 24k dari goldapi.io untuk 7 hari terakhir, dikonversi ke Rupiah memakai kurs
                <code>USDIDR</code> dari API.co.id. Harga per gram dihitung dari troy ounce
                (1 oz = 31,1035 gram).
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Saved API key</span>
                <strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong>
            </div>
            <div class="mini-stat">
                <span>Endpoint</span>
                <strong>{{ \App\Services\Internet\GoldPriceService::ENDPOINT }}</strong>
            </div>
            <div class="mini-stat">
                <span>Periode</span>
                <strong>7 hari terakhir</strong>
            </div>
        </div>
    </section>

    <section class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <h3>Gold price runner</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    Jalankan untuk mengambil harga terakhir dan pergerakan harga 7 hari terakhir. API key goldapi.io
                    diambil dari Settings dengan identifier <code>goldapi_provider</code>, kurs dari
                    <code>apicoid_provider</code>.
                </p>
            </div>

            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">
                {{ $hasSavedApiKey ? 'Saved key ready' : 'No saved key' }}
            </span>
        </div>

        @if ($errorMessage)
            <div class="form-alert form-alert--danger">
                {{ $errorMessage }}
            </div>
        @endif

        <form wire:submit="run" class="settings-form">
            <div class="form-actions form-actions--split">
                <div class="form-inline-note">
                    Header auth: <code>x-access-token</code> &middot; hasil di-cache 1 jam
                </div>

                <button type="submit" class="primary-action" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="run">Ambil harga emas</span>
                    <span wire:loading wire:target="run">Memproses...</span>
                </button>
            </div>
        </form>

        @if ($result)
            @php
                $rows = $result['rows'];
                $latest = $rows[0] ?? null;
                $formatIdr = fn ($value) => is_numeric($value) ? 'Rp '.number_format((float) $value, 0, ',', '.') : '-';
                $formatUsd = fn ($value) => is_numeric($value) ? '$ '.number_format((float) $value, 2, '.', ',') : '-';
            @endphp

            <div class="result-stack">
                <div class="result-summary">
                    <div class="result-summary__copy">
                        <p class="section-kicker">Harga terakhir</p>
                        <h4>{{ $latest ? $formatIdr($latest['priceGramIdr']) : '-' }} / gram</h4>
                        <p>
                            Emas 24k per gram
                            @if ($latest && $latest['changeIdr'] !== null)
                                &middot;
                                <span class="{{ $latest['isUp'] ? 'text-emerald-600' : 'text-rose-600' }} font-semibold">
                                    {{ $latest['isUp'] ? '+' : '' }}{{ number_format($latest['changeIdr'], 0, ',', '.') }} IDR
                                    ({{ $latest['isUp'] ? '+' : '' }}{{ number_format((float) $latest['changePercent'], 2, ',', '.') }}%)
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-4">
                    <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                        <span>Harga / gram (IDR)</span>
                        <strong class="mt-2 block text-xl font-bold tracking-tight">{{ $latest ? $formatIdr($latest['priceGramIdr']) : '-' }}</strong>
                    </article>
                    <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                        <span>Harga / gram (USD)</span>
                        <strong class="mt-2 block text-xl font-bold tracking-tight">{{ $latest ? $formatUsd($latest['priceGramUsd']) : '-' }}</strong>
                    </article>
                    <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                        <span>Kurs USD &rarr; IDR</span>
                        <strong class="mt-2 block text-xl font-bold tracking-tight">{{ number_format($result['usdToIdr'], 0, ',', '.') }}</strong>
                    </article>
                    <article class="rounded-[1.3rem] border border-[rgb(var(--app-line))] bg-white/80 px-5 py-4">
                        <span>Diambil pada</span>
                        <strong class="mt-2 block text-base font-bold leading-7">{{ $result['fetchedAt'] }}</strong>
                    </article>
                </div>

                @php
                    $maxPrice = max(array_column($rows, 'priceGramIdr'));
                @endphp

                <div class="overflow-hidden rounded-[1.75rem] border border-[rgb(var(--app-line))] bg-white/80 px-6 py-6">
                    <h4 class="mb-6 text-sm font-semibold uppercase tracking-[0.22em] text-[rgb(var(--app-muted))]">Grafik Pergerakan Harga 7 Hari</h4>
                    <div class="overflow-x-auto">
                        <div class="flex items-end justify-around gap-2 min-w-[500px]" style="height: 220px;">
                            @foreach (array_reverse($rows) as $row)
                                @php
                                    $height = $maxPrice > 0 ? ($row['priceGramIdr'] / $maxPrice) * 100 : 0;
                                    $bgColor = $row['isUp'] ? 'bg-emerald-500' : 'bg-rose-500';
                                @endphp
                                <div class="flex flex-col items-center flex-1 group">
                                    <div class="relative w-full">
                                        <div 
                                            class="{{ $bgColor }} rounded-t-lg transition-all hover:opacity-80 cursor-pointer w-full"
                                            style="height: {{ $height }}%;"
                                            title="{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}: {{ $formatIdr($row['priceGramIdr']) }}"
                                        >
                                        </div>
                                        <div class="absolute -top-6 left-1/2 -translate-x-1/2 opacity-0 group-hover:opacity-100 transition-opacity bg-gray-900 text-white text-xs rounded px-2 py-1 whitespace-nowrap pointer-events-none">
                                            {{ $formatIdr($row['priceGramIdr']) }}
                                        </div>
                                    </div>
                                    <div class="mt-2 text-xs text-center text-[rgb(var(--app-muted))]">
                                        {{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M') }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-[1.75rem] border border-[rgb(var(--app-line))] bg-white/80">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-sm">
                            <thead class="bg-[rgba(var(--app-surface-strong),0.55)]">
                                <tr class="text-left text-xs uppercase tracking-[0.22em] text-[rgb(var(--app-muted))]">
                                    <th class="px-5 py-4 font-semibold">Tanggal</th>
                                    <th class="px-5 py-4 font-semibold text-right">Harga / gram 24k (USD)</th>
                                    <th class="px-5 py-4 font-semibold text-right">Harga / gram 24k (IDR)</th>
                                    <th class="px-5 py-4 font-semibold text-right">Perubahan (IDR)</th>
                                    <th class="px-5 py-4 font-semibold text-right">Perubahan (%)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgb(var(--app-line))]">
                                @forelse ($rows as $row)
                                    <tr wire:key="gold-price-{{ $row['date'] }}">
                                        <td class="px-5 py-4 font-medium text-[rgb(var(--app-ink))]">
                                            {{ \Illuminate\Support\Carbon::parse($row['date'])->format('d M Y') }}
                                            <span class="block text-xs text-[rgb(var(--app-muted))]">
                                                {{ \Illuminate\Support\Carbon::parse($row['date'])->format('l') }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-right font-mono text-[rgb(var(--app-ink))]">{{ $formatUsd($row['priceGramUsd']) }}</td>
                                        <td class="px-5 py-4 text-right font-mono text-[rgb(var(--app-ink))]">{{ $formatIdr($row['priceGramIdr']) }}</td>
                                        <td class="px-5 py-4 text-right font-mono {{ $row['isUp'] ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $row['changeIdr'] === null ? '-' : ($row['isUp'] ? '+' : '').number_format($row['changeIdr'], 0, ',', '.') }}
                                        </td>
                                        <td class="px-5 py-4 text-right font-mono {{ $row['isUp'] ? 'text-emerald-600' : 'text-rose-600' }}">
                                            {{ $row['changePercent'] === null ? '-' : ($row['isUp'] ? '+' : '').number_format($row['changePercent'], 2, ',', '.').'%' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-6 text-center text-[rgb(var(--app-muted))]">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <p class="text-xs leading-6 text-[rgb(var(--app-muted))]">
                    Catatan: pasar emas tutup pada akhir pekan, sehingga hari tanpa transaksi tidak ditampilkan.
                    Perubahan dihitung dari harga penutupan hari sebelumnya.
                </p>
            </div>
        @endif
    </section>
</div>