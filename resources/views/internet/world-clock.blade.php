<x-app-layout>
    <x-slot name="header">
        World Clock
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Internet\WorldClock::class)
    </section>
</x-app-layout>
