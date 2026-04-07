<x-app-layout>
    <x-slot name="header">
        Profile
    </x-slot>

    <section class="page-stack">
        <div class="space-y-6">
            <div class="surface-panel">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="surface-panel">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="surface-panel">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </section>
</x-app-layout>
