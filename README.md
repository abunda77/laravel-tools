# Laravel Tools

> **Internal tools panel** berbasis **Laravel 13 + Livewire** untuk menjalankan API eksternal dan custom script dari satu dashboard terpusat.

---

## Deskripsi Project

Laravel Tools adalah panel admin internal yang dirancang untuk:

- Menjalankan **API eksternal** dari berbagai provider (Downloader, Search, Tools, Internet, Random, dll.)
- Menjalankان **script/command custom** internal buatan sendiri
- Memonitor histori eksekusi, status, dan log setiap request

Pendekatan utama adalah **config-driven modules**, sehingga menu dan submenu API bisa ditambahkan dari konfigurasi tanpa perlu mengubah kode program satu per satu.

---

## Stack Teknologi

| Layer | Teknologi |
|---|---|
| Framework | Laravel 13 (PHP ^8.3) |
| Reactive UI | Livewire 3 + Volt |
| Auth | Laravel Breeze |
| Frontend | Tailwind CSS + Alpine.js |
| Queue | Database queue (upgrade ke Redis/Horizon bila perlu) |
| HTTP Client | Laravel Http Facade (berbasis Guzzle) |
| Permission | spatie/laravel-permission *(planned)* |
| Activity Log | spatie/laravel-activitylog *(planned)* |
| Testing | PHPUnit / Pest *(planned)* |

---

## Referensi API

- Dokumentasi lokal: folder [`docs/`](./docs)
- Dokumentasi online: [https://api.ferdev.my.id/docs](https://api.ferdev.my.id/docs)
- Base URL: `https://api.ferdev.my.id`
- Semua endpoint menggunakan method `GET` dan membutuhkan `apikey`

### Kategori API yang Tersedia

- `Downloader`
- `Search`
- `Tools`
- `Internet`
- `Random`
- `Artificial Intelligence` *(dari docs online)*
- `Maker`, `Sticker`, `Stalker` *(dari docs online)*

---

## Struktur Modul

```
app/
  Livewire/
    Actions/
    ExternalApi/
      DownloaderWorkbench.php
    Forms/
    Settings/
  Services/
    ExternalApi/
      DownloaderService.php
  Support/
    Registries/
config/
  api-modules.php  (planned)
database/
  migrations/
  seeders/
docs/
  table.md
  table (1-5).md
  file.md
```

---

## Sidebar Navigasi (Rencana)

```
├── Dashboard
├── External API
│   ├── Downloader
│   ├── Search
│   ├── Tools
│   ├── Internet
│   └── Random
├── Custom Scripts
│   ├── Script List
│   └── Run Script
├── Execution History
├── Settings
│   ├── API Settings
│   └── App Settings
└── Users
    ├── User List
    └── Roles & Permissions
```

---

## Struktur Database (Planned)

### `api_modules`
Definisi modul API yang bisa diatur via admin.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| category | string | Kategori modul (Downloader, Search, dll.) |
| name | string | Nama tool |
| slug | string | Identifier unik |
| method | string | HTTP method (GET) |
| endpoint | string | Path endpoint |
| parameters | JSON | Daftar parameter form |
| is_active | boolean | Status aktif/nonaktif |
| sort_order | integer | Urutan tampil |

### `custom_scripts`
Definisi script internal yang bisa dijalankan.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| name | string | Nama script |
| slug | string | Identifier unik |
| description | text | Deskripsi script |
| handler_type | enum | `artisan`, `php_class`, `shell_command` |
| handler_target | string | Target handler |
| parameters | JSON | Daftar parameter |
| is_active | boolean | Status aktif |
| queueable | boolean | Jalankan di queue |

### `execution_histories`
Log setiap eksekusi API atau script.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| user_id | foreignId | User yang mengeksekusi |
| type | enum | `external_api`, `custom_script` |
| module_name | string | Nama modul |
| request_payload | JSON | Data input request |
| response_payload | text | Data output response |
| status | string | Status eksekusi |
| duration_ms | integer | Durasi dalam ms |
| error_message | text | Pesan error (jika ada) |
| executed_at | timestamp | Waktu eksekusi |

### `api_keys`
Penyimpanan tersentralisasi untuk semua API key yang dibutuhkan modul eksternal. Nilai API key dienkripsi di database.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| name | string | Identifier unik (contoh: `downloader_provider`) |
| label | string | Nama tampilan di UI |
| description | text | Deskripsi kegunaan (opsional) |
| value | text | Nilai API key (encrypted) |
| is_active | boolean | Status aktif/nonaktif key |

### `app_settings`
Konfigurasi global aplikasi (Timeout, Retry, Queue Mode).

| Kolom | Tipe |
|---|---|
| key | string |
| value | text |

---

## Instalasi

### Prasyarat

- PHP >= 8.3
- Composer
- Node.js & npm
- SQLite / MySQL / PostgreSQL

### Langkah Setup

```bash
# Clone repository
git clone <repo-url>
cd laravel-tools

# Install dependencies
composer install
npm install

# Salin file environment
cp .env.example .env

# Generate application key
php artisan key:generate

# Jalankan migrasi database
php artisan migrate

# Build asset frontend
npm run build
```

### Konfigurasi `.env`

Sesuaikan variabel berikut:

```env
APP_NAME="Laravel Tools"
APP_URL=http://localhost

DB_CONNECTION=sqlite
# atau sesuaikan untuk MySQL/PostgreSQL

# Catatan: API Key untuk layanan eksternal kini dikelola langsung 
# melalui antarmuka web (Menu Settings -> API Keys), bukan via .env.
```

---

## Menjalankan Aplikasi

### Development (semua service sekaligus)

```bash
composer run dev
```

Perintah ini menjalankan:
- `php artisan serve` — server Laravel
- `php artisan queue:listen` — queue worker
- `php artisan pail` — log viewer
- `npm run dev` — Vite (hot reload)

### Hanya Laravel Server

```bash
php artisan serve
```

---

## Testing

```bash
# Jalankan test suite
composer run test

# Atau langsung dengan PHP artisan
php artisan test
```

---

## Tahapan Pengembangan (Roadmap)

### ✅ Phase 1 — Foundation *(sedang berjalan)*
- [x] Inisialisasi Laravel 13
- [x] Install Breeze + Livewire + Volt
- [x] Konfigurasi Tailwind CSS
- [ ] Buat layout dashboard + sidebar
- [ ] Auth flow (login, logout, proteksi route)

### 🔲 Phase 2 — External API Module
- [ ] Config registry dari folder `docs`
- [ ] Halaman daftar kategori API
- [ ] Halaman daftar tools per kategori
- [ ] Form parameter dinamis + execute endpoint
- [ ] Tampil hasil response (JSON, image, link)

### 🔲 Phase 3 — Custom Script Module
- [ ] Registry custom script
- [ ] Script executor aman (whitelist-based)
- [ ] Log eksekusi script

### 🔲 Phase 4 — Settings & Security
- [x] Settings management (API key terpusat, timeout, queue mode)
- [ ] Role & Permission (spatie/laravel-permission)
- [ ] Audit log (spatie/laravel-activitylog)

### 🔲 Phase 5 — Reliability
- [ ] Queue untuk task berat (downloader, OCR, dll.)
- [ ] Retry & timeout configuration
- [ ] Health check provider API
- [ ] Test automation

---

## Catatan Keamanan

- **Custom Script Executor**: Hindari menjalankan shell command bebas dari input user. Prioritaskan `Artisan command` atau `PHP class handler`. Jika shell command diperlukan, gunakan **whitelist** command yang diizinkan.
- **API Key**: Semua input `value` dari halaman manajemen API Keys akan dienkripsi dari bawaan sistem sebelum masuk ke database (`Crypt::encryptString`) untuk faktor keamanan.
- **Permission**: Batasi akses menu tertentu menggunakan role-based access control.

---

## Lisensi

Project ini open-source dan tersedia di bawah [MIT License](https://opensource.org/licenses/MIT).
