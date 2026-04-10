<x-app-layout>
    <x-slot name="header">
        Cek Resi
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Tools\CekResi::class)
    </section>
</x-app-layout>
