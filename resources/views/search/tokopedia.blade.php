<x-app-layout>
    <x-slot name="header">
        Tokopedia Search
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\TokopediaSearch::class)
    </section>
</x-app-layout>
