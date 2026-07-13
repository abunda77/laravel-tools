<x-app-layout>
    <x-slot name="header">
        Holiday
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Tools\Holiday::class)
    </section>
</x-app-layout>
