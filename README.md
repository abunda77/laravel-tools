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
- Exchange Rate API docs: [https://docs.api.co.id/products/exchange-rate/](https://docs.api.co.id/products/exchange-rate/)
- Exchange Rate endpoint base URL: `https://use.api.co.id`
- Exchange Rate authentication header: `x-api-co-id`

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
    Generation/
      VideoGeneration.php
    Internet/
      CurrencyExchangeRate.php
      ProxyValidate.php
      Whois.php
    Tools/
      CekResi.php
    Operations/
      ApiKeyBackupManager.php
    Settings/
  Services/
    ApiKeys/
      ApiKeyBackupService.php
    ExternalApi/
      DownloaderService.php
    Freepik/
      VideoGenerationService.php
    Internet/
      CurrencyExchangeRateService.php
      ProxyValidateService.php
      WhoisService.php
    Tools/
      CekResiService.php
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

## Sidebar Navigasi

```
Workspace
├── Dashboard
├── Downloader
└── Custom Scripts

Modules
├── Search
├── Tools
│   └── Split Cash
└── Internet

Operations
├── Backup Data ApiKey
├── Execution History
├── Settings
└── Profile
```

---

Catatan modul Internet:
- `Overview`
- `Kurs Mata Uang`
- `Proxy Validate`
- `Whois`

---

Catatan modul Tools:
- `Split Cash`
- `Cek Resi`

---

Catatan modul Video AI:
- `Generation Video`

---

## Fitur Cek Resi

Menu **Modules -> Tools -> Cek Resi** menyediakan workbench untuk melacak paket berdasarkan nomor resi dan ekspedisi.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/tools/cekresi`.
- Parameter query utama adalah `resi` dan `ekspedisi`, contoh `SPXID054330680586` dan `shopee-express`.
- Hasil menampilkan data dari key `data`: resi, ekspedisi, kode ekspedisi, status, tanggal kirim, customer service, posisi terakhir, share link, dan history pengiriman.
- History pengiriman ditampilkan sebagai timeline vertikal agar alur perjalanan paket mudah dibaca.

---

## Fitur Kurs Mata Uang

Menu **Modules -> Internet -> Kurs Mata Uang** menyediakan workbench untuk mengambil kurs mata uang real-time dari API.co.id.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `apicoid_provider`.
- Base URL yang dipakai adalah `https://use.api.co.id`.
- Endpoint yang dipanggil adalah `/currency/exchange-rate`.
- Semua request memakai header autentikasi `x-api-co-id`.
- Parameter query utama adalah `pair`, contoh `USDIDR`, `SGDIDR`, atau `EURUSD`.
- Hasil menampilkan pair, rate, waktu update data, dan raw JSON response untuk inspeksi.

---

## Fitur Proxy Validate

Menu **Modules -> Internet -> Proxy Validate** menyediakan workbench untuk memuat, memfilter, memvalidasi, dan mengekspor daftar proxy dari source GitHub publik.

- Source proxy yang tersedia saat ini: `All Proxies`, `HTTP Only`, `SOCKS5 Only`, dan `Indonesia Only`.
- Format input yang diparse adalah `IP:PORT | PROTOCOL | COUNTRY | ANONYMITY`.
- Tabel menyediakan filter di header untuk `Address`, `Protocol`, `Country`, `Anonymity`, dan `Status`.
- Setiap row memiliki:
  - checkbox untuk bulk selection,
  - action icon untuk check validitas per row,
  - action icon untuk copy `IP:PORT`.
- Bulk action `Check selected` hanya memproses row yang dipilih user.
- Tersedia quick select:
  - `Select visible valid only`
  - `Select visible unchecked only`
- Hasil validasi menampilkan status `Unchecked`, `Valid`, atau `Invalid`, beserta response time, detected IP, dan error message jika tersedia.
- Export hasil seleksi tersedia dalam format `CSV` dan `TXT`.
- Progress panel ditampilkan selama validasi berjalan agar user bisa melihat jumlah item yang sudah diproses.

Catatan:
- Validasi dilakukan dengan mencoba request ke endpoint uji publik melalui proxy yang dipilih.
- Karena banyak proxy publik lambat atau mati, jumlah status `Invalid` yang tinggi adalah kondisi yang normal.

---

## Fitur Whois

Menu **Modules -> Internet -> Whois** menyediakan workbench untuk melihat informasi registrasi domain.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/internet/whois`.
- Parameter query utama adalah `domain`, contoh `produkmastah.com`.
- Hasil menampilkan `data.domain` dan `data.result`.
- Raw WHOIS record ditampilkan dengan line break asli agar mudah dibaca dan diaudit.
- Ringkasan registrar, tanggal registrasi, tanggal kedaluwarsa, DNSSEC, dan name server diekstrak dari raw WHOIS jika tersedia.

---

## Fitur Generation Video

Menu **Modules -> Video AI -> Generation Video** menyediakan workbench untuk generate video memakai Freepik Kling v3 Standard dengan API key `freepik_provider`.

- Endpoint generate yang dipakai: `POST /v1/ai/video/kling-v3-std`
- Endpoint history task: `GET /v1/ai/video/kling-v3`
- Endpoint task by id: `GET /v1/ai/video/kling-v3/{task_id}`
- Form input mengikuti flow `Generate Image`, dengan parameter utama:
  - `prompt`
  - `aspect_ratio`
  - `duration`
  - `negative_prompt`
  - `generate_audio`
  - `cfg_scale`
- Setelah submit, sistem menyimpan `task_id`, menampilkan status task, lalu melakukan polling berkala sampai task selesai atau gagal.
- Jika task selesai, hasil video ditampilkan di halaman dan bisa dibuka atau diunduh langsung.
- Riwayat task terbaru juga ditampilkan pada panel history agar user bisa melacak task yang sedang berjalan atau hasil sebelumnya.

Catatan:
- Implementasi saat ini memakai flow text-to-video standar dan reuse pola task history dari modul `Generate Image`.
- Semua request tetap menggunakan API key tersimpan di tabel `api_keys` dengan name `freepik_provider`.

---

## Fitur Backup Data ApiKey

Menu **Operations -> Backup Data ApiKey** menyediakan pengelolaan backup untuk data API key yang tersimpan di database.

- **Backup**: membuat file JSON berisi semua API key, termasuk value yang sudah didekripsi agar bisa dipulihkan kembali.
- **Download**: mengunduh file backup yang sudah dibuat dari tabel daftar backup.
- **Restore Apikey**: meng-upload file backup JSON dan melakukan restore dengan `updateOrCreate` berdasarkan kolom `name`.
- File backup disimpan di disk lokal private: `storage/app/private/api-key-backups`.
- File backup berisi secret API key asli, sehingga tidak boleh di-commit ke repository atau dibagikan sembarangan.

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
- [x] Buat layout dashboard + sidebar
- [ ] Auth flow (login, logout, proteksi route)

### 🔲 Phase 2 — External API Module
- [ ] Config registry dari folder `docs`
- [ ] Halaman daftar kategori API
- [ ] Halaman daftar tools per kategori
- [ ] Form parameter dinamis + execute endpoint
- [ ] Tampil hasil response (JSON, image, link)
- [x] Modul Tools: Cek Resi (tracking paket + timeline vertikal)
- [x] Modul Internet: Kurs Mata Uang (API.co.id Exchange Rate)
- [x] Modul Internet: Proxy Validate (filter, bulk select, validate, export, progress)
- [x] Modul Internet: Whois (lookup domain + raw WHOIS record)

### 🔲 Phase 3 — Custom Script Module
- [ ] Registry custom script
- [ ] Script executor aman (whitelist-based)
- [ ] Log eksekusi script

### 🔲 Phase 4 — Settings & Security
- [x] Settings management (API key terpusat, timeout, queue mode)
- [x] Backup dan restore API key dari file backup
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
- **API Key Internet / Exchange Rate**: Modul Kurs Mata Uang mengambil key dari `api_keys` dengan identifier `apicoid_provider` dan mengirimkannya melalui header `x-api-co-id`.
- **API Key Ferdev Provider**: Modul Downloader, Tools -> Cek Resi, dan Internet -> Whois mengambil key dari `api_keys` dengan identifier `downloader_provider` dan mengirimkannya sebagai parameter query `apikey`.
- **Backup API Key**: File backup API key berisi secret asli agar dapat direstore. Simpan file backup di lokasi aman dan jangan commit file dari `storage/app/private/api-key-backups`.
- **Permission**: Batasi akses menu tertentu menggunakan role-based access control.

---

## Lisensi

Project ini open-source dan tersedia di bawah [MIT License](https://opensource.org/licenses/MIT).
By ERIE PUTRANTO
