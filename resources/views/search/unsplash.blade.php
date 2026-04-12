<x-app-layout>
    <x-slot name="header">
        Unsplash Search
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\UnsplashSearch::class)
    </section>
</x-app-layout>
