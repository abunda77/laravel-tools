<x-app-layout>
    <x-slot name="header">
        Freepik Image
    </x-slot>

    <section class="page-stack">
        <div class="hero-panel">
            <div>
                <p class="section-kicker">Search Module</p>
                <h2 class="hero-panel__title">Freepik image search via Magnific API.</h2>
                <p class="hero-panel__text">
                    Cari resource Freepik, buka detailnya, dan ambil link download per format dalam satu panel kerja.
                </p>
            </div>
        </div>

        @livewire(\App\Livewire\Search\FreepikImage::class)
    </section>
</x-app-layout>
