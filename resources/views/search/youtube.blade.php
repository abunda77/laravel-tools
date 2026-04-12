<x-app-layout>
    <x-slot name="header">
        Youtube Search
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\YoutubeSearch::class)
    </section>
</x-app-layout>
