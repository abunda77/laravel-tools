<x-app-layout>
    <x-slot name="header">
        Custom Scripts
    </x-slot>

    <section class="page-stack">
        <div class="surface-panel surface-panel--compact">
            <div class="surface-panel__header">
                <div>
                    <p class="section-kicker">Script runner</p>
                    <h3>Area eksekusi script internal</h3>
                </div>
            </div>

            <p class="surface-panel__text">
                Shell halaman sudah siap untuk daftar script, parameter input, dan hasil eksekusi. Implementasi berikutnya sebaiknya memakai whitelist handler berbasis Artisan atau PHP class.
            </p>
        </div>
    </section>
</x-app-layout>
