<x-app-layout>
    <x-slot name="header">
        Wall Meter
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Tools\WallMeter::class)
    </section>
</x-app-layout>
