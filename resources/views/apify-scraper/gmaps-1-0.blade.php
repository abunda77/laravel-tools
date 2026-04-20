<x-app-layout>
    <x-slot name="header">
        Apify Scraper - GMaps 1.0
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ApifyScraper\GmapsScraper::class)
    </section>
</x-app-layout>
