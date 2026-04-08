<x-app-layout>
    <x-slot name="header">
        Backup Data ApiKey
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Operations\ApiKeyBackupManager::class)
    </section>
</x-app-layout>
