<x-app-layout>
    <x-slot name="header">
        Character Sheet
    </x-slot>

    @php
        $links = [
            [
                'title' => 'Character Sheet Generator',
                'provider' => 'ChatGPT',
                'url' => 'https://chatgpt.com/g/g-NF7Nl0SuH-character-sheet-generator',
                'description' => 'Generator untuk menyusun character sheet dasar dari ide karakter, peran, dan atribut utama.',
            ],
            [
                'title' => 'Image to JSON Prompt Engineer',
                'provider' => 'ChatGPT',
                'url' => 'https://chatgpt.com/g/g-6a14204b90f481918380f88a135896ee-image-to-json-prompt-engineer',
                'description' => 'Konversi referensi visual menjadi struktur prompt JSON yang lebih rapi dan siap dipakai ulang.',
            ],
            [
                'title' => 'Story Board Generator',
                'provider' => 'ChatGPT',
                'url' => 'https://chatgpt.com/g/g-6a25f2ed0600819188dc4e347003fa53-story-board-generator',
                'description' => 'Membantu memecah alur visual menjadi urutan storyboard per scene atau shot.',
            ],
            [
                'title' => 'Image to JSON Prompt',
                'provider' => 'Gemini',
                'url' => 'https://gemini.google.com/gem/1lO1TZAM6m4lAyHE0-ey02571I8lio0_j?usp=sharing',
                'description' => 'Alternatif Gemini untuk menyusun prompt JSON dari gambar referensi.',
            ],
            [
                'title' => 'Character Sheet Director',
                'provider' => 'Gemini',
                'url' => 'https://gemini.google.com/gem/1by6b_R1ZReYog-wCHqIpdDzc8ayLFnh0?usp=sharing',
                'description' => 'Asisten untuk mengarahkan detail karakter, pose, ekspresi, dan konsistensi visual.',
            ],
            [
                'title' => 'Video & Image Prompt Generator',
                'provider' => 'Gemini',
                'url' => 'https://gemini.google.com/share/a257460e7f1b',
                'description' => 'Generator prompt untuk kebutuhan gambar dan video dalam satu alur kerja.',
            ],
        ];
    @endphp

    <section class="page-stack">
        <div class="hero-panel">
            <div>
                <p class="section-kicker">Tools Module</p>
                <h2 class="hero-panel__title">Character Sheet resource hub untuk alur visual dan prompt engineering.</h2>
                <p class="hero-panel__text">
                    Halaman ini mengumpulkan shortcut ke generator character sheet, storyboard, dan prompt tools eksternal dalam satu tempat.
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2 2xl:grid-cols-3">
            @foreach ($links as $link)
                <article class="surface-panel surface-panel--compact">
                    <div class="surface-panel__header">
                        <div>
                            <p class="section-kicker">{{ $link['provider'] }}</p>
                            <h3>{{ $link['title'] }}</h3>
                        </div>

                        <a
                            href="{{ $link['url'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="primary-action"
                        >
                            Buka
                        </a>
                    </div>

                    <p class="surface-panel__text">
                        {{ $link['description'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </section>
</x-app-layout>
