# Laravel Tools

> **Internal tools panel** berbasis **Laravel 13 + Livewire** untuk menjalankan API eksternal dan custom script dari satu dashboard terpusat.

---

## Deskripsi Project

Laravel Tools adalah panel admin internal yang dirancang untuk:

- Menjalankan **API eksternal** dari berbagai provider (Downloader, Search, Tools, Internet, ApiFreaks, Random, dll.)
- Menjalankan **script/command custom** internal buatan sendiri
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
- ApiFreaks docs: [https://apifreaks.com](https://apifreaks.com)
- ApiFreaks endpoint base URL: `https://api.apifreaks.com`
- ApiFreaks authentication header: `X-apiKey`

### Kategori API yang Tersedia

- `Downloader`
- `Search`
- `Tools`
- `Internet`
- `ApiFreaks Tools`
- `Random`
- `Artificial Intelligence` *(dari docs online)*
- `Maker`, `Sticker`, `Stalker` *(dari docs online)*

---

## Struktur Modul

```text
app/
  Livewire/
    Actions/
    ExternalApi/
      DownloaderWorkbench.php
    Forms/
    Generation/
      VideoGeneration.php
    ApiFreaks/
      CommoditySymbols.php
      CreditUsage.php
      DomainSearch.php
      DomainWhoisHistoryLookup.php
      DomainWhoisLookup.php
      HistoricalCommodityPrices.php
      LiveCommodityPrices.php
      SubdomainLookup.php
    Internet/
      CurrencyExchangeRate.php
      ProxyValidate.php
      Whois.php
    Search/
      GoogleImageSearch.php
      TokopediaSearch.php
      UnsplashSearch.php
      TiktokVideoSearch.php
      YoutubeChannel.php
      YoutubeFinder.php
      YoutubeSearch.php
    Tools/
      CekResi.php
      SendWhatsapp.php
    Operations/
      ApiKeyBackupManager.php
    Settings/
  Services/
    ApiKeys/
      ApiKeyBackupService.php
    ApiFreaks/
      ApiFreaksService.php
      CommoditySymbolsService.php
      CreditUsageService.php
      DomainSearchService.php
      DomainWhoisHistoryLookupService.php
      DomainWhoisLookupService.php
      HistoricalCommodityPricesService.php
      LiveCommodityPricesService.php
      SubdomainLookupService.php
    ExternalApi/
      DownloaderService.php
    Freepik/
      VideoGenerationService.php
    Internet/
      CurrencyExchangeRateService.php
      ProxyValidateService.php
      WhoisService.php
    Search/
      GoogleImageSearchService.php
      TokopediaSearchService.php
      UnsplashSearchService.php
      TiktokVideoSearchService.php
      YoutubeChannelService.php
      YoutubeFinderService.php
      YoutubeSearchService.php
    Tools/
      CekResiService.php
      SendWhatsappService.php
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

```text
Workspace
|-- Dashboard
|-- Downloader
`-- Custom Scripts

Modules
|-- Search
|-- Tools
|   `-- Split Cash
`-- Internet

Operations
|-- Backup Data ApiKey
|-- Execution History
|-- Settings
`-- Profile
```

---

Ringkasan sidebar saat ini:
- `Workspace`: `Dashboard`, `Downloader`, `Custom Scripts`
- `Modules`: `Search`, `Tools`, `Image AI`, `Video AI`, `Internet`, `ApiFreaks Tools`, `Apify Scraper`
- `Operations`: `Backup Data ApiKey`, `Execution History`, `Settings`, `Profile`

Catatan modul Search:
- `Overview`
- `Tokopedia`
- `Unsplash`
- `Google Image`
- `TikTok Video`
- `Youtube`
- `Youtube Finder`
- `Youtube Channel`

---

Catatan modul Internet:
- `Overview`
- `Kurs Mata Uang`
- `Proxy Validate`
- `Whois`

---

Catatan modul ApiFreaks Tools:
- `Overview`
- `Credit Usage`
- `Domain WHOIS Lookup`
- `WHOIS History`
- `Domain Search`
- `Subdomain Lookup`
- `Commodity Symbols`
- `Live Commodity Prices`
- `Historical Commodity Prices`

---

Catatan modul Apify Scraper:
- `GMaps 1.0`

---

Catatan modul Tools:
- `Split Cash`
- `Cek Resi`
- `Kirim WA / Send Whatsapp`

---

Catatan modul Video AI:
- `Generation Video`

---

## Fitur Search Tokopedia

Menu **Modules -> Search -> Tokopedia** menyediakan workbench untuk mencari produk Tokopedia.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/search/tokopedia`.
- Parameter query utama adalah `query`, contoh `itel city 100`.
- Hasil mengambil data dari array `data` dan menampilkan informasi `id`, `name`, `price`, `price_number`, `shop.name`, `shop.city`, `url`, dan `thumbnail`.
- UI menyediakan dua mode tampilan: `Card View` dan `Table View`.
- Raw JSON response tetap ditampilkan untuk inspeksi payload provider.

---

## Fitur Search Unsplash

Menu **Modules -> Search -> Unsplash** menyediakan workbench untuk mencari gambar dari Unsplash.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/search/unsplash`.
- Parameter query utama adalah `query`, contoh `river in the mount`.
- Hasil mengambil data dari array `result` dan menampilkan `title`, `download`, dan `preview`.
- Setiap item dirender sebagai card gallery, tabel URL, dan raw JSON response.

---

## Fitur Search Google Image

Menu **Modules -> Search -> Google Image** menyediakan workbench untuk mencari gambar dari Google Image.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/search/gimage`.
- Parameter query utama adalah `query`, contoh `burung perkutut`.
- Hasil mengambil data dari array `result` dan menampilkan `title`, `url`, dan `image`.
- Setiap item dirender sebagai preview gambar, tabel URL, dan raw JSON response.

---

## Fitur Search TikTok Video

Menu **Modules -> Search -> TikTok Video** menyediakan workbench untuk mencari konten video TikTok.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/search/tiktok`.
- Parameter query utama adalah `query`, contoh `pargoy`.
- Hasil mengambil data dari array `result`, yang berisi daftar URL video `.mp4`.
- Setiap URL video dirender sebagai preview player video dan juga disusun dalam tabel URL.
- Raw JSON response tetap ditampilkan untuk verifikasi payload provider.

---

## Fitur Search Youtube

Menu **Modules -> Search -> Youtube** menyediakan workbench untuk mencari video Youtube.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/search/youtube`.
- Parameter query utama adalah `query`, contoh `cara mengecat dinding`.
- Hasil mengambil data dari array `result` dan menampilkan `title`, `duration`, `views`, `url`, `thumbnail`, `uploadDate`, dan `author`.
- UI menyediakan dua mode tampilan: `Card View` dan `Table View`.
- Raw JSON response tetap ditampilkan untuk verifikasi payload provider.

---

## Fitur Search Youtube Finder

Menu **Modules -> Search -> Youtube Finder** menyediakan workbench untuk mencari video YouTube langsung lewat **YouTube Data API v3**.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `youtubeapi_provider`.
- Endpoint yang dipakai adalah kombinasi `search.list` dan `videos.list` dari YouTube Data API v3.
- Parameter query utama adalah `query`, contoh `laravel tutorial`.
- Hasil pencarian dirender dalam tabel yang menampilkan `thumbnail`, `title`, `description`, `channelTitle`, `views`, `likes`, `comments`, `duration`, `publishedAt`, kualitas video, dan `url`.
- Judul video dan action `Buka video` sama-sama mengarah ke URL video YouTube yang sama.
- Jika YouTube mengembalikan `nextPageToken`, UI menyediakan tombol `load more` untuk mengambil halaman berikutnya.
- Raw JSON hasil yang sudah dipetakan tetap ditampilkan untuk inspeksi cepat.

---

## Fitur Search Youtube Channel

Menu **Modules -> Search -> Youtube Channel** menyediakan workbench untuk melihat profil channel YouTube dan daftar video upload-nya.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `youtubeapi_provider`.
- Endpoint yang dipakai adalah `channels.list`, `playlistItems.list`, dan `search.list` dari YouTube Data API v3.
- Input utama menerima `Channel ID` atau handle seperti `@Google`.
- Hasil menampilkan informasi channel seperti `title`, `description`, `subscriberCount`, `viewCount`, `videoCount`, dan thumbnail channel.
- Daftar video channel dirender dalam tabel dengan thumbnail, judul, tanggal rilis, dan link ke video.
- Tersedia mode pencarian video di dalam channel yang memakai keyword dan menampilkan total hasil pencarian channel tersebut.
- State hasil lama dipertahankan jika request refresh berikutnya gagal, sehingga data terakhir yang valid tidak langsung hilang dari UI.

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

## Fitur Kirim WA / Send Whatsapp

Menu **Modules -> Tools -> Kirim WA / Send Whatsapp** menyediakan workbench untuk mengirim pesan WhatsApp melalui provider eksternal.

- Base URL diambil dari environment `WHATSAPP_API_BASE_URL` dengan default `http://46.102.156.214:3003`.
- Endpoint yang dipanggil adalah `/send/message`.
- Semua request memakai basic auth dari `WHATSAPP_API_USERNAME` dan `WHATSAPP_API_PASSWORD`.
- Request body yang dikirim berisi `phone`, `message`, `reply_message_id`, `is_forwarded`, dan `duration`.
- Contoh target yang didukung: `6281310307754@s.whatsapp.net`.
- Hasil menampilkan `code`, `message`, `results.message_id`, `results.status`, dan raw JSON response untuk inspeksi payload provider.

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

## Fitur ApiFreaks Tools

Menu **Modules -> ApiFreaks Tools** menyediakan kumpulan workbench untuk endpoint dari provider ApiFreaks.

- Semua tool di grup ini menggunakan API key tersimpan di tabel `api_keys` dengan identifier `apifreaks_provider`.
- Base URL yang dipakai adalah `https://api.apifreaks.com`.
- Semua request memakai header autentikasi `X-apiKey`.
- Raw JSON response tetap ditampilkan di setiap halaman untuk inspeksi payload provider.

### Credit Usage API

- Route: `apifreaks-tools.credit-usage`
- Endpoint: `/v1.0/credits/usage/info`
- Method: `GET`
- Menampilkan response object dalam bentuk tabel field-value seperti status akun, subscription credits, dan one-off credits.

### Domain WHOIS Lookup API

- Route: `apifreaks-tools.domain-whois-lookup`
- Endpoint: `/v1.0/domain/whois/live`
- Method: `GET`
- Parameter utama: `domainName`
- Menampilkan tabel summary domain, registrar, contact registrant/administrative/technical/billing, name servers, dan domain statuses.

### Domain WHOIS History Lookup API

- Route: `apifreaks-tools.domain-whois-history-lookup`
- Endpoint: `/v1.0/domain/whois/history`
- Method: `GET`
- Parameter utama: `domainName`
- Menampilkan histori WHOIS dalam tabel record berisi nomor snapshot, domain, query time, create/update/expiry date, registrar, dan registrant.

### Domain Search API

- Route: `apifreaks-tools.domain-search`
- Endpoint: `/v1.0/domain/availability`
- Method: `GET`
- Parameter utama: `domain`, `source`
- Source yang didukung di UI: `dns` dan `whois`
- Menampilkan status availability domain dalam tabel ringkas satu baris.

### Subdomain Lookup API

- Route: `apifreaks-tools.subdomain-lookup`
- Endpoint: `/v1.0/subdomains/lookup`
- Method: `GET`
- Parameter utama: `domain`
- Menampilkan tabel subdomain berisi `subdomain`, `first_seen`, `last_seen`, dan `inactive_from`.

### Commodity Symbols

- Route: `apifreaks-tools.commodity-symbols`
- Endpoint: `/v1.0/commodity/symbols`
- Method: `GET`
- Menampilkan tabel symbol komoditas dengan kolom `symbol`, `name`, `category`, `currency`, `unit`, `status`, dan `updateInterval`.

### Live Commodity Prices API

- Route: `apifreaks-tools.live-commodity-prices`
- Endpoint: `/v1.0/commodity/rates/latest`
- Method: `GET`
- Parameter utama: `symbols`, `updates`, `quote`
- Menampilkan tabel rate live per symbol dengan kolom `symbol`, `rate`, `unit`, dan `quote`.

### Historical Commodity Prices API

- Route: `apifreaks-tools.historical-commodity-prices`
- Endpoint: `/v1.0/commodity/rates/historical`
- Method: `GET`
- Parameter utama: `symbols`, `date`
- Menampilkan tabel historical OHLC per symbol dengan kolom `date`, `open`, `high`, `low`, dan `close`.

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

## Fitur Apify Scraper GMaps 1.0

Menu **Modules -> Apify Scraper -> GMaps 1.0** menyediakan workbench untuk menjalankan actor Apify yang mengambil data bisnis dari Google Maps.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `apify_provider`.
- Endpoint yang dipakai adalah `POST /v2/acts/sbEjxxfeFlEBHijJS/run-sync-get-dataset-items` dari Apify API.
- Parameter utama yang wajib adalah `search_query`.
- Parameter `gmaps_url`, `latitude`, `longitude`, `area_width`, `area_height`, dan `max_results` bersifat opsional.
- Jika `area_width` atau `area_height` dikosongkan, backend memakai default `20`.
- Jika `max_results` dikosongkan, backend memakai default `500`.
- Hasil response dirender sebagai tabel dinamis berdasarkan key payload actor, sehingga perubahan struktur kolom dari provider tetap bisa ditampilkan.
- Data hasil scrape tidak disimpan ke database; semuanya hanya diproses pada halaman aktif.
- Tersedia export langsung ke format `CSV`, `XLSX`, dan `PDF`.

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
- `php artisan serve` - server Laravel
- `php artisan queue:listen` - queue worker
- `php artisan pail` - log viewer
- `npm run dev` - Vite (hot reload)

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

### Phase 1 - Foundation *(sedang berjalan)*
- [x] Inisialisasi Laravel 13
- [x] Install Breeze + Livewire + Volt
- [x] Konfigurasi Tailwind CSS
- [x] Buat layout dashboard + sidebar
- [ ] Auth flow (login, logout, proteksi route)

### Phase 2 - External API Module
- [ ] Config registry dari folder `docs`
- [ ] Halaman daftar kategori API
- [ ] Halaman daftar tools per kategori
- [ ] Form parameter dinamis + execute endpoint
- [ ] Tampil hasil response (JSON, image, link)
- [x] Modul Search: Tokopedia (card view + table view + raw JSON)
- [x] Modul Search: Unsplash (gallery card + table URL + raw JSON)
- [x] Modul Search: Google Image (preview image + table URL + raw JSON)
- [x] Modul Search: TikTok Video (preview video + table URL + raw JSON)
- [x] Modul Search: Youtube (card view + table view + raw JSON)
- [x] Modul Search: Youtube Finder (table view + pagination + YouTube Data API v3)
- [x] Modul Search: Youtube Channel (profil channel + daftar upload + pencarian dalam channel)
- [x] Modul Tools: Cek Resi (tracking paket + timeline vertikal)
- [x] Modul Internet: Kurs Mata Uang (API.co.id Exchange Rate)
- [x] Modul Internet: Proxy Validate (filter, bulk select, validate, export, progress)
- [x] Modul Internet: Whois (lookup domain + raw WHOIS record)

### Phase 3 - Custom Script Module
- [ ] Registry custom script
- [ ] Script executor aman (whitelist-based)
- [ ] Log eksekusi script

### Phase 4 - Settings & Security
- [x] Settings management (API key terpusat, timeout, queue mode)
- [x] Backup dan restore API key dari file backup
- [ ] Role & Permission (spatie/laravel-permission)
- [ ] Audit log (spatie/laravel-activitylog)

### Phase 5 - Reliability
- [ ] Queue untuk task berat (downloader, OCR, dll.)
- [ ] Retry & timeout configuration
- [ ] Health check provider API
- [ ] Test automation

---

## Catatan Keamanan

- **Custom Script Executor**: Hindari menjalankan shell command bebas dari input user. Prioritaskan `Artisan command` atau `PHP class handler`. Jika shell command diperlukan, gunakan **whitelist** command yang diizinkan.
- **API Key**: Semua input `value` dari halaman manajemen API Keys akan dienkripsi dari bawaan sistem sebelum masuk ke database (`Crypt::encryptString`) untuk faktor keamanan.
- **API Key Internet / Exchange Rate**: Modul Kurs Mata Uang mengambil key dari `api_keys` dengan identifier `apicoid_provider` dan mengirimkannya melalui header `x-api-co-id`.
- **API Key Ferdev Provider**: Modul Downloader, Search -> Tokopedia, Search -> Unsplash, Search -> Google Image, Search -> TikTok Video, Search -> Youtube, Tools -> Cek Resi, dan Internet -> Whois mengambil key dari `api_keys` dengan identifier `downloader_provider` dan mengirimkannya sebagai parameter query `apikey`.
- **API Key YouTube Data API**: Modul Search -> Youtube Finder dan Search -> Youtube Channel mengambil key dari `api_keys` dengan identifier `youtubeapi_provider` untuk request ke YouTube Data API v3.
- **API Key Apify**: Modul Apify Scraper -> GMaps 1.0 mengambil key dari `api_keys` dengan identifier `apify_provider` untuk request ke Apify actor API.
- **Backup API Key**: File backup API key berisi secret asli agar dapat direstore. Simpan file backup di lokasi aman dan jangan commit file dari `storage/app/private/api-key-backups`.
- **Permission**: Batasi akses menu tertentu menggunakan role-based access control.

---

## Lisensi

Project ini open-source dan tersedia di bawah [MIT License](https://opensource.org/licenses/MIT).
By ERIE PUTRANTO
