<x-app-layout>
    <x-slot name="header">
        Search
    </x-slot>

    <section class="page-stack">
        <div class="hero-panel">
            <div>
                <p class="section-kicker">Search Module</p>
                <h2 class="hero-panel__title">Pencarian terpusat dari berbagai sumber.</h2>
                <p class="hero-panel__text">
                    Modul Search menampung endpoint pencarian produk dan dataset terstruktur dari provider eksternal.
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Available tools</p>
                        <h3>Tokopedia</h3>
                    </div>
                    <a href="{{ route('search.tokopedia') }}" wire:navigate class="primary-action">
                        Buka tool
                    </a>
                </div>

                <p class="surface-panel__text">
                    Cari produk Tokopedia lewat endpoint <code>/search/tokopedia</code> dengan API key
                    <code>downloader_provider</code>, lalu lihat hasil dalam mode <code>card</code> atau <code>table</code>.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Available tools</p>
                        <h3>Unsplash</h3>
                    </div>
                    <a href="{{ route('search.unsplash') }}" wire:navigate class="primary-action">
                        Buka tool
                    </a>
                </div>

                <p class="surface-panel__text">
                    Cari gambar Unsplash lewat endpoint <code>/search/unsplash</code> dengan API key
                    <code>downloader_provider</code>, lalu lihat hasil sebagai galeri, tabel URL, dan raw JSON.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Available tools</p>
                        <h3>Google Image</h3>
                    </div>
                    <a href="{{ route('search.google-image') }}" wire:navigate class="primary-action">
                        Buka tool
                    </a>
                </div>

                <p class="surface-panel__text">
                    Cari gambar Google lewat endpoint <code>/search/gimage</code> dengan API key
                    <code>downloader_provider</code>, lalu lihat hasil sebagai preview gambar, tabel URL, dan raw JSON.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Available tools</p>
                        <h3>TikTok Video</h3>
                    </div>
                    <a href="{{ route('search.tiktok') }}" wire:navigate class="primary-action">
                        Buka tool
                    </a>
                </div>

                <p class="surface-panel__text">
                    Cari video TikTok lewat endpoint <code>/search/tiktok</code> dengan API key
                    <code>downloader_provider</code>, lalu lihat hasil sebagai preview video dan daftar URL.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Available tools</p>
                        <h3>Youtube</h3>
                    </div>
                    <a href="{{ route('search.youtube') }}" wire:navigate class="primary-action">
                        Buka tool
                    </a>
                </div>

                <p class="surface-panel__text">
                    Cari video Youtube lewat endpoint <code>/search/youtube</code> dengan API key
                    <code>downloader_provider</code>, lalu lihat hasil dalam mode <code>card</code> atau <code>table</code>.
                </p>
            </div>
        </div>
    </section>
</x-app-layout>
