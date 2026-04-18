<x-app-layout>
    <x-slot name="header">
        ApiFreaks Tools
    </x-slot>

    <section class="page-stack">
        <div class="hero-panel">
            <div>
                <p class="section-kicker">ApiFreaks module</p>
                <h2 class="hero-panel__title">Toolkit domain, WHOIS, dan commodity market dari ApiFreaks.</h2>
                <p class="hero-panel__text">
                    Semua endpoint di grup ini memakai identifier API key <code>apifreaks_provider</code> dari Settings.
                    Setiap tool menampilkan response JSON dalam bentuk tabel agar cepat dibaca dan diaudit.
                </p>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            @foreach ([
                ['title' => 'Credit Usage API', 'route' => 'apifreaks-tools.credit-usage', 'description' => 'Pantau penggunaan kredit subscription dan one-off dari akun ApiFreaks.'],
                ['title' => 'Domain WHOIS Lookup API', 'route' => 'apifreaks-tools.domain-whois-lookup', 'description' => 'Lihat data WHOIS live domain dalam format terstruktur.'],
                ['title' => 'Domain WHOIS History Lookup API', 'route' => 'apifreaks-tools.domain-whois-history-lookup', 'description' => 'Audit perubahan histori WHOIS dan registrar sebuah domain.'],
                ['title' => 'Domain Search API', 'route' => 'apifreaks-tools.domain-search', 'description' => 'Cek ketersediaan domain dengan source dns atau whois.'],
                ['title' => 'Subdomain Lookup API', 'route' => 'apifreaks-tools.subdomain-lookup', 'description' => 'Tampilkan daftar subdomain, first seen, last seen, dan inactive date.'],
                ['title' => 'Commodity Symbols', 'route' => 'apifreaks-tools.commodity-symbols', 'description' => 'Ambil daftar symbol komoditas aktif beserta kategori, mata uang, unit, dan interval update.'],
                ['title' => 'Live Commodity Prices API', 'route' => 'apifreaks-tools.live-commodity-prices', 'description' => 'Harga komoditas live berdasarkan symbol, update period, dan quote currency.'],
                ['title' => 'Historical Commodity Prices API', 'route' => 'apifreaks-tools.historical-commodity-prices', 'description' => 'Harga open, high, low, close komoditas pada tanggal tertentu.'],
            ] as $tool)
                <div class="surface-panel surface-panel--compact">
                    <div class="surface-panel__header">
                        <div>
                            <p class="section-kicker">Available tools</p>
                            <h3>{{ $tool['title'] }}</h3>
                        </div>
                        <a href="{{ route($tool['route']) }}" wire:navigate class="primary-action">
                            Buka tool
                        </a>
                    </div>

                    <p class="surface-panel__text">
                        {{ $tool['description'] }}
                    </p>
                </div>
            @endforeach
        </div>
    </section>
</x-app-layout>
