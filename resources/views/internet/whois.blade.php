<x-app-layout>
    <x-slot name="header">
        Whois
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Internet\Whois::class)
    </section>
</x-app-layout>
