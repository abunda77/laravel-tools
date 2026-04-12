<x-app-layout>
    <x-slot name="header">
        TikTok Video Search
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Search\TiktokVideoSearch::class)
    </section>
</x-app-layout>
