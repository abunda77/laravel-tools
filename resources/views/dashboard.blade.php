<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    @php
        $overview = [
            ['label' => 'Downloader', 'value' => 'Pending registry', 'tone' => 'amber'],
            ['label' => 'Custom scripts', 'value' => 'Ready to wire', 'tone' => 'emerald'],
            ['label' => 'Execution history', 'value' => 'Schema next', 'tone' => 'sky'],
        ];

        $highlights = [
            [
                'title' => 'Scaffold inti sudah siap',
                'text' => 'Laravel 13, Breeze Livewire, auth, dan shell dashboard sudah terpasang sehingga modul API bisa ditambahkan tanpa setup ulang fondasi.',
            ],
            [
                'title' => 'Sidebar disiapkan untuk growth',
                'text' => 'Navigasi memisahkan workspace dan operations agar kategori API dan script internal bisa berkembang tanpa membuat panel terasa penuh.',
            ],
            [
                'title' => 'Base UI sudah aman untuk iterasi',
                'text' => 'Setiap area memakai route terproteksi, sehingga registry endpoint dan eksekutor custom script bisa ditambahkan bertahap.',
            ],
        ];
    @endphp

    <section class="page-stack">
        <div class="hero-panel">
            <div>
                <p class="section-kicker">Operations surface</p>
                <h2 class="hero-panel__title">Panel kerja untuk eksekusi API eksternal dan custom script.</h2>
                <p class="hero-panel__text">
                    Tahap ini fokus pada shell aplikasi. Registry endpoint menyusul, tetapi struktur halaman, auth, dan navigasi sudah siap dipakai untuk pengembangan modul berikutnya.
                </p>
            </div>

            <div class="hero-panel__grid">
                @foreach ($overview as $item)
                    <article class="stat-tile stat-tile--{{ $item['tone'] }}">
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="content-grid">
            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Launch map</p>
                        <h3>Urutan kerja yang paling masuk akal</h3>
                    </div>
                </div>

                <div class="timeline-list">
                    <article>
                        <span>01</span>
                        <div>
                            <h4>Buat registry endpoint</h4>
                            <p>Mulai dari kategori utama dan definisi parameter dinamis berbasis config atau database.</p>
                        </div>
                    </article>
                    <article>
                        <span>02</span>
                        <div>
                            <h4>Bangun executor request</h4>
                            <p>Tambah service class HTTP, settings `base_url`, `apikey`, timeout, dan retry.</p>
                        </div>
                    </article>
                    <article>
                        <span>03</span>
                        <div>
                            <h4>Wire custom script runner</h4>
                            <p>Gunakan handler berbasis Artisan atau PHP class lebih dulu sebelum membuka shell command terbatas.</p>
                        </div>
                    </article>
                </div>
            </section>

            <section class="surface-panel">
                <div class="surface-panel__header">
                    <div>
                        <p class="section-kicker">Implementation notes</p>
                        <h3>Pondasi yang sudah dipasang</h3>
                    </div>
                </div>

                <div class="feature-list">
                    @foreach ($highlights as $item)
                        <article>
                            <h4>{{ $item['title'] }}</h4>
                            <p>{{ $item['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </section>
</x-app-layout>
