<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel Tools') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="auth-login-shell">
            <div class="auth-ambient auth-ambient--one"></div>
            <div class="auth-ambient auth-ambient--two"></div>

            <main class="auth-login-stage">
                <section class="auth-login-intro" aria-labelledby="home-heading">
                    <a href="/" class="auth-login-brand" aria-label="{{ config('app.name', 'Laravel Tools') }}">
                        <x-application-logo class="auth-login-logo" />
                        <span>{{ config('app.name', 'Laravel Tools') }}</span>
                    </a>

                    <div class="auth-login-copy">
                        <p class="auth-login-kicker">Secure workspace</p>
                        <h1 id="home-heading">Ruang kerja operasional untuk automasi internal.</h1>
                        <p>
                            Akses dashboard, workflow API, dan alat produktivitas dari satu halaman yang konsisten.
                        </p>
                    </div>

                    <div class="auth-signal-panel" aria-hidden="true">
                        <div class="auth-signal-panel__header">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="auth-signal-panel__body">
                            <div class="auth-signal-panel__meter auth-signal-panel__meter--wide"></div>
                            <div class="auth-signal-panel__meter"></div>
                            <div class="auth-signal-panel__grid">
                                <span></span>
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="auth-login-panel" aria-label="Home navigation">
                    <div class="auth-login-panel__heading">
                        <p>Laravel Tools</p>
                        <h2>Mulai kerja</h2>
                    </div>

                    <div class="auth-home-actions">
                        @auth
                            <a href="{{ route('dashboard') }}" class="auth-login-submit">
                                Buka dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="auth-login-submit">
                                Masuk ke dashboard
                            </a>
                        @endauth
                    </div>

                    <div class="auth-home-links" aria-label="Available workspaces">
                        <a href="{{ route('tools') }}">Tools</a>
                        <a href="{{ route('internet') }}">Internet</a>
                        <a href="{{ route('external-api') }}">External API</a>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
