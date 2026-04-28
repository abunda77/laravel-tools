<div class="mx-auto w-full max-w-6xl rounded-[2rem] border border-[rgba(148,163,184,0.2)] bg-[#17191d] p-4 text-slate-100 shadow-2xl shadow-black/30 md:p-8">
    <section class="space-y-6">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Tools module</p>
            <h2 class="text-2xl font-black md:text-3xl">Wall Meter</h2>
            <p class="max-w-3xl text-sm text-slate-300 md:text-base">
                Menghitung tinggi dinding dengan metode trigonometri sudut elevasi menggunakan rumus h2 = d x tan(alpha), lalu H = h1 + h2.
            </p>
        </header>

        <div class="rounded-2xl border border-[rgba(148,163,184,0.2)] bg-[#111317] p-4 md:p-6">
            <svg viewBox="0 0 720 290" class="h-auto w-full">
                <rect x="0" y="0" width="720" height="290" fill="transparent"></rect>
                <line x1="90" y1="178" x2="600" y2="178" stroke="#858994" stroke-width="1.4"></line>
                <line x1="90" y1="218" x2="600" y2="218" stroke="#4b5563" stroke-width="1.2" stroke-dasharray="7 6"></line>
                <line x1="104" y1="178" x2="560" y2="30" stroke="#1d9bf0" stroke-width="2" stroke-dasharray="8 5"></line>

                <line x1="104" y1="178" x2="104" y2="{{ 178 - ($instrumentHeight * 36) }}" stroke="#d1d5db" stroke-width="2"></line>
                <line x1="560" y1="178" x2="560" y2="30" stroke="#d1d5db" stroke-width="2"></line>
                <line x1="560" y1="178" x2="560" y2="{{ 178 - ($this->verticalComponent * 36) }}" stroke="#94a3b8" stroke-width="2"></line>

                <rect x="560" y="30" width="58" height="188" rx="3" fill="none" stroke="#d1d5db" stroke-width="1.4"></rect>
                <line x1="560" y1="84" x2="618" y2="84" stroke="#7f8ea3" stroke-width="1"></line>
                <line x1="560" y1="126" x2="618" y2="126" stroke="#7f8ea3" stroke-width="1"></line>
                <line x1="560" y1="168" x2="618" y2="168" stroke="#7f8ea3" stroke-width="1"></line>

                <circle cx="104" cy="178" r="5.5" fill="#111317" stroke="#e5e7eb" stroke-width="1.6"></circle>
                <path d="M86 198v-18l8-10 8 10v18" fill="none" stroke="#d1d5db" stroke-width="1.5"></path>

                <path d="M156 179A46 46 0 0 1 137 150" fill="none" stroke="#1d9bf0" stroke-width="2"></path>

                <text x="73" y="170" fill="#d1d5db" font-size="15" font-weight="700">h1</text>
                <text x="562" y="20" fill="#f8fafc" font-size="23" font-weight="700">Dinding</text>
                <text x="560" y="114" fill="#d1d5db" font-size="21" font-weight="700">h2</text>
                <text x="180" y="165" fill="#f8fafc" font-size="28" font-weight="700">α</text>
                <text x="305" y="223" fill="#f8fafc" font-size="24" font-weight="700">d = jarak</text>
                <text x="92" y="238" fill="#d1d5db" font-size="20" font-weight="600">Pengamat</text>
                <text x="268" y="240" fill="#d1d5db" font-size="20" font-weight="600">Permukaan lantai</text>
            </svg>
        </div>

        <section class="rounded-2xl border border-[rgba(148,163,184,0.25)] bg-[#14171d] p-4">
            <p class="text-sm font-semibold text-slate-100 md:text-base">h2 = d x tan(alpha) | Tinggi total: H = h1 + h2</p>
            <p class="mt-1 text-sm text-slate-300">d = jarak horizontal, alpha = sudut elevasi, h1 = tinggi mata/alat dari lantai.</p>
        </section>

        <section class="space-y-5 rounded-2xl border border-[rgba(148,163,184,0.2)] bg-[#111317] p-4 md:p-6">
            <div>
                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                    <label for="distance" class="font-semibold text-slate-200">Jarak (d)</label>
                    <span class="font-bold text-slate-50">{{ number_format($horizontalDistance, 1) }} m</span>
                </div>
                <input id="distance" type="range" min="0.1" max="30" step="0.1" wire:model.live="horizontalDistance" class="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-700 accent-sky-400">
                @error('horizontalDistance') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                    <label for="angle" class="font-semibold text-slate-200">Sudut elevasi (alpha)</label>
                    <span class="font-bold text-slate-50">{{ number_format($elevationAngleDeg, 0) }}°</span>
                </div>
                <input id="angle" type="range" min="1" max="89" step="1" wire:model.live="elevationAngleDeg" class="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-700 accent-sky-400">
                @error('elevationAngleDeg') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="mb-2 flex items-center justify-between gap-4 text-sm">
                    <label for="height" class="font-semibold text-slate-200">Tinggi alat (h1)</label>
                    <span class="font-bold text-slate-50">{{ number_format($instrumentHeight, 2) }} m</span>
                </div>
                <input id="height" type="range" min="0.1" max="2.5" step="0.01" wire:model.live="instrumentHeight" class="h-2 w-full cursor-pointer appearance-none rounded-full bg-slate-700 accent-sky-400">
                @error('instrumentHeight') <p class="mt-2 text-xs text-rose-400">{{ $message }}</p> @enderror
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl border border-[rgba(148,163,184,0.15)] bg-[#111317] p-4">
                <p class="text-sm text-slate-400">h2 (tan x jarak)</p>
                <p class="mt-2 text-3xl font-black text-slate-50">{{ number_format($this->verticalComponent, 2) }} m</p>
            </article>
            <article class="rounded-2xl border border-[rgba(125,211,252,0.3)] bg-[#111317] p-4">
                <p class="text-sm text-slate-300">H total dinding</p>
                <p class="mt-2 text-3xl font-black text-sky-300">{{ number_format($this->totalHeight, 2) }} m</p>
            </article>
            <article class="rounded-2xl border border-[rgba(148,163,184,0.15)] bg-[#111317] p-4">
                <p class="text-sm text-slate-400">Panjang garis bidik</p>
                <p class="mt-2 text-3xl font-black text-slate-50">{{ number_format($this->sightLineLength, 2) }} m</p>
            </article>
        </section>

        <section class="rounded-2xl border border-[rgba(148,163,184,0.2)] bg-[#111317] p-4 md:p-6">
            <h3 class="text-lg font-bold text-slate-100">Langkah perhitungan:</h3>
            <div class="mt-3 space-y-2 text-sm text-slate-300 md:text-base">
                <p>h2 = {{ number_format($horizontalDistance, 1) }} x tan({{ number_format($elevationAngleDeg, 0) }}°)</p>
                <p>h2 = {{ number_format($horizontalDistance, 1) }} x {{ number_format($this->tangentValue, 4) }}</p>
                <p>h2 = <strong class="text-slate-100">{{ number_format($this->verticalComponent, 3) }} m</strong></p>
                <p>H = h1 + h2 = {{ number_format($instrumentHeight, 2) }} + {{ number_format($this->verticalComponent, 3) }} = <strong class="text-sky-300">{{ number_format($this->totalHeight, 3) }} m</strong></p>
                <p>L = d / cos(alpha) = {{ number_format($horizontalDistance, 1) }} / cos({{ number_format($elevationAngleDeg, 0) }}°) = {{ number_format($this->sightLineLength, 3) }} m</p>
                <p class="text-xs text-slate-500">alpha(rad) = {{ number_format($this->angleRad, 6) }}</p>
            </div>
        </section>
    </section>
</div>
