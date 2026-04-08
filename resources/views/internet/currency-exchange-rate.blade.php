<x-app-layout>
    <x-slot name="header">
        Kurs Mata Uang
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Internet\CurrencyExchangeRate::class)
    </section>
</x-app-layout>
