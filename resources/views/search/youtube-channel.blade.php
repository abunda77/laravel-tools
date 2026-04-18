<x-app-layout>
    <x-slot name="header">
        Youtube Channel
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\YoutubeChannel::class)
    </section>
</x-app-layout>
