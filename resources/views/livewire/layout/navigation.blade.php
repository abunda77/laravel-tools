<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public array $sections = [];

    public function mount(): void
    {
        $this->sections = [
            [
                'label' => 'Workspace',
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard'],
                    ['label' => 'Downloader', 'route' => 'external-api', 'icon' => 'api'],
                    ['label' => 'Custom Scripts', 'route' => 'custom-scripts', 'icon' => 'scripts'],
                ],
            ],
            [
                'label' => 'Modules',
                'items' => [
                    ['label' => 'Search', 'route' => 'search', 'icon' => 'search'],
                    ['label' => 'Tools', 'route' => 'tools', 'icon' => 'tools'],
                    ['label' => 'Internet', 'route' => 'internet', 'icon' => 'internet'],
                ],
            ],
            [
                'label' => 'Operations',
                'items' => [
                    ['label' => 'Execution History', 'route' => 'execution-history', 'icon' => 'history'],
                    ['label' => 'Settings', 'route' => 'settings', 'icon' => 'settings'],
                    ['label' => 'Profile', 'route' => 'profile', 'icon' => 'profile'],
                ],
            ],
        ];
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $iconPaths = [
        'dashboard' => 'M3.75 4.5A.75.75 0 0 1 4.5 3.75h6a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-.75.75h-6a.75.75 0 0 1-.75-.75v-6ZM12.75 4.5a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 .75.75v3a.75.75 0 0 1-.75.75h-6a.75.75 0 0 1-.75-.75v-3ZM12.75 13.5a.75.75 0 0 1 .75-.75h6a.75.75 0 0 1 .75.75v6a.75.75 0 0 1-.75.75h-6a.75.75 0 0 1-.75-.75v-6ZM3.75 16.5a3.75 3.75 0 1 1 7.5 0 3.75 3.75 0 0 1-7.5 0Z',
        'api' => 'M5.25 6h13.5M5.25 12h13.5M5.25 18h13.5M2.25 6h.008v.008H2.25V6Zm0 6h.008v.008H2.25V12Zm0 6h.008v.008H2.25V18Z',
        'scripts' => 'M7.5 8.25 3.75 12l3.75 3.75M16.5 8.25 20.25 12l-3.75 3.75M13.5 4.5 10.5 19.5',
        'history' => 'M12 6v6l4.5 2.25M21 12a9 9 0 1 1-3.17-6.87',
        'settings' => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.592c.55 0 1.02.398 1.11.94l.213 1.277c.066.393.326.726.682.894.355.169.771.15 1.11-.05l1.1-.648a1.125 1.125 0 0 1 1.454.2l1.833 1.833c.39.39.473.99.2 1.454l-.648 1.1a1.125 1.125 0 0 0-.05 1.11c.168.356.5.616.894.682l1.277.213c.542.09.94.56.94 1.11v2.592c0 .55-.398 1.02-.94 1.11l-1.277.213a1.125 1.125 0 0 0-.894.682c-.169.355-.15.771.05 1.11l.648 1.1c.273.464.19 1.064-.2 1.454l-1.833 1.833a1.125 1.125 0 0 1-1.454.2l-1.1-.648a1.125 1.125 0 0 0-1.11-.05 1.125 1.125 0 0 0-.682.894l-.213 1.277c-.09.542-.56.94-1.11.94h-2.592c-.55 0-1.02-.398-1.11-.94l-.213-1.277a1.125 1.125 0 0 0-.682-.894 1.125 1.125 0 0 0-1.11.05l-1.1.648a1.125 1.125 0 0 1-1.454-.2l-1.833-1.833a1.125 1.125 0 0 1-.2-1.454l.648-1.1c.2-.339.219-.755.05-1.11a1.125 1.125 0 0 0-.894-.682l-1.277-.213A1.125 1.125 0 0 1 3 14.296v-2.592c0-.55.398-1.02.94-1.11l1.277-.213a1.125 1.125 0 0 0 .894-.682 1.125 1.125 0 0 0-.05-1.11l-.648-1.1a1.125 1.125 0 0 1 .2-1.454l1.833-1.833a1.125 1.125 0 0 1 1.454-.2l1.1.648c.339.2.755.219 1.11.05.356-.168.616-.5.682-.894l.213-1.277ZM15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z',
        'profile' => 'M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.25a8.25 8.25 0 0 1 14.998 0',
        'search' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z',
        'tools' => 'M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437 1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008Z',
        'internet' => 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253M3.157 7.582A8.959 8.959 0 0 0 3 12c0 .778.099 1.533.284 2.253',
    ];

    $quickStats = [
        ['label' => 'Mode', 'value' => 'Livewire'],
        ['label' => 'Auth', 'value' => 'Breeze'],
        ['label' => 'Status', 'value' => 'Ready'],
    ];
@endphp

<div>
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="app-shell__overlay"
        @click="sidebarOpen = false"
    ></div>

    <aside class="app-sidebar" :class="{ 'is-open': sidebarOpen }">
        <div class="app-sidebar__brand">
            <a href="{{ route('dashboard') }}" wire:navigate class="app-sidebar__logo">
                <span class="app-sidebar__logo-mark">LT</span>
                <span>
                    <span class="app-sidebar__brand-title">{{ config('app.name', 'Laravel Tools') }}</span>
                    <span class="app-sidebar__brand-subtitle">Ops workspace</span>
                </span>
            </a>

            <button type="button" class="app-sidebar__close" @click="sidebarOpen = false">
                <span class="sr-only">Close navigation</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="app-sidebar__meta">
            <div>
                <p class="app-sidebar__meta-label">Signed in as</p>
                <p class="app-sidebar__meta-value">{{ auth()->user()->name }}</p>
                <p class="app-sidebar__meta-subtle">{{ auth()->user()->email }}</p>
            </div>

            <div class="app-sidebar__stats">
                @foreach ($quickStats as $stat)
                    <div>
                        <span>{{ $stat['label'] }}</span>
                        <strong>{{ $stat['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <nav class="app-sidebar__nav">
            @foreach ($sections as $section)
                <div class="app-sidebar__section">
                    <p class="app-sidebar__section-label">{{ $section['label'] }}</p>

                    <div class="app-sidebar__links">
                        @foreach ($section['items'] as $item)
                            <a
                                href="{{ route($item['route']) }}"
                                wire:navigate
                                @click="sidebarOpen = false"
                                class="app-sidebar__link {{ request()->routeIs($item['route']) ? 'is-active' : '' }}"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="{{ $iconPaths[$item['icon']] ?? $iconPaths['dashboard'] }}" />
                                </svg>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="app-sidebar__footer">
            <button wire:click="logout" type="button" class="app-sidebar__logout">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                <span>Log out</span>
            </button>
        </div>
    </aside>
</div>
