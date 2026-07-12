<x-app-layout>
    <x-slot name="header">
        IP Geolocation API
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApiFreaks\IpGeolocationLookup::class)
    </section>
</x-app-layout>
