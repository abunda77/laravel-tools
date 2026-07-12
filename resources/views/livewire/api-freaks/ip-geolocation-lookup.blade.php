<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>IP Geolocation API</h3>
            <p>
                Lookup informasi geolokasi, ASN, timezone, dan currency dari alamat IP via <code>{{ \App\Services\ApiFreaks\IpGeolocationService::ENDPOINT }}</code>.
                Hasil ditampilkan sebagai tabel per-section: Location, Country Metadata, Network, Currency, ASN, Company, dan Time Zone.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat"><span>Saved API key</span><strong>{{ $hasSavedApiKey ? 'Available' : 'Missing' }}</strong></div>
            <div class="mini-stat"><span>Method</span><strong>GET</strong></div>
            <div class="mini-stat"><span>Endpoint</span><strong>{{ \App\Services\ApiFreaks\IpGeolocationService::ENDPOINT }}</strong></div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Lookup IP geolocation</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Masukkan alamat IP. Parameter <code>lang</code>, <code>fields</code>, dan <code>include</code> bersifat opsional.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">{{ $errorMessage }}</div>
            @endif

            <form wire:submit="run" class="settings-form">
                <div class="form-grid">
                    <div class="form-field form-field--wide">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <label for="geolocation_ip" class="form-label">IP Address</label>
                                <p class="form-help">Contoh: <code>8.8.8.8</code>.</p>
                            </div>
                            <span class="status-pill {{ $hasSavedApiKey ? 'status-pill--ready' : 'status-pill--pending' }}">{{ $hasSavedApiKey ? 'Key ready' : 'No key' }}</span>
                        </div>

                        <input id="geolocation_ip" type="text" wire:model="ip" class="form-input font-mono" placeholder="8.8.8.8" autocomplete="off" />
                        @error('ip') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="geolocation_lang" class="form-label">Language</label>
                        <select id="geolocation_lang" wire:model="lang" class="form-input">
                            <option value="en">English (en)</option>
                            <option value="id">Bahasa Indonesia (id)</option>
                        </select>
                        @error('lang') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="geolocation_fields" class="form-label">Fields</label>
                        <input id="geolocation_fields" type="text" wire:model="fields" class="form-input font-mono" placeholder="location" autocomplete="off" />
                        @error('fields') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-field">
                        <label for="geolocation_include" class="form-label">Include</label>
                        <input id="geolocation_include" type="text" wire:model="include" class="form-input font-mono" placeholder="security,hostnameFallbackLive" autocomplete="off" />
                        @error('include') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-actions form-actions--split">
                    <div class="form-inline-note">Parameter: <code>ip</code>, <code>lang</code>, <code>fields</code>, <code>include</code></div>
                    <button type="submit" class="primary-action" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="run">Cek Geolocation</span>
                        <span wire:loading wire:target="run">Memproses...</span>
                    </button>
                </div>
            </form>

            @if ($result)
                <div class="result-stack">
                    @foreach ([
                        ['title' => 'Location', 'rows' => $result['location']],
                        ['title' => 'Country Metadata', 'rows' => $result['country_metadata']],
                        ['title' => 'Network', 'rows' => $result['network']],
                        ['title' => 'Currency', 'rows' => $result['currency']],
                        ['title' => 'ASN', 'rows' => $result['asn']],
                        ['title' => 'Company', 'rows' => $result['company']],
                    ] as $table)
                        <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                    <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                        <tr><th class="px-4 py-3 font-semibold">{{ $table['title'] }} Field</th><th class="px-4 py-3 font-semibold">Value</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                        @forelse ($table['rows'] as $field => $value)
                                            <tr wire:key="geo-{{ strtolower(str_replace(' ', '-', $table['title'])) }}-{{ $field }}">
                                                <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $field }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">
                                                    @if (is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @elseif (is_array($value))
                                                        {{ json_encode($value, JSON_UNESCAPED_SLASHES) }}
                                                    @else
                                                        {{ filled($value) ? $value : '-' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="2" class="px-4 py-4 text-center text-[rgb(var(--app-muted))]">Data tidak tersedia.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach

                    @php
                        $timezone = $result['time_zone'] ?? [];
                        $dstStart = $timezone['dst_start'] ?? null;
                        $dstEnd = $timezone['dst_end'] ?? null;
                        $timezoneMain = collect($timezone)->except(['dst_start', 'dst_end'])->all();
                    @endphp

                    @if ($timezoneMain)
                        <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                    <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                        <tr><th class="px-4 py-3 font-semibold">Time Zone Field</th><th class="px-4 py-3 font-semibold">Value</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                        @foreach ($timezoneMain as $field => $value)
                                            <tr wire:key="geo-timezone-{{ $field }}">
                                                <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $field }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">
                                                    @if (is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ filled((string) $value) ? $value : '-' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($dstStart)
                        <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                    <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                        <tr><th class="px-4 py-3 font-semibold">DST Start Field</th><th class="px-4 py-3 font-semibold">Value</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                        @foreach ($dstStart as $field => $value)
                                            <tr wire:key="geo-dst-start-{{ $field }}">
                                                <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">dst_start.{{ $field }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">
                                                    @if (is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ filled((string) $value) ? $value : '-' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if ($dstEnd)
                        <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                    <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                        <tr><th class="px-4 py-3 font-semibold">DST End Field</th><th class="px-4 py-3 font-semibold">Value</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                        @foreach ($dstEnd as $field => $value)
                                            <tr wire:key="geo-dst-end-{{ $field }}">
                                                <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">dst_end.{{ $field }}</td>
                                                <td class="px-4 py-4 text-[rgb(var(--app-muted))]">
                                                    @if (is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ filled((string) $value) ? $value : '-' }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Raw JSON response</span>
                            <strong>{{ $result['ip'] ?? $ip }}</strong>
                        </div>
                        <pre>{{ $this->prettyResponse }}</pre>
                    </div>
                </div>
            @endif
        </section>

        <aside class="settings-side">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div><p class="section-kicker">Request spec</p><h3>Parameter</h3></div>
                </div>
                <div class="settings-checklist">
                    <article><strong>ip</strong><p>Alamat IP yang akan di-lookup. Contoh: <code>8.8.8.8</code>.</p></article>
                    <article><strong>lang</strong><p>Kode bahasa untuk response (<code>en</code> atau <code>id</code>).</p></article>
                    <article><strong>fields</strong><p>Field utama yang ingin ditampilkan (contoh: <code>location</code>).</p></article>
                    <article><strong>include</strong><p>Data tambahan yang disertakan, pisahkan dengan koma (contoh: <code>security,hostnameFallbackLive</code>).</p></article>
                    <article><strong>Identifier API key</strong><p><code>apifreaks_provider</code></p></article>
                </div>
            </section>
        </aside>
    </div>
</div>
