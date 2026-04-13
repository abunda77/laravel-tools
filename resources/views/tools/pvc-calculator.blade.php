<x-app-layout>
    <x-slot name="header">
        Calculator PVC
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Tools\PvcCalculator::class)
    </section>
</x-app-layout>
