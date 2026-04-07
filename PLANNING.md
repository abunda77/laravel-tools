# PLANNING

## 1. Ringkasan Project

Project ini direncanakan sebagai aplikasi **Laravel 13 + Livewire** untuk menjalankan:

- API eksternal berbasis endpoint dari provider sample pada folder [`docs`](./docs)
- Script custom internal buatan sendiri
- Dashboard admin dengan login dan sidebar navigasi per kategori fitur

Fokus utamanya adalah membuat panel tools yang rapi, mudah ditambah modul baru, dan aman untuk penggunaan internal maupun multi-user terbatas.

## 2. Hasil Review Awal

Berdasarkan folder [`docs`](./docs), struktur fitur sample API sudah cukup jelas dan bisa langsung dijadikan dasar menu sidebar:

- `Downloader`
- `Search`
- `Tools`
- `Internet`
- `Random`

Catatan sumber referensi:

- Referensi lokal: folder [`docs`](./docs)
- Referensi online yang lebih lengkap: `https://api.ferdev.my.id/docs`

Temuan awal:

- Semua endpoint menggunakan `GET`
- Semua endpoint membutuhkan `apikey`
- Base URL sample: `https://api.ferdev.my.id`
- Parameter per endpoint berbeda-beda, sehingga form input perlu dibuat dinamis per modul
- Ada duplikasi dokumen `Tools` pada `table (4).md` dan `table (5).md`
- Ada beberapa penamaan endpoint yang perlu dinormalisasi saat implementasi, misalnya `anime Stream` mengandung spasi dan perlu diverifikasi saat coding
- Dokumentasi online berisi kategori tambahan di luar file lokal, seperti `Artificial Intelligence`, `Maker`, `Sticker`, dan `Stalker`

Kesimpulan review:

- Aplikasi sebaiknya tidak meng-hardcode form untuk setiap endpoint satu per satu di awal
- Lebih baik memakai pendekatan **config-driven modules**, supaya tiap menu/submenu bisa didefinisikan dari konfigurasi dan dirender otomatis
- Perlu pemisahan jelas antara **external API executor** dan **custom script executor**
- Dokumentasi online sebaiknya dijadikan **source of truth utama** untuk endpoint dan parameter, sedangkan file lokal dipakai sebagai seed awal

## 3. Requirement Utama

Requirement dari Anda:

1. Framework Laravel 13
2. Menggunakan Livewire
3. Login dengan dashboard dan sidebar
4. Fitur untuk menjalankan API eksternal
5. Fitur untuk menjalankan script custom sendiri
6. Sidebar berisi menu-menu fitur

## 4. Rekomendasi Stack dan Library Tambahan

Berikut tambahan yang disarankan agar project lebih stabil dan cepat dikembangkan.

### Wajib / sangat direkomendasikan

- `laravel/breeze` atau `laravel/jetstream`
  - Untuk autentikasi awal
  - Rekomendasi: **Laravel Breeze + Livewire**
  - Alasan: lebih ringan, cepat untuk bootstrap dashboard internal

- `livewire/livewire`
  - Fondasi UI interaktif tanpa banyak JavaScript custom

- `livewire/volt` (opsional tapi direkomendasikan)
  - Jika ingin struktur komponen Livewire lebih ringkas
  - Cocok untuk dashboard yang berkembang cepat

- `spatie/laravel-permission`
  - Untuk role dan permission
  - Minimal role: `admin`, `operator`
  - Berguna jika nanti ingin membatasi akses menu/script tertentu

- `guzzlehttp/guzzle`
  - Client HTTP untuk integrasi API eksternal
  - Laravel HTTP Client memang sudah cukup, tetapi Guzzle tetap ikut di bawah Laravel; implementasi utama sebaiknya gunakan `Http` facade Laravel

- `laravel/horizon` atau queue worker standar
  - Dibutuhkan jika ada eksekusi script/API yang berat atau berjalan async
  - Minimal gunakan queue database/redis sejak awal

### Direkomendasikan untuk maintainability

- `spatie/laravel-settings` atau pendekatan settings table sendiri
  - Untuk menyimpan `base_url`, `apikey`, timeout, retry, dan konfigurasi global

- `spatie/laravel-activitylog`
  - Logging aktivitas user
  - Sangat berguna karena project ini mengeksekusi API dan script

- `spatie/laravel-backup` (fase berikutnya)
  - Jika data konfigurasi dan histori eksekusi mulai penting

- `pestphp/pest`
  - Untuk testing yang lebih cepat ditulis

- `laravel/pulse` (opsional)
  - Monitoring performa app

### Frontend pendukung

- `tailwindcss`
  - Default choice paling efisien untuk dashboard Livewire

- `alpinejs`
  - Dipakai seperlunya untuk interaksi ringan pada sidebar, modal, dropdown, tab

- `wireui`, `flux`, atau komponen UI sejenis
  - Opsional
  - Bisa mempercepat pembuatan form, modal, notification
  - Jika ingin stack tetap ramping, bisa tanpa library UI tambahan

## 5. Rekomendasi Arsitektur

### Pendekatan utama

Gunakan pola berikut:

- **UI Layer**: Livewire pages/components
- **Application Layer**: service class untuk eksekusi API dan script
- **Configuration Layer**: daftar modul/menu/submenu dalam file config atau database
- **Logging Layer**: simpan histori request, response ringkas, status, durasi, user executor

### Modul inti yang disarankan

1. **Authentication**
   - Login
   - Logout
   - Proteksi dashboard

2. **Dashboard**
   - Ringkasan jumlah request API
   - Ringkasan jumlah script dijalankan
   - Status terakhir eksekusi
   - Shortcut ke modul yang paling sering dipakai

3. **Sidebar Navigation**
   - Dashboard
   - External API
     - Downloader
     - Search
     - Tools
     - Internet
     - Random
   - Custom Scripts
   - Execution History
   - Settings
   - User Management

4. **External API Executor**
   - Pilih kategori
   - Pilih endpoint/submenu
   - Tampilkan form parameter dinamis
   - Jalankan request
   - Tampilkan hasil JSON / preview / link download

5. **Custom Script Executor**
   - Daftar script internal
   - Input parameter per script
   - Jalankan script sync/async
   - Tampilkan output, log, dan status

6. **Execution History**
   - Simpan histori API dan script
   - Filter by user, status, tipe executor, tanggal

7. **Settings**
   - API base URL
   - API key
   - timeout
   - retry
   - queue mode

8. **User & Permission**
   - Manage user
   - Role-based access ke menu tertentu

## 6. Rancangan Sidebar

Contoh struktur sidebar yang saya rekomendasikan:

- Dashboard
- External API
  - Downloader
  - Search
  - Tools
  - Internet
  - Random
- Custom Scripts
  - Script List
  - Run Script
- Execution History
- Settings
  - API Settings
  - App Settings
- Users
  - User List
  - Roles & Permissions

Catatan:

- Kategori pada `docs` sangat cocok dijadikan group menu utama
- Karena kategori di docs online bisa bertambah, sidebar sebaiknya dirancang fleksibel dan berbasis registry/config
- Submenu endpoint tidak perlu semuanya ditampilkan permanen di sidebar level paling bawah
- Lebih baik tiap kategori membuka halaman index yang berisi daftar tool, search bar, dan filter agar sidebar tetap ringkas

## 7. Rancangan Data dan Konfigurasi

### Opsi terbaik untuk fase awal

Gabungkan:

- **config file** untuk seed awal daftar endpoint API
- **database table** untuk histori eksekusi, setting, dan custom scripts

### Struktur data yang direkomendasikan

#### `api_modules`

Menyimpan definisi fitur API jika nanti ingin editable via admin.

Kolom:

- `id`
- `category`
- `name`
- `slug`
- `method`
- `endpoint`
- `parameters` (JSON)
- `is_active`
- `sort_order`

#### `custom_scripts`

Kolom:

- `id`
- `name`
- `slug`
- `description`
- `handler_type` (`artisan`, `php_class`, `shell_command`)
- `handler_target`
- `parameters` (JSON)
- `is_active`
- `queueable`

#### `execution_histories`

Kolom:

- `id`
- `user_id`
- `type` (`external_api`, `custom_script`)
- `module_name`
- `request_payload` (JSON)
- `response_payload` (JSON / TEXT)
- `status`
- `duration_ms`
- `error_message`
- `executed_at`

#### `app_settings`

Kolom:

- `key`
- `value`

## 8. Rancangan Teknis Eksekusi

### External API

Gunakan service seperti:

- `App\Services\ExternalApi\ExternalApiService`
- `App\Services\ExternalApi\ModuleRegistry`

Tanggung jawab:

- Mengambil definisi modul dari config/database
- Menyusun parameter request
- Menambahkan `apikey`
- Menjalankan HTTP request
- Menangani timeout, retry, dan error
- Menyimpan histori eksekusi

### Custom Script

Gunakan service seperti:

- `App\Services\Scripts\ScriptRunner`
- `App\Services\Scripts\ScriptRegistry`

Jenis handler yang didukung:

- Artisan command
- PHP class handler
- Shell command terbatas

Rekomendasi penting:

- Untuk keamanan, **hindari shell command bebas dari input user**
- Prioritaskan `Artisan command` atau `PHP class handler`
- Jika shell command memang dibutuhkan, whitelist command yang boleh dijalankan

## 9. Alur Halaman per Fitur

### Dashboard

Menampilkan:

- statistik ringkas
- aktivitas terbaru
- quick access menu

### Halaman kategori API

Menampilkan:

- daftar tool dalam kategori
- pencarian tool
- filter berdasarkan nama endpoint

### Halaman detail tool API

Menampilkan:

- nama tool
- endpoint
- method
- parameter form dinamis
- tombol execute
- hasil response

### Halaman custom script

Menampilkan:

- daftar script aktif
- form parameter
- tombol execute
- output log

### Halaman history

Menampilkan:

- tabel histori
- status badge
- waktu eksekusi
- detail request/response

## 10. MVP Scope

Supaya pengerjaan tetap cepat dan terarah, MVP sebaiknya mencakup:

1. Laravel 13 setup
2. Breeze + Livewire auth
3. Dashboard layout dengan sidebar
4. Modul config-driven untuk sample API dari folder `docs`
5. Halaman kategori API
6. Halaman execute endpoint dengan form dinamis
7. Modul custom scripts dasar
8. Settings untuk `base_url` dan `apikey`
9. Execution history
10. Role minimal `admin`

## 11. Fitur Tambahan yang Saya Rekomendasikan

Fitur ini belum Anda minta eksplisit, tetapi menurut saya penting untuk project seperti ini:

### 1. Retry dan timeout configuration

Karena project bergantung pada API eksternal, timeout dan retry harus bisa diatur.

### 2. Histori dan audit log

Tanpa histori, sulit menelusuri error dari request API dan script internal.

### 3. Queue untuk task berat

Beberapa tools seperti OCR, image processing, transcript, downloader, atau custom script bisa memakan waktu lama. Queue akan membuat UI tetap responsif.

### 4. Response viewer yang fleksibel

Karena format output bisa berupa JSON, text, image, atau link file, viewer hasil perlu mendukung beberapa jenis tampilan.

### 5. Search tool registry

Jumlah endpoint cukup banyak. Search di halaman tool akan jauh lebih berguna daripada memaksa semua submenu muncul di sidebar.

### 6. Permission per module

Jika nanti ada user operator, tidak semua menu perlu dibuka penuh.

### 7. Health check provider API

Perlu halaman sederhana untuk mengecek apakah base API masih responsif.

## 12. Risiko dan Antisipasi

### Risiko

- Dokumentasi sample API bisa berubah
- Beberapa endpoint mungkin lambat atau tidak stabil
- Respons antar endpoint kemungkinan tidak konsisten
- Menjalankan script custom bisa membuka risiko keamanan

### Antisipasi

- Simpan definisi modul secara terpusat
- Tambahkan retry, timeout, dan fallback error handling
- Simpan log request/response ringkas
- Batasi script runner hanya ke handler yang di-whitelist

## 13. Struktur Folder yang Disarankan

```text
app/
  Actions/
  Livewire/
    Dashboard/
    ExternalApi/
    Scripts/
    Settings/
    History/
  Services/
    ExternalApi/
    Scripts/
  Support/
    Registries/
config/
  api-modules.php
database/
  migrations/
  seeders/
resources/
  views/
    layouts/
    livewire/
routes/
  web.php
```

## 14. Tahapan Implementasi

### Phase 1 - Foundation

- Inisialisasi Laravel 13
- Install Breeze + Livewire
- Buat layout dashboard + sidebar
- Buat auth flow

### Phase 2 - External API Module

- Buat config registry dari dokumen `docs`
- Buat kategori API
- Buat halaman daftar tools
- Buat halaman execute tool + hasil response

### Phase 3 - Custom Script Module

- Buat registry custom script
- Buat eksekutor script aman
- Tambahkan log eksekusi

### Phase 4 - Settings & Security

- Tambahkan settings management
- Tambahkan role & permission
- Tambahkan audit log

### Phase 5 - Reliability

- Tambahkan queue
- Tambahkan retry, timeout, monitoring
- Tambahkan test automation

## 15. Keputusan Teknis yang Saya Rekomendasikan

Jika project ini akan saya mulai dari nol, saya sarankan keputusan berikut:

- Framework: **Laravel 13**
- Auth starter: **Laravel Breeze**
- Reactive UI: **Livewire**
- Permission: **spatie/laravel-permission**
- Logging activity: **spatie/laravel-activitylog**
- Queue: **database queue** dulu, bisa naik ke Redis/Horizon nanti
- Modul API: **config-driven**
- Script executor: **registry + whitelist-based runner**
- Styling: **Tailwind**

## 16. Definisi Selesai Tahap Planning

Tahap planning dianggap selesai jika:

- struktur menu sidebar disepakati
- stack utama disepakati
- scope MVP disepakati
- modul external API dan custom scripts disepakati
- model data dasar disepakati

## 17. Next Step

Langkah berikutnya yang paling masuk akal:

1. Generate project Laravel 13
2. Install Breeze + Livewire
3. Buat layout dashboard + sidebar
4. Import definisi modul sample API dari folder `docs`
5. Bangun halaman executor untuk endpoint API
6. Bangun modul custom script runner

---

Dokumen ini disusun berdasarkan requirement saat ini dan review awal terhadap sample API pada folder [`docs`](./docs). Jika Anda ingin, tahap berikutnya saya bisa langsung lanjut membuat scaffold project Laravel beserta struktur dashboard, sidebar, dan registry modul API-nya.
