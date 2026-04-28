<x-app-layout>
    <x-slot name="header">
        Tools
    </x-slot>

    <section class="page-stack">
        <div class="hero-panel">
            <div>
                <p class="section-kicker">Tools Module</p>
                <h2 class="hero-panel__title">Kumpulan utilitas dan alat bantu operasional.</h2>
                <p class="hero-panel__text">
                    Modul ini menyediakan workbench untuk kebutuhan operasional seperti split cash, cek resi, dan kirim WhatsApp.
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-5">
            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <h3>Split Cash</h3>
                    </div>
                    <a href="{{ route('tools.split-cash') }}" wire:navigate class="primary-action">
                        Buka
                    </a>
                </div>
                <p class="surface-panel__text">
                    Hitung pembagian uang tunai dengan cepat untuk kebutuhan transaksi operasional.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <h3>Calculator PVC</h3>
                    </div>
                    <a href="{{ route('tools.calculator-pvc') }}" wire:navigate class="primary-action">
                        Buka
                    </a>
                </div>
                <p class="surface-panel__text">
                    Estimasi kebutuhan lembar PVC panel atau board berdasarkan ukuran bidang dan harga per lembar.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <h3>Cek Resi</h3>
                    </div>
                    <a href="{{ route('tools.cek-resi') }}" wire:navigate class="primary-action">
                        Buka
                    </a>
                </div>
                <p class="surface-panel__text">
                    Lacak status paket berdasarkan nomor resi dan slug ekspedisi provider.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <h3>Wall Meter</h3>
                    </div>
                    <a href="{{ route('tools.wall-meter') }}" wire:navigate class="primary-action">
                        Buka
                    </a>
                </div>
                <p class="surface-panel__text">
                    Hitung tinggi dinding berbasis jarak horizontal, sudut elevasi, dan tinggi alat menggunakan metode trigonometri.
                </p>
            </div>

            <div class="surface-panel surface-panel--compact">
                <div class="surface-panel__header">
                    <div>
                        <h3>Kirim WA / Send Whatsapp</h3>
                    </div>
                    <a href="{{ route('tools.send-whatsapp') }}" wire:navigate class="primary-action">
                        Buka
                    </a>
                </div>
                <p class="surface-panel__text">
                    Kirim pesan WhatsApp lewat endpoint provider dengan basic auth yang dibaca dari environment.
                </p>
            </div>
        </div>
    </section>
</x-app-layout>
