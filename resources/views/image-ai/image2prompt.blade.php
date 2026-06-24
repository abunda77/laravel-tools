<x-app-layout>
    <x-slot name="header">
        Image2Prompt
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ImageAi\ImageToPrompt::class)
    </section>
</x-app-layout>
