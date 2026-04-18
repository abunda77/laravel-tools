<x-app-layout>
    <x-slot name="header">
        Domain WHOIS Lookup API
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApiFreaks\DomainWhoisLookup::class)
    </section>
</x-app-layout>
