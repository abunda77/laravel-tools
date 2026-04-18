<x-app-layout>
    <x-slot name="header">
        Domain Search API
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApiFreaks\DomainSearch::class)
    </section>
</x-app-layout>
