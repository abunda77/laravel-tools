<x-app-layout>
    <x-slot name="header">
        Downloader
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ExternalApi\DownloaderWorkbench::class)
    </section>
</x-app-layout>
