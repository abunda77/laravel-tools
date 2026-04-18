<x-app-layout>
    <x-slot name="header">
        Youtube Finder
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\YoutubeFinder::class)
    </section>
</x-app-layout>
