<x-app-layout>
    <x-slot name="header">
        Download Youtube Short
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ExternalApi\DownloaderWorkbench::class, ['selectedProvider' => 'ytshorts'])
    </section>
</x-app-layout>
