<x-app-layout>
    <x-slot name="header">
        Execution History
    </x-slot>

    <section class="page-stack">
        <div class="surface-panel surface-panel--compact">
            <div class="surface-panel__header">
                <div>
                    <p class="section-kicker">Audit trail</p>
                    <h3>Riwayat eksekusi akan muncul di sini</h3>
                </div>
            </div>

            <p class="surface-panel__text">
                Setelah modul API dan script runner aktif, halaman ini akan menampilkan histori request, status, durasi, payload ringkas, dan error log untuk kebutuhan audit maupun debugging.
            </p>
        </div>
    </section>
</x-app-layout>
