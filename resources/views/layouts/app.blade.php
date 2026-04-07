<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($header) ? trim(strip_tags($header)) . ' - ' : '' }}{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
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

                            <button type="button" class="app-topbar__menu lg:hidden" @click="sidebarOpen = true">
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
