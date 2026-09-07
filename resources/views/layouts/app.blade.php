<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    x-data="{
        theme: localStorage.getItem('app-theme') ?? 'system',
        get isDark() {
            if (this.theme === 'dark') return true;
            if (this.theme === 'light') return false;
            return window.matchMedia('(prefers-color-scheme: dark)').matches;
        },
        toggleTheme() {
            this.theme = this.isDark ? 'light' : 'dark';
            localStorage.setItem('app-theme', this.theme);
            this.$el.dataset.theme = this.isDark ? 'dark' : 'light';
        },
        initTheme() {
            this.$el.dataset.theme = this.isDark ? 'dark' : 'light';
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                if (this.theme === 'system') this.$el.dataset.theme = this.isDark ? 'dark' : 'light';
            });
        }
    }"
    x-init="initTheme()"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($header) ? trim(strip_tags($header)) . ' - ' : '' }}{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script>
            (function () {
                var t = localStorage.getItem('app-theme');
                var dark = t === 'dark' || (t !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            })();
        </script>
        @livewireStyles
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased app-shell-body">
        <div class="app-shell min-h-screen">
            <div x-data="{ sidebarOpen: false }" class="app-shell__frame">
                <livewire:layout.navigation />

                <div class="app-content">
                    <header class="app-topbar">
                        <div>
                            <p class="app-topbar__eyebrow">Laravel 13 workspace</p>
                            <h1 class="app-topbar__title">{{ isset($header) ? trim(strip_tags($header)) : 'Workspace' }}</h1>
                        </div>

                        <div class="app-topbar__actions">
                            <div class="app-topbar__badge">
                                <span class="app-topbar__badge-dot"></span>
                                <span>Authenticated</span>
                            </div>

                            {{-- Dark / Light mode toggle --}}
                            <button
                                type="button"
                                class="app-topbar__menu"
                                @click="toggleTheme()"
                                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                            >
                                {{-- Moon: shown in light mode --}}
                                <svg x-show="!isDark" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                                </svg>
                                {{-- Sun: shown in dark mode --}}
                                <svg x-show="isDark" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5" aria-hidden="true">
                                    <circle cx="12" cy="12" r="5" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42" />
                                </svg>
                            </button>

                            <button type="button" class="app-topbar__menu app-topbar__menu--nav" @click="sidebarOpen = true">
                                <span class="sr-only">Open navigation</span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-6">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        </div>
                    </header>

                    <main class="app-shell__main">
                        {{ $slot }}
                    </main>
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
