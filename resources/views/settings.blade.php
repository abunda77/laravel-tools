<x-app-layout>
    <x-slot name="header">
        Settings
    </x-slot>

    <section class="page-stack">

        {{-- ── Tab Navigation ─────────────────────────────────────────────── --}}
        <div class="settings-tabs" x-data="{ tab: window.location.hash === '#api-keys' ? 'api-keys' : 'general' }">
            <nav class="settings-tabs__nav" role="tablist" aria-label="Settings tabs">
                <button
                    role="tab"
                    :aria-selected="tab === 'general'"
                    :class="{ 'settings-tabs__tab--active': tab === 'general' }"
                    class="settings-tabs__tab"
                    @click="tab = 'general'; history.replaceState(null, '', '#general')"
                >
                    General
                </button>
                <button
                    role="tab"
                    :aria-selected="tab === 'api-keys'"
                    :class="{ 'settings-tabs__tab--active': tab === 'api-keys' }"
                    class="settings-tabs__tab"
                    @click="tab = 'api-keys'; history.replaceState(null, '', '#api-keys')"
                >
                    API Keys
                </button>
            </nav>

            <div class="settings-tabs__panels">
                <div x-show="tab === 'general'" x-cloak>
                    @livewire(\App\Livewire\Settings\GeneralSettings::class)
                </div>

                <div x-show="tab === 'api-keys'" x-cloak>
                    @livewire(\App\Livewire\Settings\ApiKeyManager::class)
                </div>
            </div>
        </div>

    </section>
</x-app-layout>

<style>
/* ── Settings Tabs ─────────────────────────────────────────── */
.settings-tabs {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.settings-tabs__nav {
    display: flex;
    gap: 0.25rem;
    padding: 0.25rem;
    background: var(--surface-2, rgba(255,255,255,0.04));
    border: 1px solid var(--border-subtle, rgba(255,255,255,0.08));
    border-radius: 0.6rem;
    width: fit-content;
}

.settings-tabs__tab {
    padding: 0.45rem 1.15rem;
    border-radius: 0.45rem;
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 500;
    transition: background 0.15s, color 0.15s;
    color: inherit;
    opacity: 0.65;
}

.settings-tabs__tab:hover { opacity: 0.9; }

.settings-tabs__tab--active {
    background: var(--primary, #6366f1);
    color: #fff;
    opacity: 1;
}

.settings-tabs__panels > div {
    min-height: 0;
}

[x-cloak] { display: none !important; }
</style>
