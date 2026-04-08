<x-app-layout>
    <x-slot name="header">
        Internet
    </x-slot>

    <section class="page-stack">
        <div class="hero-panel">
            <div>
                <p class="section-kicker">Internet Module</p>
                <h2 class="hero-panel__title">Akses dan manajemen resource dari internet.</h2>
                <p class="hero-panel__text">
                    Modul Internet sekarang memiliki tool pertama untuk cek kurs mata uang lewat API.co.id.
                </p>
            </div>
        </div>

        <div class="surface-panel surface-panel--compact">
            <div class="surface-panel__header">
                <div>
                    <p class="section-kicker">Available tools</p>
                    <h3>Kurs Mata Uang</h3>
                </div>
                <a href="{{ route('internet.currency-exchange-rate') }}" wire:navigate class="primary-action">
                    Buka tool
                </a>
            </div>

            <p class="surface-panel__text">
                Gunakan pair seperti <code>USDIDR</code> untuk melihat rate terbaru dari endpoint <code>/currency/exchange-rate</code>.
            </p>
        </div>
    </section>
</x-app-layout>
