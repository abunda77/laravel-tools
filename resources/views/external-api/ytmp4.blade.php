<x-app-layout>
    <x-slot name="header">
        Download Youtube MP4
    </x-slot>

    <section class="page-stack">
        @livewire(\App\Livewire\ExternalApi\DownloaderWorkbench::class, ['selectedProvider' => 'ytmp4'])
    </section>
</x-app-layout>
