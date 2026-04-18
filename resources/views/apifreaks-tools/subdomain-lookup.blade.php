<x-app-layout>
    <x-slot name="header">
        Subdomain Lookup API
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApiFreaks\SubdomainLookup::class)
    </section>
</x-app-layout>
