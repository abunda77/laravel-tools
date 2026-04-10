<x-app-layout>
    <x-slot name="header">
        Workspace ChatBot
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Workspace\ChatBot::class)
    </section>
</x-app-layout>
