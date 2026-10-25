# Info System — Halaman Monitoring Server untuk Filament 3

Halaman Filament read-only di grup **Settings** yang menampilkan kondisi server: sistem operasi, konfigurasi PHP, modul PHP yang aktif/tersedia, RAM, disk, paket Composer, database, dan antrian. Semua data disajikan sebagai kartu ringkasan, tabel interaktif, dan grafik.

Dokumen ini berdiri sendiri: semua kode yang dibutuhkan ada di sini dan bisa disalin ke project Laravel lain.

## Apa yang ditampilkan

| Tab | Isi |
| --- | --- |
| **Ringkasan** | 9 kartu: hostname, OS + arsitektur, PHP + SAPI, Laravel/Filament/Livewire, database + jumlah tabel, jumlah paket Composer, RAM, disk, uptime + CPU. Di atasnya 3 grafik: doughnut RAM, doughnut disk, bar modul PHP per kategori. |
| **Sistem Operasi** | Tabel 14 baris: versi kernel, arsitektur, hostname, IP server, web server, timezone, uptime, CPU, core/thread, load average, RAM, disk. |
| **PHP** | Bar pemakaian memori vs `memory_limit` + tabel 16 baris: versi, Zend, SAPI, php.ini, extension_dir, OPcache (hit rate), ICU, dll. |
| **Modul PHP** | Tabel modul aktif (badge Core/Zend/Ekstensi + chip hitung per kategori) dan tabel ekstensi yang ada di `extension_dir` tapi belum dimuat. |
| **Composer** | Chip ringkasan (total/produksi/development/requirement PHP) + tabel nama, versi, lisensi, tipe. |
| **Runtime & Infra** | 3 grup: runtime aplikasi (env, debug, cache/session/queue driver), database (versi, jumlah tabel, ukuran, migration pending, failed/pending jobs), penyimpanan & log (ukuran log, status writable folder). |

Interaksi:

- Tiap tab dimuat terpisah (hanya tab aktif yang dirender), jadi tidak ada query/probe yang tidak perlu.
- Tabel punya pencarian instan, sort dengan klik header, dan pagination — semuanya client-side lewat Alpine, tanpa request ke server.
- Tombol **Refresh** membersihkan cache probe perangkat keras; ada toggle **Auto refresh 30 detik** (`wire:poll.30s`).
- Mendukung dark mode mengikuti tema panel.

## Prasyarat

| Kebutuhan | Keterangan |
| --- | --- |
| PHP | 8.2+ |
| Laravel | 10 / 11 |
| Filament | `^3.2` (panel apa pun) |
| Livewire | `^3` (ikut Filament) |
| Shield | opsional — `bezhansalleh/filament-shield ^3`. Tanpa Shield, hapus trait `HasPageShield`. |
| Windows | PowerShell + `shell_exec` aktif untuk RAM/CPU/uptime |
| Linux | Membaca `/proc/meminfo`, `/proc/cpuinfo`, `/proc/uptime` |
| Frontend | **tidak perlu** build Vite/Tailwind; CSS tambahan ditulis inline di blade |

## Struktur file

```text
app/Support/SystemInfo.php                                   # seluruh probe sistem (tanpa UI)
app/Filament/Pages/InfoSystem.php                            # halaman + tab + tombol refresh
app/Filament/Widgets/SystemMemoryChart.php                   # doughnut RAM
app/Filament/Widgets/SystemDiskChart.php                     # doughnut disk
app/Filament/Widgets/SystemModuleChart.php                   # bar modul PHP per kategori
resources/views/filament/pages/info-system.blade.php         # markup halaman
resources/views/filament/pages/partials/info-rows.blade.php  # tabel key-value
resources/views/filament/pages/partials/data-table.blade.php # tabel Alpine: search/sort/paginate
tests/Feature/InfoSystemPageTest.php                         # feature test
```

Aturan pembagian: semua pembacaan OS/PHP/DB/dll ada di `App\Support\SystemInfo`, halaman hanya mengatur tampilan. Dengan begitu probe bisa dipakai widget, command artisan, atau endpoint API lain tanpa menduplikasi logika.

## 1. Probe sistem — `app/Support/SystemInfo.php`

Yang perlu diperhatikan saat menyalin:

- `memo` menyimpan hasil per instance supaya satu request tidak memanggil PowerShell atau membaca `composer.lock` berkali-kali.
- Probe perangkat keras di-cache satu jam dengan key `system-info:hardware`. Cache dibungkus `try/catch` supaya project tanpa tabel cache tetap jalan.
- Semua akses DB dibungkus `try/catch` dan mengembalikan `null` bila gagal, jadi halaman tetap tampil meski database sedang bermasalah.
- `CORE_EXTENSIONS` adalah daftar ekstensi internal PHP. Sesuaikan bila ingin kategori berbeda.
- Format angka (byte, durasi, persen) disediakan sebagai method publik agar blade tidak menghitung sendiri.

```php
<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemInfo
{
    /**
     * Ekstensi yang selalu ikut terkompilasi bersama PHP.
     *
     * @var list<string>
     */
    private const CORE_EXTENSIONS = [
        'Core', 'SPL', 'Reflection', 'random', 'standard', 'date', 'pcre',
        'hash', 'json', 'ctype', 'tokenizer', 'filter', 'session',
    ];

    /**
     * @var list<string>
     */
    private const BYTE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

    /**
     * @var array<string, mixed>
     */
    private array $memo = [];

    /**
     * Kartu ringkasan untuk tab utama.
     *
     * @return list<array{label: string, value: string, sub: string|null}>
     */
    public function overviewCards(): array
    {
        $hardware = $this->hardware();
        $database = $this->databaseInfo();
        $composer = $this->composerPackages();
        $disk = $this->diskSnapshot();
        $memory = $this->memorySnapshot();

        return [
            [
                'label' => 'Hostname',
                'value' => (string) php_uname('n'),
                'sub' => $this->serverIp(),
            ],
            [
                'label' => 'Sistem Operasi',
                'value' => PHP_OS_FAMILY.' '.php_uname('r'),
                'sub' => (string) php_uname('m'),
            ],
            [
                'label' => 'PHP',
                'value' => PHP_VERSION,
                'sub' => php_sapi_name().' • Zend '.zend_version(),
            ],
            [
                'label' => 'Laravel',
                'value' => app()->version(),
                'sub' => 'Filament '.$this->packageVersion('filament/filament').' • Livewire '.$this->packageVersion('livewire/livewire'),
            ],
            [
                'label' => 'Database',
                'value' => strtoupper((string) config('database.default')),
                'sub' => ($database['version'] ?? '—').' • '.($database['table_count'] === null ? '—' : $database['table_count'].' tabel'),
            ],
            [
                'label' => 'Paket Composer',
                'value' => $composer['counts']['total'].' paket',
                'sub' => $composer['counts']['production'].' produksi • '.$composer['counts']['development'].' development',
            ],
            [
                'label' => 'RAM Fisik',
                'value' => $this->formatBytes($memory['total']),
                'sub' => 'Terpakai '.$this->formatBytes($memory['used']).' ('.$this->formatPercent($memory['percent']).')',
            ],
            [
                'label' => 'Disk',
                'value' => $this->formatBytes($disk['total']),
                'sub' => 'Bebas '.$this->formatBytes($disk['free']).' ('.$this->formatPercent($disk['percent_free']).')',
            ],
            [
                'label' => 'Uptime',
                'value' => $this->formatDuration($hardware['uptime_seconds']),
                'sub' => filled($hardware['cpu_name']) ? $hardware['cpu_name'] : 'CPU tidak terdeteksi',
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function osInfo(): array
    {
        $hardware = $this->hardware();
        $memory = $this->memorySnapshot();
        $disk = $this->diskSnapshot();
        $load = $hardware['load_average'];

        return [
            ['label' => 'Sistem Operasi', 'value' => (string) PHP_OS],
            ['label' => 'Keluarga OS', 'value' => PHP_OS_FAMILY],
            ['label' => 'Versi / Build Kernel', 'value' => php_uname('r').' ('.php_uname('v').')'],
            ['label' => 'Arsitektur', 'value' => (string) php_uname('m')],
            ['label' => 'Hostname', 'value' => (string) php_uname('n')],
            ['label' => 'IP Server', 'value' => $this->serverIp()],
            ['label' => 'Web Server', 'value' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI ('.php_sapi_name().')'],
            ['label' => 'Timezone Aplikasi', 'value' => (string) config('app.timezone')],
            ['label' => 'Uptime', 'value' => $this->formatDuration($hardware['uptime_seconds'])],
            ['label' => 'CPU', 'value' => filled($hardware['cpu_name']) ? $hardware['cpu_name'] : '—'],
            ['label' => 'Core / Thread CPU', 'value' => $this->formatCpuCores($hardware)],
            ['label' => 'Load Average (1 / 5 / 15 menit)', 'value' => $load === null ? 'Tidak tersedia di platform ini' : number_format($load[0], 2).' / '.number_format($load[1], 2).' / '.number_format($load[2], 2)],
            ['label' => 'RAM Fisik', 'value' => $this->formatBytes($memory['total']).' (terpakai '.$this->formatBytes($memory['used']).', bebas '.$this->formatBytes($memory['free']).')'],
            ['label' => 'Disk ('.base_path().')', 'value' => $this->formatBytes($disk['total']).' (terpakai '.$this->formatBytes($disk['used']).', bebas '.$this->formatBytes($disk['free']).')'],
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function phpInfo(): array
    {
        $memory = $this->phpMemoryUsage();

        return [
            ['label' => 'Versi PHP', 'value' => PHP_VERSION],
            ['label' => 'Zend Engine', 'value' => zend_version()],
            ['label' => 'SAPI', 'value' => php_sapi_name()],
            ['label' => 'Berkas php.ini', 'value' => php_ini_loaded_file() ?: 'Tidak ada php.ini yang dimuat'],
            ['label' => 'Berkas ini tambahan', 'value' => php_ini_scanned_files() ?: '—'],
            ['label' => 'Direktori ekstensi', 'value' => (string) ini_get('extension_dir')],
            ['label' => 'memory_limit', 'value' => (string) ini_get('memory_limit')],
            ['label' => 'Pemakaian memori saat ini', 'value' => $memory['usage_label'].' ('.$memory['percent_label'].' dari limit)'],
            ['label' => 'Puncak pemakaian memori', 'value' => $memory['peak_label']],
            ['label' => 'max_execution_time', 'value' => (string) ini_get('max_execution_time').' detik'],
            ['label' => 'upload_max_filesize', 'value' => (string) ini_get('upload_max_filesize')],
            ['label' => 'post_max_size', 'value' => (string) ini_get('post_max_size')],
            ['label' => 'max_input_vars', 'value' => (string) ini_get('max_input_vars')],
            ['label' => 'OPcache', 'value' => $this->opcacheSummary()],
            ['label' => 'ICU / intl', 'value' => defined('INTL_ICU_VERSION') ? INTL_ICU_VERSION : 'intl tidak aktif'],
            ['label' => 'Timezone PHP', 'value' => date_default_timezone_get()],
        ];
    }

    /**
     * @return array{usage_label: string, peak_label: string, limit_label: string, percent: float, percent_label: string}
     */
    public function phpMemoryUsage(): array
    {
        $usage = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->iniBytes((string) ini_get('memory_limit'));
        $percent = $limit > 0 ? min(100, round(($usage / $limit) * 100, 1)) : 0.0;

        return [
            'usage_label' => $this->formatBytes($usage),
            'peak_label' => $this->formatBytes($peak),
            'limit_label' => $limit > 0 ? $this->formatBytes($limit) : (string) ini_get('memory_limit'),
            'percent' => $percent,
            'percent_label' => $this->formatPercent($percent),
        ];
    }

    /**
     * Modul PHP yang aktif pada proses ini.
     *
     * @return list<array{name: string, version: string, category: string}>
     */
    public function phpModules(): array
    {
        if (isset($this->memo['modules'])) {
            return $this->memo['modules'];
        }

        $zendExtensions = array_map('strtolower', get_loaded_extensions(true));
        $modules = [];

        foreach (get_loaded_extensions() as $name) {
            $version = phpversion($name);

            $modules[] = [
                'name' => $name,
                'version' => $version === false ? '—' : (string) $version,
                'category' => in_array(strtolower($name), $zendExtensions, true)
                    ? 'Zend'
                    : (in_array($name, self::CORE_EXTENSIONS, true) ? 'Core' : 'Ekstensi'),
            ];
        }

        usort($modules, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return $this->memo['modules'] = $modules;
    }

    /**
     * Jumlah modul aktif per kategori.
     *
     * @return array<string, int>
     */
    public function moduleCountsByCategory(): array
    {
        $counts = ['Core' => 0, 'Zend' => 0, 'Ekstensi' => 0];

        foreach ($this->phpModules() as $module) {
            $counts[$module['category']] = ($counts[$module['category']] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Ekstensi yang tersedia di direktori ekstensi tetapi belum dimuat.
     *
     * @return list<array{name: string, version: string, category: string}>
     */
    public function unloadedExtensions(): array
    {
        if (isset($this->memo['unloaded'])) {
            return $this->memo['unloaded'];
        }

        $directory = (string) ini_get('extension_dir');

        if ($directory === '' || ! is_dir($directory)) {
            return $this->memo['unloaded'] = [];
        }

        $loaded = array_map('strtolower', get_loaded_extensions());
        $extensionFiles = glob(rtrim($directory, '/\\').DIRECTORY_SEPARATOR.'*.'.(PHP_OS_FAMILY === 'Windows' ? 'dll' : 'so')) ?: [];
        $rows = [];

        foreach ($extensionFiles as $file) {
            $name = strtolower(basename($file));
            $name = preg_replace('/^php_/', '', $name);
            $name = (string) preg_replace('/\.(dll|so)$/', '', $name);

            if ($name === '' || in_array($name, $loaded, true)) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'version' => '—',
                'category' => 'Belum aktif',
            ];
        }

        usort($rows, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return $this->memo['unloaded'] = $rows;
    }

    /**
     * @return array{packages: list<array{name: string, version: string, license: string, type: string}>, counts: array{production: int, development: int, total: int}, php_constraint: string|null}
     */
    public function composerPackages(): array
    {
        if (isset($this->memo['composer'])) {
            return $this->memo['composer'];
        }

        $lockPath = base_path('composer.lock');

        if (! is_file($lockPath)) {
            return $this->memo['composer'] = [
                'packages' => [],
                'counts' => ['production' => 0, 'development' => 0, 'total' => 0],
                'php_constraint' => null,
            ];
        }

        $lock = json_decode((string) file_get_contents($lockPath), true) ?: [];
        $packages = [];

        foreach (['Production' => 'packages', 'Development' => 'packages-dev'] as $type => $key) {
            foreach ($lock[$key] ?? [] as $package) {
                $packages[] = [
                    'name' => (string) ($package['name'] ?? '—'),
                    'version' => ltrim((string) ($package['version'] ?? '—'), 'v'),
                    'license' => implode(', ', $package['license'] ?? []) ?: '—',
                    'type' => $type,
                ];
            }
        }

        usort($packages, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        $production = count($lock['packages'] ?? []);
        $development = count($lock['packages-dev'] ?? []);

        return $this->memo['composer'] = [
            'packages' => $packages,
            'counts' => [
                'production' => $production,
                'development' => $development,
                'total' => $production + $development,
            ],
            'php_constraint' => $lock['platform']['php'] ?? null,
        ];
    }

    /**
     * Kelompok informasi runtime, database, dan penyimpanan.
     *
     * @return list<array{heading: string, rows: list<array{label: string, value: string}>}>
     */
    public function infraGroups(): array
    {
        $database = $this->databaseInfo();
        $logs = $this->logDirectoryInfo();

        return [
            [
                'heading' => 'Runtime Aplikasi',
                'rows' => [
                    ['label' => 'APP_ENV', 'value' => (string) config('app.env')],
                    ['label' => 'APP_DEBUG', 'value' => config('app.debug') ? 'AKTIF (matikan di produksi)' : 'Nonaktif'],
                    ['label' => 'APP_URL', 'value' => (string) config('app.url')],
                    ['label' => 'Timezone', 'value' => (string) config('app.timezone')],
                    ['label' => 'Laravel', 'value' => app()->version()],
                    ['label' => 'Cache store', 'value' => (string) config('cache.default')],
                    ['label' => 'Session driver', 'value' => (string) config('session.driver')],
                    ['label' => 'Queue connection', 'value' => (string) config('queue.default')],
                    ['label' => 'Filesystem disk', 'value' => (string) config('filesystems.default')],
                ],
            ],
            [
                'heading' => 'Database',
                'rows' => [
                    ['label' => 'Koneksi default', 'value' => (string) config('database.default')],
                    ['label' => 'Driver', 'value' => $database['driver'] ?? '—'],
                    ['label' => 'Versi server', 'value' => $database['version'] ?? '—'],
                    ['label' => 'Jumlah tabel', 'value' => $database['table_count'] === null ? '—' : (string) $database['table_count']],
                    ['label' => 'Ukuran database', 'value' => $database['size'] === null ? '—' : $this->formatBytes($database['size'])],
                    ['label' => 'Migration belum dijalankan', 'value' => $database['pending_migrations'] === null ? '—' : (string) $database['pending_migrations']],
                    ['label' => 'Failed jobs', 'value' => $database['failed_jobs'] === null ? '—' : (string) $database['failed_jobs']],
                    ['label' => 'Job menunggu di antrian', 'value' => $database['pending_jobs'] === null ? '—' : (string) $database['pending_jobs']],
                ],
            ],
            [
                'heading' => 'Penyimpanan & Log',
                'rows' => [
                    ['label' => 'Ukuran folder log', 'value' => $this->formatBytes($logs['total']).' ('.$logs['count'].' berkas)'],
                    ['label' => 'Log terakhir', 'value' => $logs['latest'] === null ? '—' : $logs['latest'].' • '.$this->formatBytes($logs['latest_size'])],
                    ['label' => 'Log terakhir diubah', 'value' => $logs['latest_modified'] ?? '—'],
                    ['label' => 'storage/ bisa ditulis', 'value' => $this->writableLabel(storage_path())],
                    ['label' => 'storage/logs bisa ditulis', 'value' => $this->writableLabel(storage_path('logs'))],
                    ['label' => 'storage/framework bisa ditulis', 'value' => $this->writableLabel(storage_path('framework'))],
                    ['label' => 'bootstrap/cache bisa ditulis', 'value' => $this->writableLabel(base_path('bootstrap/cache'))],
                    ['label' => 'Batas disk aplikasi', 'value' => $this->formatBytes($this->diskSnapshot()['free']).' bebas'],
                ],
            ],
        ];
    }

    /**
     * @return array{total: float|null, free: float|null, used: float|null, percent: float|null, percent_free: float|null, path: string}
     */
    public function diskSnapshot(): array
    {
        $path = base_path();
        $total = @disk_total_space($path) ?: null;
        $free = @disk_free_space($path) ?: null;
        $used = ($total !== null && $free !== null) ? (float) ($total - $free) : null;

        return [
            'total' => $total,
            'free' => $free,
            'used' => $used,
            'percent' => ($total && $used !== null) ? round(($used / $total) * 100, 1) : null,
            'percent_free' => ($total && $free !== null) ? round(($free / $total) * 100, 1) : null,
            'path' => $path,
        ];
    }

    /**
     * @return array{total: float|null, free: float|null, used: float|null, percent: float|null}
     */
    public function memorySnapshot(): array
    {
        $hardware = $this->hardware();
        $total = $hardware['total_ram_bytes'];
        $free = $hardware['free_ram_bytes'];
        $used = ($total !== null && $free !== null) ? (float) ($total - $free) : null;

        return [
            'total' => $total,
            'free' => $free,
            'used' => $used,
            'percent' => ($total && $used !== null) ? round(($used / $total) * 100, 1) : null,
        ];
    }

    public function formatBytes(float|int|null $bytes, int $precision = 2): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '—';
        }

        $power = (int) min((int) floor(log((float) $bytes, 1024)), count(self::BYTE_UNITS) - 1);

        return number_format($bytes / (1024 ** $power), $precision).' '.self::BYTE_UNITS[$power];
    }

    public function formatPercent(float|int|null $percent): string
    {
        return $percent === null ? '—' : number_format((float) $percent, 1).'%';
    }

    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds < 0) {
            return '—';
        }

        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return $days.' hari '.$hours.' jam';
        }

        if ($hours > 0) {
            return $hours.' jam '.$minutes.' menit';
        }

        return $minutes.' menit';
    }

    /**
     * Hapus cache probe perangkat keras agar data diambil ulang.
     */
    public function flush(): void
    {
        $this->memo = [];

        try {
            Cache::forget('system-info:hardware');
        } catch (Throwable) {
            // Cache tidak tersedia, tidak ada yang perlu dibersihkan.
        }
    }

    private function packageVersion(string $package): string
    {
        foreach ($this->composerPackages()['packages'] as $row) {
            if ($row['name'] === $package) {
                return $row['version'];
            }
        }

        return '—';
    }

    private function serverIp(): string
    {
        $hostname = (string) php_uname('n');
        $ip = gethostbyname($hostname);

        return $ip === $hostname ? '—' : $ip;
    }

    /**
     * @param  array<string, mixed>  $hardware
     */
    private function formatCpuCores(array $hardware): string
    {
        $cores = $hardware['cpu_cores'];
        $threads = $hardware['cpu_threads'];

        if ($cores === null && $threads === null) {
            return '—';
        }

        return ($cores ?? 0).' core / '.($threads ?? 0).' thread';
    }

    private function opcacheSummary(): string
    {
        if (! extension_loaded('Zend OPcache')) {
            return 'Tidak aktif';
        }

        $status = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;

        if (! is_array($status)) {
            return 'Aktif (status tidak tersedia)';
        }

        $hits = (int) ($status['opcache_statistics']['hits'] ?? 0);
        $misses = (int) ($status['opcache_statistics']['misses'] ?? 0);
        $total = $hits + $misses;
        $hitRate = $total > 0 ? round(($hits / $total) * 100, 1) : 0.0;
        $usage = $status['memory_usage']['used_memory'] ?? null;

        return 'Aktif • hit rate '.$this->formatPercent($hitRate).' • memori '.$this->formatBytes(is_numeric($usage) ? (float) $usage : null);
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (int) $value;

        return match ($unit) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function writableLabel(string $path): string
    {
        if (! file_exists($path)) {
            return 'Tidak ditemukan';
        }

        return is_writable($path) ? 'Ya' : 'Tidak (periksa permission)';
    }

    /**
     * @return array{total: int|null, count: int, latest: string|null, latest_size: int|null, latest_modified: string|null}
     */
    private function logDirectoryInfo(): array
    {
        $files = glob(storage_path('logs').DIRECTORY_SEPARATOR.'*.log') ?: [];
        $total = 0;
        $latest = null;
        $latestTime = null;

        foreach ($files as $file) {
            $size = (int) @filesize($file);
            $total += $size;
            $modified = (int) @filemtime($file);

            if ($latestTime === null || $modified > $latestTime) {
                $latestTime = $modified;
                $latest = $file;
            }
        }

        return [
            'total' => $total,
            'count' => count($files),
            'latest' => $latest === null ? null : basename($latest),
            'latest_size' => $latest === null ? null : (int) @filesize($latest),
            'latest_modified' => $latestTime === null ? null : Carbon::createFromTimestamp($latestTime)->format('d M Y H:i:s'),
        ];
    }

    /**
     * @return array{driver: string|null, version: string|null, table_count: int|null, size: int|null, pending_migrations: int|null, failed_jobs: int|null, pending_jobs: int|null}
     */
    private function databaseInfo(): array
    {
        if (isset($this->memo['database'])) {
            return $this->memo['database'];
        }

        $info = [
            'driver' => null,
            'version' => null,
            'table_count' => null,
            'size' => null,
            'pending_migrations' => null,
            'failed_jobs' => null,
            'pending_jobs' => null,
        ];

        try {
            $info['driver'] = DB::connection()->getDriverName();
        } catch (Throwable) {
            return $this->memo['database'] = $info;
        }

        $info['version'] = $this->databaseVersion($info['driver']);
        $info['table_count'] = $this->databaseTableCount($info['driver']);
        $info['size'] = $this->databaseSize($info['driver']);
        $info['pending_migrations'] = $this->pendingMigrations();
        $info['failed_jobs'] = $this->tableCount('failed_jobs');
        $info['pending_jobs'] = config('queue.default') === 'database' ? $this->tableCount('jobs') : null;

        return $this->memo['database'] = $info;
    }

    private function databaseVersion(?string $driver): ?string
    {
        try {
            return match ($driver) {
                'sqlite' => DB::selectOne('select sqlite_version() as version')?->version,
                'mysql', 'mariadb' => DB::selectOne('select version() as version')?->version,
                'pgsql' => DB::selectOne('show server_version')?->server_version,
                default => null,
            };
        } catch (Throwable) {
            return null;
        }
    }

    private function databaseTableCount(?string $driver): ?int
    {
        try {
            $count = match ($driver) {
                'sqlite' => DB::selectOne("select count(*) as total from sqlite_master where type = 'table'"),
                'mysql', 'mariadb' => DB::selectOne('select count(*) as total from information_schema.tables where table_schema = database()'),
                'pgsql' => DB::selectOne("select count(*) as total from information_schema.tables where table_schema = 'public'"),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }

        return $count === null ? null : (int) $count->total;
    }

    private function databaseSize(?string $driver): ?int
    {
        try {
            $size = match ($driver) {
                'sqlite' => $this->sqliteSize(),
                'mysql', 'mariadb' => (float) (DB::selectOne('select coalesce(sum(data_length + index_length), 0) as size from information_schema.tables where table_schema = database()')?->size ?? 0),
                'pgsql' => (float) (DB::selectOne('select pg_database_size(current_database()) as size')?->size ?? 0),
                default => null,
            };
        } catch (Throwable) {
            return null;
        }

        return $size === null ? null : (int) $size;
    }

    private function sqliteSize(): ?int
    {
        $database = DB::connection()->getDatabaseName();

        if ($database === ':memory:' || ! is_file($database)) {
            return null;
        }

        return (int) @filesize($database);
    }

    private function pendingMigrations(): ?int
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return null;
            }

            $migrated = DB::table('migrations')->pluck('migration')->all();
            $files = array_map(
                fn (string $file): string => pathinfo($file, PATHINFO_FILENAME),
                glob(database_path('migrations/*.php')) ?: [],
            );

            return count(array_diff($files, $migrated));
        } catch (Throwable) {
            return null;
        }
    }

    private function tableCount(string $table): ?int
    {
        try {
            if (! Schema::hasTable($table)) {
                return null;
            }

            return (int) DB::table($table)->count();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{total_ram_bytes: float|null, free_ram_bytes: float|null, cpu_name: string|null, cpu_cores: int|null, cpu_threads: int|null, uptime_seconds: int|null, load_average: array{0: float, 1: float, 2: float}|null}
     */
    private function hardware(): array
    {
        if (isset($this->memo['hardware'])) {
            return $this->memo['hardware'];
        }

        try {
            $hardware = Cache::remember('system-info:hardware', now()->addHour(), fn (): array => $this->probeHardware());
        } catch (Throwable) {
            $hardware = $this->probeHardware();
        }

        return $this->memo['hardware'] = $hardware;
    }

    /**
     * @return array{total_ram_bytes: float|null, free_ram_bytes: float|null, cpu_name: string|null, cpu_cores: int|null, cpu_threads: int|null, uptime_seconds: int|null, load_average: array{0: float, 1: float, 2: float}|null}
     */
    private function probeHardware(): array
    {
        $hardware = PHP_OS_FAMILY === 'Windows' ? $this->windowsHardware() : $this->unixHardware();

        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
        $hardware['load_average'] = is_array($load) && count($load) === 3
            ? [(float) $load[0], (float) $load[1], (float) $load[2]]
            : null;

        return $hardware;
    }

    /**
     * @return array{total_ram_bytes: float|null, free_ram_bytes: float|null, cpu_name: string|null, cpu_cores: int|null, cpu_threads: int|null, uptime_seconds: int|null, load_average: null}
     */
    private function windowsHardware(): array
    {
        $script = '$os = Get-CimInstance Win32_OperatingSystem; '
            .'$cpu = Get-CimInstance Win32_Processor | Select-Object -First 1; '
            .'@{total_ram_kb = $os.TotalVisibleMemorySize; free_ram_kb = $os.FreePhysicalMemory; '
            .'cpu_name = $cpu.Name; cpu_cores = $cpu.NumberOfCores; cpu_threads = $cpu.NumberOfLogicalProcessors; '
            .'last_boot = $os.LastBootUpTime.ToString(\'o\')} | ConvertTo-Json -Compress';
        $output = $this->runCommand('powershell -NoProfile -NonInteractive -Command "'.$script.'"');
        $data = is_string($output) ? json_decode($output, true) : null;

        if (! is_array($data)) {
            return $this->emptyHardware();
        }

        $lastBoot = isset($data['last_boot']) ? Carbon::parse((string) $data['last_boot']) : null;

        return [
            'total_ram_bytes' => isset($data['total_ram_kb']) ? (float) $data['total_ram_kb'] * 1024 : null,
            'free_ram_bytes' => isset($data['free_ram_kb']) ? (float) $data['free_ram_kb'] * 1024 : null,
            'cpu_name' => isset($data['cpu_name']) ? trim((string) $data['cpu_name']) : null,
            'cpu_cores' => isset($data['cpu_cores']) ? (int) $data['cpu_cores'] : null,
            'cpu_threads' => isset($data['cpu_threads']) ? (int) $data['cpu_threads'] : null,
            'uptime_seconds' => $lastBoot === null ? null : max(0, (int) $lastBoot->diffInSeconds(now())),
            'load_average' => null,
        ];
    }

    /**
     * @return array{total_ram_bytes: float|null, free_ram_bytes: float|null, cpu_name: string|null, cpu_cores: int|null, cpu_threads: int|null, uptime_seconds: int|null, load_average: null}
     */
    private function unixHardware(): array
    {
        $meminfo = $this->readProcFile('/proc/meminfo');
        $cpuinfo = $this->readProcFile('/proc/cpuinfo');
        $uptime = $this->readProcFile('/proc/uptime');

        $totalRam = null;
        $freeRam = null;

        if ($meminfo !== null) {
            if (preg_match('/^MemTotal:\s+(\d+)\s+kB/m', $meminfo, $matches)) {
                $totalRam = (float) $matches[1] * 1024;
            }

            if (preg_match('/^MemAvailable:\s+(\d+)\s+kB/m', $meminfo, $matches)) {
                $freeRam = (float) $matches[1] * 1024;
            }
        }

        $cpuName = null;
        $cpuCores = null;

        if ($cpuinfo !== null) {
            if (preg_match('/^model name\s*:\s*(.+)$/m', $cpuinfo, $matches)) {
                $cpuName = trim($matches[1]);
            }

            $cpuCores = preg_match_all('/^processor\s*:/m', $cpuinfo) ?: null;
        }

        return [
            'total_ram_bytes' => $totalRam,
            'free_ram_bytes' => $freeRam,
            'cpu_name' => $cpuName,
            'cpu_cores' => $cpuCores,
            'cpu_threads' => $cpuCores,
            'uptime_seconds' => $uptime !== null ? (int) (float) $uptime : null,
            'load_average' => null,
        ];
    }

    /**
     * @return array{total_ram_bytes: null, free_ram_bytes: null, cpu_name: null, cpu_cores: null, cpu_threads: null, uptime_seconds: null, load_average: null}
     */
    private function emptyHardware(): array
    {
        return [
            'total_ram_bytes' => null,
            'free_ram_bytes' => null,
            'cpu_name' => null,
            'cpu_cores' => null,
            'cpu_threads' => null,
            'uptime_seconds' => null,
            'load_average' => null,
        ];
    }

    private function readProcFile(string $path): ?string
    {
        if (! is_readable($path)) {
            return null;
        }

        $contents = @file_get_contents($path);

        return is_string($contents) ? $contents : null;
    }

    private function runCommand(string $command): ?string
    {
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        if (! function_exists('shell_exec') || in_array('shell_exec', $disabled, true)) {
            return null;
        }

        try {
            $output = @shell_exec($command);
        } catch (Throwable) {
            return null;
        }

        return is_string($output) ? trim($output) : null;
    }
}
```

## 2. Halaman Filament — `app/Filament/Pages/InfoSystem.php`

- `getHeaderWidgets()` mengembalikan daftar widget hanya saat tab `overview` aktif; komponen `<x-filament::page>` milik Filament otomatis merender header widget.
- Widget disimpan sebagai properti privat `$systemInfo` agar instance probe yang sama dipakai seluruh render.
- `setTab()` memvalidasi nama tab, jadi nilai tak dikenal tidak merusak halaman.
- `HasPageShield` memberi permission `page_InfoSystem` (prefix `page` + nama kelas).

```php
<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\SystemDiskChart;
use App\Filament\Widgets\SystemMemoryChart;
use App\Filament\Widgets\SystemModuleChart;
use App\Support\SystemInfo;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class InfoSystem extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationLabel = 'Info System';

    protected static ?string $title = 'Info System';

    protected static ?string $slug = 'info-system';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.pages.info-system';

    public string $activeTab = 'overview';

    public bool $autoRefresh = false;

    private ?SystemInfo $systemInfo = null;

    public static function getNavigationGroup(): ?string
    {
        return 'Settings';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Kondisi server, konfigurasi PHP, modul yang aktif, dan paket Composer.';
    }

    /**
     * @return array<string, string>
     */
    public function tabs(): array
    {
        return [
            'overview' => 'Ringkasan',
            'os' => 'Sistem Operasi',
            'php' => 'PHP',
            'modules' => 'Modul PHP',
            'composer' => 'Composer',
            'infra' => 'Runtime & Infra',
        ];
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs())) {
            $this->activeTab = $tab;
        }
    }

    public function refresh(): void
    {
        $this->systemInfo()->flush();

        Notification::make()
            ->title('Informasi sistem diperbarui')
            ->success()
            ->send();
    }

    public function systemInfo(): SystemInfo
    {
        return $this->systemInfo ??= app(SystemInfo::class);
    }

    /**
     * @return array<class-string>
     */
    protected function getHeaderWidgets(): array
    {
        if ($this->activeTab !== 'overview') {
            return [];
        }

        return [
            SystemMemoryChart::class,
            SystemDiskChart::class,
            SystemModuleChart::class,
        ];
    }

    /**
     * @return int|string|array<string, int|null>
     */
    public function getHeaderWidgetsColumns(): int|string|array
    {
        return 3;
    }
}
```

## 3. Widget grafik

`protected static bool $isDiscovered = false;` wajib ada: tanpa itu Filament ikut mendaftarkan widget ke dashboard.

### 3a. `app/Filament/Widgets/SystemMemoryChart.php`

```php
<?php

namespace App\Filament\Widgets;

use App\Support\SystemInfo;
use Filament\Widgets\ChartWidget;

class SystemMemoryChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'RAM Fisik';

    protected static ?string $description = 'Perbandingan RAM terpakai dan tersedia';

    protected static ?string $maxHeight = '220px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $systemInfo = app(SystemInfo::class);
        $memory = $systemInfo->memorySnapshot();

        if ($memory['total'] === null) {
            return [
                'datasets' => [
                    [
                        'label' => 'RAM',
                        'data' => [0],
                        'backgroundColor' => ['#9ca3af'],
                    ],
                ],
                'labels' => ['Tidak terdeteksi'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'RAM (GB)',
                    'data' => [
                        round((float) $memory['used'] / 1024 ** 3, 2),
                        round((float) $memory['free'] / 1024 ** 3, 2),
                    ],
                    'backgroundColor' => ['#ef4444', '#22c55e'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Terpakai', 'Tersedia'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'cutout' => '62%',
            'maintainAspectRatio' => false,
        ];
    }
}
```

### 3b. `app/Filament/Widgets/SystemDiskChart.php`

```php
<?php

namespace App\Filament\Widgets;

use App\Support\SystemInfo;
use Filament\Widgets\ChartWidget;

class SystemDiskChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Kapasitas Disk';

    protected static ?string $description = 'Pemakaian partisi tempat aplikasi berada';

    protected static ?string $maxHeight = '220px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $disk = app(SystemInfo::class)->diskSnapshot();

        if ($disk['total'] === null) {
            return [
                'datasets' => [
                    [
                        'label' => 'Disk',
                        'data' => [0],
                        'backgroundColor' => ['#9ca3af'],
                    ],
                ],
                'labels' => ['Tidak terdeteksi'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Disk (GB)',
                    'data' => [
                        round((float) $disk['used'] / 1024 ** 3, 2),
                        round((float) $disk['free'] / 1024 ** 3, 2),
                    ],
                    'backgroundColor' => ['#f59e0b', '#22c55e'],
                    'borderWidth' => 0,
                ],
            ],
            'labels' => ['Terpakai', 'Bebas'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['position' => 'bottom'],
            ],
            'cutout' => '62%',
            'maintainAspectRatio' => false,
        ];
    }
}
```

### 3c. `app/Filament/Widgets/SystemModuleChart.php`

```php
<?php

namespace App\Filament\Widgets;

use App\Support\SystemInfo;
use Filament\Widgets\ChartWidget;

class SystemModuleChart extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static ?string $heading = 'Modul PHP per Kategori';

    protected static ?string $description = 'Jumlah ekstensi yang aktif pada proses ini';

    protected static ?string $maxHeight = '220px';

    protected int|string|array $columnSpan = 1;

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = app(SystemInfo::class)->moduleCountsByCategory();

        return [
            'datasets' => [
                [
                    'label' => 'Modul',
                    'data' => array_values($counts),
                    'backgroundColor' => ['#3b82f6', '#8b5cf6', '#10b981'],
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_keys($counts),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => ['display' => false],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['precision' => 0],
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}
```

## 4. Markup halaman — `resources/views/filament/pages/info-system.blade.php`

Blok `<style>` di dalam `@once` mendefinisikan kelas `.sysinfo-*` untuk badge, chip, dan progress bar. Ini disengaja: panel Filament dengan tema bawaan tidak menyertakan utility Tailwind seperti `xl:grid-cols-3`, `bg-emerald-100`, atau `h-2.5`, sehingga utility tersebut tidak akan ter-compile. Kalau project memakai `->viteTheme(...)` sendiri yang memindai folder `resources/views`, kelas-kelas itu boleh diganti utility Tailwind biasa.

```blade
@php
    $systemInfo = $this->systemInfo();
    $memory = $systemInfo->phpMemoryUsage();
    $composer = $systemInfo->composerPackages();
    $modules = $systemInfo->phpModules();
    $unloaded = $systemInfo->unloadedExtensions();
    $moduleCounts = $systemInfo->moduleCountsByCategory();

    $badgeClasses = [
        'Core' => 'sysinfo-badge sysinfo-badge--core',
        'Zend' => 'sysinfo-badge sysinfo-badge--zend',
        'Ekstensi' => 'sysinfo-badge sysinfo-badge--ext',
        'Belum aktif' => 'sysinfo-badge sysinfo-badge--idle',
        'Production' => 'sysinfo-badge sysinfo-badge--core',
        'Development' => 'sysinfo-badge sysinfo-badge--dev',
    ];
@endphp

<x-filament::page>
    @once
        <style>
            .sysinfo-chip,
            .sysinfo-badge {
                display: inline-flex;
                align-items: center;
                border-radius: 0.5rem;
                padding: 0.125rem 0.5rem;
                font-size: 0.75rem;
                font-weight: 600;
                background-color: #f3f4f6;
                color: #374151;
            }

            .sysinfo-badge {
                border-radius: 0.375rem;
                font-size: 0.6875rem;
            }

            .sysinfo-badge--core {
                background-color: #dbeafe;
                color: #1e40af;
            }

            .sysinfo-badge--zend {
                background-color: #ede9fe;
                color: #5b21b6;
            }

            .sysinfo-badge--ext {
                background-color: #dcfce7;
                color: #166534;
            }

            .sysinfo-badge--idle {
                background-color: #fef3c7;
                color: #92400e;
            }

            .sysinfo-badge--dev {
                background-color: #fee2e2;
                color: #991b1b;
            }

            .dark .sysinfo-chip,
            .dark .sysinfo-badge {
                background-color: rgba(255, 255, 255, 0.1);
                color: #e5e7eb;
            }

            .dark .sysinfo-badge--core {
                background-color: rgba(59, 130, 246, 0.2);
                color: #93c5fd;
            }

            .dark .sysinfo-badge--zend {
                background-color: rgba(139, 92, 246, 0.2);
                color: #c4b5fd;
            }

            .dark .sysinfo-badge--ext {
                background-color: rgba(34, 197, 94, 0.2);
                color: #86efac;
            }

            .dark .sysinfo-badge--idle {
                background-color: rgba(245, 158, 11, 0.2);
                color: #fcd34d;
            }

            .dark .sysinfo-badge--dev {
                background-color: rgba(239, 68, 68, 0.2);
                color: #fca5a5;
            }

            .sysinfo-bar {
                height: 8px;
                border-radius: 9999px;
                background-color: #e5e7eb;
                overflow: hidden;
            }

            .dark .sysinfo-bar {
                background-color: #374151;
            }

            .sysinfo-bar-fill {
                height: 8px;
                border-radius: 9999px;
                background-color: #2563eb;
            }

            [x-cloak] {
                display: none !important;
            }
        </style>
    @endonce

    <div class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <x-filament::tabs contained>
                @foreach ($this->tabs() as $key => $label)
                    <x-filament::tabs.item :active="$activeTab === $key" wire:click="setTab('{{ $key }}')">
                        {{ $label }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <input type="checkbox" wire:model.live="autoRefresh" class="rounded border-gray-300 dark:border-white/10">
                    Auto refresh 30 detik
                </label>

                <x-filament::button wire:click="refresh" size="sm" color="gray" icon="heroicon-o-arrow-path">
                    Refresh
                </x-filament::button>
            </div>
        </div>

        <div @if ($autoRefresh) wire:poll.30s @endif class="space-y-6">
            @if ($activeTab === 'overview')
                <div class="grid gap-4 md:grid-cols-3">
                    @foreach ($systemInfo->overviewCards() as $card)
                        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                            <p class="mt-1 text-lg font-semibold text-gray-950 break-words dark:text-white">{{ $card['value'] }}</p>
                            @if (filled($card['sub']))
                                <p class="mt-1 text-xs text-gray-500 break-words dark:text-gray-400">{{ $card['sub'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <x-filament::section heading="Catatan">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Halaman ini hanya menampilkan nilai konfigurasi terpilih, bukan keluaran mentah
                        <code class="font-mono">phpinfo()</code>, sehingga path sensitif dan variabel environment
                        tidak ikut terbuka. Data perangkat keras di-cache selama satu jam dan bisa diambil ulang
                        dengan tombol Refresh.
                    </p>
                </x-filament::section>
            @endif

            @if ($activeTab === 'os')
                @include('filament.pages.partials.info-rows', [
                    'heading' => 'Detail Sistem Operasi',
                    'rows' => $systemInfo->osInfo(),
                ])
            @endif

            @if ($activeTab === 'php')
                <x-filament::section heading="Pemakaian Memori PHP">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                            <span>{{ $memory['usage_label'] }} dari {{ $memory['limit_label'] }}</span>
                            <span>{{ $memory['percent_label'] }}</span>
                        </div>

                        <div class="sysinfo-bar">
                            <div class="sysinfo-bar-fill" style="width: {{ $memory['percent'] }}%"></div>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Puncak pemakaian: {{ $memory['peak_label'] }}
                        </p>
                    </div>
                </x-filament::section>

                @include('filament.pages.partials.info-rows', [
                    'heading' => 'Konfigurasi PHP',
                    'rows' => $systemInfo->phpInfo(),
                ])
            @endif

            @if ($activeTab === 'modules')
                <x-filament::section heading="Modul PHP Aktif ({{ count($modules) }})">
                    <div class="mb-3 flex flex-wrap gap-2">
                        @foreach ($moduleCounts as $category => $count)
                            <span class="sysinfo-chip">{{ $category }}: {{ $count }}</span>
                        @endforeach
                    </div>

                    @include('filament.pages.partials.data-table', [
                        'rows' => $modules,
                        'columns' => ['name' => 'Modul', 'version' => 'Versi', 'category' => 'Kategori'],
                        'badgeColumns' => ['category'],
                        'badgeClasses' => $badgeClasses,
                        'perPage' => 15,
                        'placeholder' => 'Cari modul…',
                        'emptyText' => 'Tidak ada modul yang cocok.',
                    ])
                </x-filament::section>

                <x-filament::section heading="Tersedia di Direktori Ekstensi ({{ count($unloaded) }})">
                    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                        Ekstensi berikut ada di <code class="font-mono">{{ ini_get('extension_dir') }}</code>
                        tetapi belum dimuat oleh <code class="font-mono">php.ini</code>.
                    </p>

                    @include('filament.pages.partials.data-table', [
                        'rows' => $unloaded,
                        'columns' => ['name' => 'Modul', 'category' => 'Status'],
                        'badgeColumns' => ['category'],
                        'badgeClasses' => $badgeClasses,
                        'perPage' => 15,
                        'placeholder' => 'Cari modul…',
                        'emptyText' => 'Semua ekstensi yang tersedia sudah aktif, atau direktori ekstensi tidak dapat dibaca.',
                    ])
                </x-filament::section>
            @endif

            @if ($activeTab === 'composer')
                <x-filament::section heading="Ringkasan Composer">
                    <div class="flex flex-wrap gap-2">
                        <span class="sysinfo-chip">Total: {{ $composer['counts']['total'] }} paket</span>
                        <span class="sysinfo-chip">Produksi: {{ $composer['counts']['production'] }}</span>
                        <span class="sysinfo-chip">Development: {{ $composer['counts']['development'] }}</span>
                        @if (filled($composer['php_constraint']))
                            <span class="sysinfo-chip">Requirement PHP: {{ $composer['php_constraint'] }}</span>
                        @endif
                    </div>
                </x-filament::section>

                <x-filament::section heading="Paket Terpasang">
                    @include('filament.pages.partials.data-table', [
                        'rows' => $composer['packages'],
                        'columns' => ['name' => 'Paket', 'version' => 'Versi', 'license' => 'Lisensi', 'type' => 'Tipe'],
                        'badgeColumns' => ['type'],
                        'badgeClasses' => $badgeClasses,
                        'perPage' => 20,
                        'placeholder' => 'Cari paket, lisensi, atau tipe…',
                        'emptyText' => 'composer.lock tidak ditemukan atau tidak berisi paket.',
                    ])
                </x-filament::section>
            @endif

            @if ($activeTab === 'infra')
                @foreach ($systemInfo->infraGroups() as $group)
                    @include('filament.pages.partials.info-rows', [
                        'heading' => $group['heading'],
                        'rows' => $group['rows'],
                    ])
                @endforeach
            @endif
        </div>
    </div>
</x-filament::page>
```

## 5. Partial

### 5a. `resources/views/filament/pages/partials/info-rows.blade.php`

Tabel key-value sederhana untuk tab OS/PHP/Infra.

```blade
@props([
    'heading' => null,
    'rows' => [],
])

<x-filament::section :heading="$heading">
    <dl class="divide-y divide-gray-100 dark:divide-white/10">
        @foreach ($rows as $row)
            <div class="grid gap-1 py-2 md:grid-cols-3">
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    {{ $row['label'] }}
                </dt>
                <dd class="font-mono text-sm text-gray-950 dark:text-white break-words md:col-span-2">
                    {{ $row['value'] }}
                </dd>
            </div>
        @endforeach
    </dl>
</x-filament::section>
```

### 5b. `resources/views/filament/pages/partials/data-table.blade.php`

Tabel interaktif untuk data berbentuk array (modul PHP, paket Composer). Data dikirim ke Alpine memakai `\Illuminate\Support\Js::from()`.

> **Penting:** jangan ganti `Js::from()` dengan `json_encode()` polos di dalam atribut `x-data`. `Js::from()` meng-escape tanda kutip menjadi `\u0022` (flag `JSON_HEX_QUOT`), sehingga atribut HTML tidak terputus. JSON mentah dengan tanda kutip ganda akan merusak markup.

Cara kerja: `filtered` memfilter semua nilai kolom, `sortBy()` membalik arah bila kolom sama, `paged` memotong hasil sesuai `perPage` dan halaman aktif. Semua di memori browser; tidak ada request Livewire saat mengetik atau sort.

```blade
@props([
    'rows' => [],
    'columns' => [],
    'badgeColumns' => [],
    'badgeClasses' => [],
    'perPage' => 15,
    'placeholder' => 'Cari…',
    'emptyText' => 'Tidak ada data.',
])

@php
    $firstColumn = (string) (array_key_first($columns) ?? 'name');
@endphp

<div
    x-data="{
        search: '',
        sortKey: '{{ $firstColumn }}',
        sortAsc: true,
        page: 1,
        perPage: {{ (int) $perPage }},
        rows: {{ \Illuminate\Support\Js::from(array_values($rows)) }},
        badgeClasses: {{ \Illuminate\Support\Js::from((object) $badgeClasses) }},
        get filtered() {
            const query = this.search.toLowerCase().trim();

            const result = this.rows.filter((row) => ! query || Object.values(row).join(' ').toLowerCase().includes(query));

            result.sort((left, right) => {
                const a = String(left[this.sortKey] ?? '').toLowerCase();
                const b = String(right[this.sortKey] ?? '').toLowerCase();

                if (a === b) {
                    return 0;
                }

                return (a < b ? -1 : 1) * (this.sortAsc ? 1 : -1);
            });

            return result;
        },
        get totalPages() {
            return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
        },
        get currentPage() {
            return Math.min(this.page, this.totalPages);
        },
        get paged() {
            const start = (this.currentPage - 1) * this.perPage;

            return this.filtered.slice(start, start + this.perPage);
        },
        get rangeFrom() {
            return this.filtered.length === 0 ? 0 : (this.currentPage - 1) * this.perPage + 1;
        },
        get rangeTo() {
            return Math.min(this.currentPage * this.perPage, this.filtered.length);
        },
        sortBy(column) {
            if (this.sortKey === column) {
                this.sortAsc = ! this.sortAsc;
            } else {
                this.sortKey = column;
                this.sortAsc = true;
            }

            this.page = 1;
        },
        badgeClass(value) {
            return this.badgeClasses[value] ?? 'sysinfo-badge';
        },
    }"
    class="space-y-3"
>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <input
            type="search"
            x-model.debounce.200ms="search"
            x-on:input="page = 1"
            placeholder="{{ $placeholder }}"
            class="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-950 dark:border-white/10 dark:bg-white/5 dark:text-white"
        />

        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
            <span x-text="`${rangeFrom}–${rangeTo} dari ${filtered.length}`"></span>

            <x-filament::button size="xs" color="gray" x-on:click="page = Math.max(1, page - 1)" x-bind:disabled="currentPage === 1">
                Sebelumnya
            </x-filament::button>

            <x-filament::button size="xs" color="gray" x-on:click="page = Math.min(totalPages, page + 1)" x-bind:disabled="currentPage >= totalPages">
                Berikutnya
            </x-filament::button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <table class="w-full text-left text-sm text-gray-950 dark:text-white">
            <thead class="border-b border-gray-200 dark:border-white/10">
                <tr>
                    @foreach ($columns as $key => $label)
                        <th scope="col" class="px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
                            <button type="button" class="inline-flex items-center gap-1 cursor-pointer select-none" x-on:click="sortBy('{{ $key }}')">
                                {{ $label }}
                                <span class="text-xs" x-show="sortKey === '{{ $key }}'" x-text="sortAsc ? '▲' : '▼'"></span>
                            </button>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                <template x-for="(row, index) in paged" :key="index">
                    <tr>
                        @foreach ($columns as $key => $label)
                            <td class="px-3 py-2 align-top">
                                @if (in_array($key, $badgeColumns, true))
                                    <span x-text="row['{{ $key }}']" :class="badgeClass(row['{{ $key }}'])"></span>
                                @else
                                    <span class="font-mono break-words" x-text="row['{{ $key }}']"></span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                </template>
            </tbody>
        </table>

        <p x-show="filtered.length === 0" x-cloak class="px-3 py-6 text-center text-sm text-gray-500 dark:text-gray-400">
            {{ $emptyText }}
        </p>
    </div>
</div>
```

## 6. Feature test — `tests/Feature/InfoSystemPageTest.php`

Menguji: isi probe, kategori modul, penolakan akses tanpa permission, render halaman, perpindahan tab, tab tak dikenal, notifikasi refresh, dan render ketiga widget.

Test membuat permission `page_InfoSystem` di dalam transaksi agar tidak bergantung pada hasil `shield:generate`:

```php
$permissionClass = config('permission.models.permission');
$admin->givePermissionTo($permissionClass::findOrCreate('page_InfoSystem', 'admin'));
```

```php
<?php

namespace Tests\Feature;

use App\Filament\Pages\InfoSystem;
use App\Filament\Widgets\SystemDiskChart;
use App\Filament\Widgets\SystemMemoryChart;
use App\Filament\Widgets\SystemModuleChart;
use App\Models\Admin;
use App\Support\SystemInfo;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Tests\TestCase;

class InfoSystemPageTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_probe_sistem_mengembalikan_data_utama(): void
    {
        $systemInfo = app(SystemInfo::class);

        $this->assertNotEmpty($systemInfo->overviewCards());
        $this->assertNotEmpty($systemInfo->osInfo());
        $this->assertNotEmpty($systemInfo->phpInfo());
        $this->assertNotEmpty($systemInfo->phpModules());
        $this->assertIsArray($systemInfo->unloadedExtensions());
        $this->assertNotEmpty($systemInfo->infraGroups());

        $composer = $systemInfo->composerPackages();
        $this->assertNotEmpty($composer['packages']);
        $this->assertSame(count($composer['packages']), $composer['counts']['total']);
        $this->assertSame(
            $composer['counts']['production'] + $composer['counts']['development'],
            $composer['counts']['total'],
        );
    }

    public function test_modul_php_memiliki_kategori_yang_dikenal(): void
    {
        $systemInfo = app(SystemInfo::class);
        $categories = ['Core', 'Zend', 'Ekstensi'];

        foreach ($systemInfo->phpModules() as $module) {
            $this->assertArrayHasKey('name', $module);
            $this->assertArrayHasKey('version', $module);
            $this->assertContains($module['category'], $categories);
        }

        $counts = $systemInfo->moduleCountsByCategory();
        $this->assertSame(count($systemInfo->phpModules()), array_sum($counts));
    }

    public function test_admin_tanpa_izin_tidak_dapat_mengakses_halaman(): void
    {
        $admin = Admin::create([
            'name' => 'Admin Tanpa Izin',
            'email' => 'admin-tanpa-izin-'.uniqid().'@kospin.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($admin, 'admin');

        $this->assertFalse(InfoSystem::canAccess());
    }

    public function test_halaman_info_system_dapat_dirender(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        Livewire::test(InfoSystem::class)
            ->assertSuccessful()
            ->assertSee('Info System')
            ->assertSee('Ringkasan')
            ->assertSee('Sistem Operasi');
    }

    public function test_tab_dapat_berpindah_dan_menampilkan_konten(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        Livewire::test(InfoSystem::class)
            ->call('setTab', 'modules')
            ->assertSet('activeTab', 'modules')
            ->assertSee('Modul PHP Aktif')
            ->call('setTab', 'composer')
            ->assertSet('activeTab', 'composer')
            ->assertSee('Paket Terpasang')
            ->call('setTab', 'infra')
            ->assertSet('activeTab', 'infra')
            ->assertSee('Runtime Aplikasi');
    }

    public function test_tab_tidak_dikenal_diabaikan(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        Livewire::test(InfoSystem::class)
            ->call('setTab', 'tidak-ada')
            ->assertSet('activeTab', 'overview');
    }

    public function test_refresh_mengirim_notifikasi(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        Livewire::test(InfoSystem::class)
            ->call('refresh')
            ->assertNotified();
    }

    public function test_widget_grafik_dapat_dirender(): void
    {
        $this->actingAs($this->superAdmin(), 'admin');

        Livewire::test(SystemMemoryChart::class)->assertSuccessful();
        Livewire::test(SystemDiskChart::class)->assertSuccessful();
        Livewire::test(SystemModuleChart::class)
            ->assertSuccessful()
            ->assertSee('Modul PHP per Kategori');
    }

    private function superAdmin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Admin Info System Test',
            'email' => 'admin-info-system-'.uniqid().'@kospin.com',
            'password' => bcrypt('password'),
        ]);

        $admin->assignRole('super_admin');

        $permissionClass = config('permission.models.permission');
        $admin->givePermissionTo($permissionClass::findOrCreate('page_InfoSystem', 'admin'));

        return $admin;
    }
}
```

## 7. Registrasi, permission, dan pemakaian

1. **Halaman terdeteksi otomatis.** Filament 3 menemukan halaman di `app/Filament/Pages` lewat `discoverPages()` pada panel provider. Bila project memakai daftar eksplisit, tambahkan:

   ```php
   ->pages([
       \App\Filament\Pages\InfoSystem::class,
   ])
   ```

2. **Widget tidak perlu didaftarkan** — cukup dikembalikan dari `getHeaderWidgets()`.

3. **Permission Shield.** Buat permission `page_InfoSystem` lalu berikan ke role yang berhak:

   ```bash
   php artisan shield:generate --all --no-interaction
   ```

   Atau lewat menu **Role** di panel. Selama permission belum ada, halaman tidak muncul di navigasi dan akses langsung menghasilkan 403 — ini perilaku `HasPageShield`, bukan bug.

4. **Navigasi.** Halaman muncul di grup `Settings` dengan sort 5. Ubah `getNavigationGroup()` / `$navigationSort` bila grup lain lebih cocok.

## 8. Keamanan

- Tidak pernah memanggil `phpinfo()`; hanya nilai ini terpilih yang dibaca, sehingga path dan variabel environment tidak ikut bocor.
- `APP_KEY`, kredensial database, dan isi `.env` tidak ditampilkan. `APP_DEBUG` hanya ditampilkan sebagai label status, bukan nilai mentah.
- Hostname, IP server, dan path aplikasi tetap terlihat. Batasi permission ke role teknis, jangan ke semua admin.
- `shell_exec` hanya dipakai untuk PowerShell di Windows dan hanya bila fungsi itu tidak masuk `disable_functions`.

## 9. Kustomisasi

| Ingin | Ubah di |
| --- | --- |
| Menambah kartu ringkasan | `SystemInfo::overviewCards()` |
| Menambah baris tab OS/PHP/Infra | `osInfo()`, `phpInfo()`, `infraGroups()` |
| Mengubah kategori Core/Zend/Ekstensi | konstanta `CORE_EXTENSIONS` |
| Menambah grafik | buat `ChartWidget` baru dengan `$isDiscovered = false`, daftarkan di `getHeaderWidgets()` |
| Mengubah isi tabel / urutan kolom | argumen `columns` pada `@include('filament.pages.partials.data-table', ...)` |
| Ukuran halaman tabel | argumen `perPage` |
| Bahasa | teks label ada di `SystemInfo`, `tabs()`, dan blade |
| Tanpa Shield | hapus `use HasPageShield;` dan `use BezhanSalleh\FilamentShield\Traits\HasPageShield;` |

## 10. Troubleshooting

| Gejala | Penyebab & solusi |
| --- | --- |
| RAM/CPU/uptime menampilkan `—` | Windows: `shell_exec` dinonaktifkan atau PowerShell tidak tersedia. Linux: `/proc` tidak terbaca (open_basedir/hosting terbatas). |
| `Tersedia di Direktori Ekstensi (0)` di Linux | Normal — distro membundel ekstensi dan tidak menyisakan file `.so` mandiri di `extension_dir`. |
| Halaman tidak muncul di menu | Permission `page_InfoSystem` belum dibuat/diberikan. |
| Tabel kosong padahal data ada | JSON terpotong karena atribut `x-data` rusak — pastikan tetap memakai `Js::from()`. |
| Grafik kosong saat pertama dibuka | Widget Filament bersifat lazy; grafik dimuat menyusul lewat request Livewire terpisah. |
| Data lama setelah `Refresh` | `Refresh` membersihkan key `system-info:hardware`; bila memakai cache store bersama, pastikan `Cache::forget` diizinkan. |

## 11. Checklist verifikasi

```bash
php -l app/Support/SystemInfo.php
vendor/bin/pint app/Support/SystemInfo.php app/Filament/Pages/InfoSystem.php \
    app/Filament/Widgets/System*.php tests/Feature/InfoSystemPageTest.php
php artisan test tests/Feature/InfoSystemPageTest.php
php artisan route:list | grep info-system
```

Hasil yang diharapkan: Pint lolos, seluruh test hijau, dan rute `admin/info-system` terdaftar.
