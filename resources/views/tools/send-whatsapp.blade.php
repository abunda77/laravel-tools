<x-app-layout>
    <x-slot name="header">
        Kirim WA / Send Whatsapp
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\Tools\SendWhatsapp::class)
    </section>
</x-app-layout>
