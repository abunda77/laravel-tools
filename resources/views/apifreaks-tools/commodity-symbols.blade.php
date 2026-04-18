<x-app-layout>
    <x-slot name="header">
        Commodity Symbols
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApiFreaks\CommoditySymbols::class)
    </section>
</x-app-layout>
