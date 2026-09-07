<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel Tools') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased">
        <div class="wlc-root">

            {{-- ── Topbar ──────────────────────────────────────────────── --}}
            <header>
                <nav class="wlc-topbar">
                    <a href="/" class="wlc-brand">
                        <x-application-logo class="size-7" />
                        {{ config('app.name', 'Laravel Tools') }}
                    </a>

                    <div class="flex items-center gap-2">
                        @auth
                            <a href="{{ route('dashboard') }}" wire:navigate class="wlc-nav-link--primary">
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="wlc-nav-link">Masuk</a>
                        @endauth
                    </div>
                </nav>
            </header>

            {{-- ── Hero ────────────────────────────────────────────────── --}}
            <section class="wlc-hero">
                <span class="wlc-hero__eyebrow">
                    <span class="wlc-hero__eyebrow-dot" aria-hidden="true"></span>
                    Panel Internal
                </span>

                <h1 class="wlc-hero__heading">
                    Satu ruang kerja untuk semua automasi &amp; API.
                </h1>

                <p class="wlc-hero__sub">
                    Laravel Tools menyatukan integrasi API eksternal, skrip kustom,
                    dan alat operasional dalam satu dashboard yang rapi — tanpa perlu berpindah-pindah tab.
                </p>

                <div class="wlc-hero__actions">
                    @auth
                        <a href="{{ route('dashboard') }}" class="wlc-cta-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/></svg>
                            Buka Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="wlc-cta-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" x2="3" y1="12" y2="12"/></svg>
                            Masuk ke Dashboard
                        </a>
                        <a href="{{ route('login') }}" class="wlc-cta-secondary">
                            Pelajari fitur
                        </a>
                    @endauth
                </div>
            </section>

            {{-- ── Stats strip ─────────────────────────────────────────── --}}
            <div class="wlc-stats" aria-label="Ringkasan fitur">
                <dl class="wlc-stats__inner">
                    <div class="wlc-stat">
                        <dt class="wlc-stat__label">Integrasi API</dt>
                        <dd class="wlc-stat__value">10+</dd>
                    </div>
                    <div class="wlc-stat">
                        <dt class="wlc-stat__label">Alat aktif</dt>
                        <dd class="wlc-stat__value">15+</dd>
                    </div>
                    <div class="wlc-stat">
                        <dt class="wlc-stat__label">Antarmuka</dt>
                        <dd class="wlc-stat__value">Livewire</dd>
                    </div>
                    <div class="wlc-stat">
                        <dt class="wlc-stat__label">AI Provider</dt>
                        <dd class="wlc-stat__value">4</dd>
                    </div>
                </dl>
            </div>

            {{-- ── Module grid ──────────────────────────────────────────── --}}
            <section class="wlc-modules" aria-labelledby="modules-heading">
                <div class="wlc-section-head">
                    <p class="wlc-section-head__kicker">Modul tersedia</p>
                    <h2 id="modules-heading" class="wlc-section-head__title">Semua yang kamu butuhkan, sudah ada di sini.</h2>
                </div>

                <div class="wlc-grid" role="list">

                    {{-- Search --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </div>
                        <p class="wlc-card__title">Pencarian Produk</p>
                        <p class="wlc-card__desc">Cari produk di marketplace populer lewat API terintegrasi.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                    {{-- YouTube --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/></svg>
                        </div>
                        <p class="wlc-card__title">YouTube Downloader</p>
                        <p class="wlc-card__desc">Unduh video &amp; audio YouTube dengan pilihan kualitas.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                    {{-- AI Chat --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>
                        </div>
                        <p class="wlc-card__title">AI ChatBot</p>
                        <p class="wlc-card__desc">Chat dengan berbagai model AI: OpenAI, Gemini, Anthropic, Perplexity.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                    {{-- Wilayah --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
                        </div>
                        <p class="wlc-card__title">Wilayah Indonesia</p>
                        <p class="wlc-card__desc">Data provinsi, kota, kecamatan, dan kelurahan seluruh Indonesia.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                    {{-- Image AI --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
                        </div>
                        <p class="wlc-card__title">AI Image</p>
                        <p class="wlc-card__desc">Generate dan edit gambar menggunakan Freepik AI.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                    {{-- Bookmark --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/></svg>
                        </div>
                        <p class="wlc-card__title">Bookmark</p>
                        <p class="wlc-card__desc">Simpan &amp; kelola tautan penting dengan pratinjau otomatis.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                    {{-- WhatsApp --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                        </div>
                        <p class="wlc-card__title">WhatsApp Sender</p>
                        <p class="wlc-card__desc">Kirim pesan WhatsApp massal dari antrean internal.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                    {{-- Settings --}}
                    <a href="{{ route('login') }}" class="wlc-card" role="listitem">
                        <div class="wlc-card__icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        <p class="wlc-card__title">Pengaturan</p>
                        <p class="wlc-card__desc">Kelola API key, model AI, dan konfigurasi sistem secara terpusat.</p>
                        <span class="wlc-card__arrow" aria-hidden="true">→</span>
                    </a>

                </div>
            </section>

            {{-- ── Footer ───────────────────────────────────────────────── --}}
            <footer class="wlc-footer">
                <p>{{ config('app.name', 'Laravel Tools') }} &middot; Laravel v{{ Illuminate\Foundation\Application::VERSION }} &middot; PHP v{{ PHP_VERSION }}</p>
            </footer>

        </div>
    </body>
</html>
