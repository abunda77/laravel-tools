<x-app-layout>
    <x-slot name="header">
        Info System
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Operations\InfoSystem::class)
    </section>
</x-app-layout>
