<x-app-layout>
    <x-slot name="header">
        Quotes Anime
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\AnimeQuoteSearch::class)
    </section>
</x-app-layout>
