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
                'sub' => 'Livewire '.$this->packageVersion('livewire/livewire').' • Volt '.$this->packageVersion('livewire/volt'),
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
