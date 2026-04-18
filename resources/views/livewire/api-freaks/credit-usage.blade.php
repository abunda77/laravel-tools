<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">ApiFreaks module</p>
            <h3>Credit Usage API</h3>
            <p>
                Cek pemakaian kredit ApiFreaks dari endpoint <code>{{ \App\Services\ApiFreaks\CreditUsageService::ENDPOINT }}</code>.
                API key otomatis memakai <code>apifreaks_provider</code>.
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
                <span>Endpoint</span>
                <strong>{{ \App\Services\ApiFreaks\CreditUsageService::ENDPOINT }}</strong>
            </div>
        </div>
    </section>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr)_24rem]">
        <section class="surface-panel">
            <div class="surface-panel__header">
                <div>
                    <h3>Load credit usage</h3>
                    <p class="surface-panel__text surface-panel__text--tight">
                        Jalankan request untuk melihat status user, subscription credits, surcharge, dan one-off credits.
                    </p>
                </div>
            </div>

            @if ($errorMessage)
                <div class="form-alert form-alert--danger">
                    {{ $errorMessage }}
                </div>
            @endif

            <div class="form-actions form-actions--split">
                <div class="form-inline-note">
                    Header auth: <code>X-apiKey</code>
                </div>

                <button type="button" wire:click="run" class="primary-action" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="run">Ambil data kredit</span>
                    <span wire:loading wire:target="run">Memproses...</span>
                </button>
            </div>

            @if ($result)
                <div class="result-stack">
                    <div class="overflow-hidden rounded-[1.6rem] border border-[rgb(var(--app-line))]">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-[rgb(var(--app-line))] text-left text-sm">
                                <thead class="bg-white/70 text-[rgb(var(--app-muted))]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Field</th>
                                        <th class="px-4 py-3 font-semibold">Value</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgb(var(--app-line))] bg-[rgb(246_245_240_/_0.45)]">
                                    @foreach ($result['rows'] as $row)
                                        <tr wire:key="credit-usage-{{ $row['field'] }}">
                                            <td class="px-4 py-4 font-semibold text-[rgb(var(--app-ink))]">{{ $row['field'] }}</td>
                                            <td class="px-4 py-4 font-mono text-[rgb(var(--app-muted))]">{{ $row['value'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="json-viewer">
                        <div class="json-viewer__header">
                            <span>Raw JSON response</span>
                            <strong>{{ \Illuminate\Support\Number::format(count($result['rows'])) }} fields</strong>
                        </div>
                        <pre>{{ $this->prettyResponse }}</pre>
                    </div>
                </div>
            @endif
        </section>

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
                        <p><code>https://api.apifreaks.com</code></p>
                    </article>
                    <article>
                        <strong>Identifier API key</strong>
                        <p><code>apifreaks_provider</code></p>
                    </article>
                    <article>
                        <strong>Response mode</strong>
                        <p>Field-value table dari object JSON.</p>
                    </article>
                </div>
            </section>
        </aside>
    </div>
</div>
