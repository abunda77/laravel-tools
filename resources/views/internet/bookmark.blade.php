<x-app-layout>
    <x-slot name="header">
        Bookmark
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Internet\BookmarkIndex::class)
    </section>
</x-app-layout>