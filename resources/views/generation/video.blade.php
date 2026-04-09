<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl leading-tight text-[rgb(var(--app-ink))]">
            {{ __('Generation Video AI') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <livewire:generation.video-generation />
        </div>
    </div>
</x-app-layout>
