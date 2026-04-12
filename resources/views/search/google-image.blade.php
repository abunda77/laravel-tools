<x-app-layout>
    <x-slot name="header">
        Google Image Search
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\GoogleImageSearch::class)
    </section>
</x-app-layout>
