<x-app-layout>
    <x-slot name="header">
        Historical Commodity Prices API
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApiFreaks\HistoricalCommodityPrices::class)
    </section>
</x-app-layout>
