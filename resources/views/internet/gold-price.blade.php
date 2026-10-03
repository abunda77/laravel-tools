<x-app-layout>
    <x-slot name="header">
        Gold Price
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Internet\GoldPrice::class)
    </section>
</x-app-layout>