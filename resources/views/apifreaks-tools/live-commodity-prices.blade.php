<x-app-layout>
    <x-slot name="header">
        Live Commodity Prices API
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApiFreaks\LiveCommodityPrices::class)
    </section>
</x-app-layout>
