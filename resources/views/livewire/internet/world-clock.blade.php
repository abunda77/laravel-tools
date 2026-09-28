<div class="external-stack">
    <section class="external-hero">
        <article class="external-hero__intro">
            <p class="section-kicker">Internet module</p>
            <h3>World Clock & Time Converter</h3>
            <p>
                Tampilkan waktu real-time dari berbagai timezone di seluruh dunia dan konversi waktu Jakarta ke timezone lain.
                Menggunakan IANA timezone database dan browser Intl API tanpa dependency eksternal.
            </p>
        </article>

        <div class="external-hero__meta">
            <div class="mini-stat">
                <span>Total Cities</span>
                <strong>25</strong>
            </div>
            <div class="mini-stat">
                <span>Regions</span>
                <strong>5</strong>
            </div>
            <div class="mini-stat">
                <span>Update Interval</span>
                <strong>1 second</strong>
            </div>
        </div>
    </section>

    {{-- Current Time Section --}}
    <section class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <h3>Current Time</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    Waktu server dan waktu Jakarta saat ini.
                </p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 rounded-2xl p-6 border border-blue-100 dark:border-blue-900">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400">Server Time</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $serverTimezone }}</p>
                    </div>
                    <span class="text-2xl">🖥️</span>
                </div>
                <div class="space-y-1">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white font-mono" id="server-clock">--:--:--</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400" id="server-date">Loading...</p>
                </div>
            </div>

            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 dark:from-emerald-950/30 dark:to-teal-950/30 rounded-2xl p-6 border border-emerald-100 dark:border-emerald-900">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400">Jakarta Time</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Asia/Jakarta (GMT+7)</p>
                    </div>
                    <span class="text-2xl">🇮🇩</span>
                </div>
                <div class="space-y-1">
                    <p class="text-3xl font-bold text-gray-900 dark:text-white font-mono" id="jakarta-clock">--:--:--</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400" id="jakarta-date">Loading...</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Time Converter Section --}}
    <section class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <h3>Time Converter</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    Konversi waktu dari Jakarta ke timezone lain.
                </p>
            </div>
        </div>

        <form id="converter-form" class="settings-form">
            <div class="form-grid">
                <div class="form-field">
                    <label for="jakarta-time" class="form-label">Jakarta Time (24-hour)</label>
                    <input
                        id="jakarta-time"
                        type="time"
                        class="form-input font-mono"
                        placeholder="14:30"
                    />
                    <p class="form-help">Masukkan waktu dalam format 24 jam (HH:MM)</p>
                </div>

                <div class="form-field">
                    <label for="target-timezone" class="form-label">Target Timezone</label>
                    <select id="target-timezone" class="form-input">
                        <option value="">-- Pilih timezone --</option>
                        <option value="{{ $serverTimezone }}">Server: {{ $serverTimezone }}</option>
                        @foreach ($timezones as $tz)
                            <option value="{{ $tz[0] }}">{{ $tz[2] }} {{ $tz[1] }} ({{ $tz[3] }})</option>
                        @endforeach
                    </select>
                    <p class="form-help">Pilih timezone tujuan untuk konversi</p>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="primary-action">
                    Konversi waktu
                </button>
            </div>
        </form>

        <div id="conversion-result" class="hidden mt-6">
            <div class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 rounded-2xl p-6">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-300 mb-2">Conversion Result</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white font-mono" id="converted-time">--:--:--</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1" id="converted-timezone">--</p>
            </div>
        </div>
    </section>

    {{-- World Clock Section --}}
    <section class="surface-panel">
        <div class="surface-panel__header">
            <div>
                <h3>World Clock</h3>
                <p class="surface-panel__text surface-panel__text--tight">
                    Waktu real-time dari 25 lokasi di seluruh dunia, dikelompokkan berdasarkan region.
                </p>
            </div>
        </div>

        <div class="space-y-8">
            @php
                $regions = [
                    'Asia' => array_filter($timezones, fn($tz) => str_starts_with($tz[0], 'Asia/')),
                    'Europe' => array_filter($timezones, fn($tz) => str_starts_with($tz[0], 'Europe/')),
                    'Americas' => array_filter($timezones, fn($tz) => str_starts_with($tz[0], 'America/')),
                    'Australia & Pacific' => array_filter($timezones, fn($tz) => str_starts_with($tz[0], 'Australia/') || str_starts_with($tz[0], 'Pacific/')),
                    'Africa' => array_filter($timezones, fn($tz) => str_starts_with($tz[0], 'Africa/')),
                ];
            @endphp

            @foreach ($regions as $regionName => $regionTimezones)
                @if (!empty($regionTimezones))
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">{{ $regionName }}</h4>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($regionTimezones as $tz)
                                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $tz[1] }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $tz[3] }}</p>
                                        </div>
                                        <span class="text-xl ml-2">{{ $tz[2] }}</span>
                                    </div>
                                    <p class="text-xl font-bold text-gray-900 dark:text-white font-mono tz-clock" data-timezone="{{ $tz[0] }}">--:--:--</p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 tz-date" data-timezone="{{ $tz[0] }}">Loading...</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
</div>

<script>
    const serverInitialTimestamp = {{ $serverTimestamp }};
    const jakartaInitialTimestamp = {{ $jakartaTimestamp }};
    const pageLoadTime = Date.now();
    const serverTimezone = @js($serverTimezone);

    function currentPageTime(initialTimestamp) {
        const elapsed = Date.now() - pageLoadTime;
        return new Date((initialTimestamp * 1000) + elapsed);
    }

    function formatDateTime(date, timezone) {
        try {
            const formatter = new Intl.DateTimeFormat('en-GB', {
                timeZone: timezone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });

            const dateFormatter = new Intl.DateTimeFormat('en-GB', {
                timeZone: timezone,
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });

            return {
                time: formatter.format(date),
                date: dateFormatter.format(date)
            };
        } catch (e) {
            return {
                time: 'Unavailable',
                date: 'Invalid timezone'
            };
        }
    }

    function updateFixedClocks() {
        const serverTime = currentPageTime(serverInitialTimestamp);
        const jakartaTime = currentPageTime(jakartaInitialTimestamp);

        const serverFormatted = formatDateTime(serverTime, serverTimezone);
        const jakartaFormatted = formatDateTime(jakartaTime, 'Asia/Jakarta');

        document.getElementById('server-clock').textContent = serverFormatted.time;
        document.getElementById('server-date').textContent = serverFormatted.date;
        document.getElementById('jakarta-clock').textContent = jakartaFormatted.time;
        document.getElementById('jakarta-date').textContent = jakartaFormatted.date;
    }

    function updateWorldClock() {
        const now = new Date();
        const clocks = document.querySelectorAll('.tz-clock');
        const dates = document.querySelectorAll('.tz-date');

        clocks.forEach(clock => {
            const timezone = clock.dataset.timezone;
            const formatted = formatDateTime(now, timezone);
            clock.textContent = formatted.time;
        });

        dates.forEach(dateEl => {
            const timezone = dateEl.dataset.timezone;
            const formatted = formatDateTime(now, timezone);
            dateEl.textContent = formatted.date;
        });
    }

    function getTimezoneOffset(date, timezone) {
        try {
            const formatter = new Intl.DateTimeFormat('en-US', {
                timeZone: timezone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            });

            const parts = formatter.formatToParts(date);
            const obj = {};
            parts.forEach(p => { obj[p.type] = p.value; });

            const tzDate = new Date(
                `${obj.year}-${obj.month}-${obj.day}T${obj.hour}:${obj.minute}:${obj.second}`
            );

            return (date - tzDate);
        } catch (e) {
            return 0;
        }
    }

    function jakartaTimeToDate(time) {
        const [hours, minutes] = time.split(':').map(Number);
        const now = new Date();
        const jakartaOffset = getTimezoneOffset(now, 'Asia/Jakarta');
        const localOffset = now.getTimezoneOffset() * 60000;

        const utcDate = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate(),
            hours,
            minutes,
            0
        );

        return new Date(utcDate.getTime() - jakartaOffset + localOffset);
    }

    document.getElementById('converter-form').addEventListener('submit', (e) => {
        e.preventDefault();

        const jakartaTime = document.getElementById('jakarta-time').value;
        const targetTimezone = document.getElementById('target-timezone').value;

        if (!jakartaTime || !targetTimezone) {
            alert('Mohon isi waktu Jakarta dan pilih timezone tujuan.');
            return;
        }

        try {
            const jakartaDate = jakartaTimeToDate(jakartaTime);
            const formatted = formatDateTime(jakartaDate, targetTimezone);

            document.getElementById('converted-time').textContent = formatted.time;
            document.getElementById('converted-timezone').textContent = targetTimezone;
            document.getElementById('conversion-result').classList.remove('hidden');
        } catch (e) {
            alert('Conversion failed. Please try again.');
        }
    });

    updateFixedClocks();
    updateWorldClock();

    setInterval(() => {
        updateFixedClocks();
        updateWorldClock();
    }, 1000);
</script>
