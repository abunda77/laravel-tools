# Changelog

Semua perubahan penting pada proyek ini akan dicatat di file ini.

Format berdasarkan [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Authentication: HTTP Basic Authentication untuk endpoint dokumentasi API (`/docs/api`) dengan konfigurasi environment `DOCS_BASIC_AUTH_USERNAME` dan `DOCS_BASIC_AUTH_PASSWORD` (middleware `DocsBasicAuth`, config `docs.basic_auth`, Gate `viewApiDocs`, dokumentasi di `docs/api-docs-authentication.md`)
- Authentication: REST API `v1` (`/api/v1/auth/*`) menggunakan Laravel Sanctum Personal Access Token — login, logout, logout-all (hapus semua token), me (info user), tokens (daftar token aktif), revoke-token (hapus token tertentu)
- Bookmark (menu **Modules -> Internet -> Bookmark**): simpan tautan dengan link preview otomatis (title, Open Graph image, deskripsi via `BookmarkPreviewService`), kategori dinamis, pencarian & filter, dan soft delete
- Bookmark: REST API `v1` (`/api/v1/bookmarks` & `/api/v1/bookmark-categories`) menggunakan Laravel Sanctum Personal Access Token — list + pagination + filter, simpan, detail, update, hapus, preview metadata tanpa simpan
- Dependency: `laravel/sanctum` untuk autentikasi API (Personal Access Token)
- Downloader: YouTube Shorts provider (endpoint `/downloader/ytshorts`) + sidebar menu "Download Youtube Short"
- Downloader: YouTube MP4 provider (endpoint `/downloader/ytmp4`) + sidebar menu "Download Youtube MP4"
- Image AI: Image2Prompt
- ApiFreaks: IP Geolocation Lookup
- Dependency: `binarybuilds/laritor-client` (^3.0)
- Tools: QR Code Generator (menu **Modules -> Tools -> QR Code**) dengan generate PNG/JPG, preview base64, download file temporary, dan cleanup otomatis/manual
- Tools: Holiday (menu **Modules -> Tools -> Holiday**) untuk jadwal libur nasional Indonesia menggunakan API.co.id (`apicoid_provider`): daftar libur per tahun, cek tanggal, dan libur mendatang (dihitung dari field `is_upcoming`/`days_until` pada endpoint `/holidays/indonesia` karena endpoint `/holidays/indonesia/upcoming` tidak tersedia di API)
- Tools: Wilayah API (menu **Modules -> Tools -> Wilayah API**) untuk menelusuri wilayah administrasi Indonesia (provinsi, kabupaten/kota, kecamatan, desa/kelurahan) menggunakan API.co.id (`apicoid_provider`): hierarki bertahap dengan filter nama per level, kode wilayah, kode pos, dan status `is_courier_support`
- Command: `cleanup:temporary-uploads` untuk membersihkan temporary upload Livewire dan file QR Code kedaluwarsa

### Changed
- Bookmark: dokumentasi lengkap di `BOOKMARK.md` (fitur, struktur database, endpoint API `v1`, format response, error handling, contoh integrasi cURL/JS/Dart/Kotlin/PHP)
- Downloader: workbench `mount()` menerima `selectedProvider` opsional agar halaman ytshorts bisa pre-select provider
- Downloader: validation rule `selectedProvider` ditambah `ytshorts`; stat provider count dijadikan dinamis
- Downloader: validation rule `selectedProvider` ditambah `ytmp4`; provider `ytmp4` ditambah ke `DownloaderService::PROVIDERS` (endpoint `/downloader/ytmp4`)
- Update README — dokumentasi fitur Downloader (Instagram, TikTok, Facebook, YouTube Shorts, YouTube MP4)
- Update CLAUDE.md

---

## [1.1.0] - 2026-06-24

### Added
- Image AI: Image2Prompt
- Character Sheet menu
- Freepik Image Search

### Changed
- Update README — dokumentasi Image AI service
- Update Composer dependencies

### Removed
- Nonaktifkan Freepik service (feature-gated)

---

## [1.0.0] - 2026-04-29

### Added
- Anime Quotes
- Wall Meter
- Apify Service
- YouTube Finder & Channel
- ApiFreaks Service
- Calculator PVC
- YouTube Search
- Send WhatsApp
- Search Unsplash & Google Image
- TikTok Video
- Tokopedia Search
- Whois & Cek Resi
- ChatBot (Laravel AI SDK)
- Video Generation (Freepik)
- Proxy Validation
- Backup API Key (export/import)
- Button Copy to Text
- Exchange API (api.co.id)
- Image2Prompt (Freepik)
- Submenu Sidebar
- Laravel Boost
- Split Cash
- Image Generation (Freepik)

### Changed
- Update halaman login (Laravel Breeze)
- Update route register & gitignore
- Update README

### Fixed
- Bug minor API Key Manager
- Menu dropdown slow
- Device ID send WhatsApp
- Bug cek proxy validate
- HTTPS forward proto
- Test case data hilang
- Split Cash

### Removed
- Docs (dipindahkan)

---

## Versi 1.0 Aplikasi Tools — 2026-04-07

Rilis perdana Laravel Tools — panel admin internal yang menyatukan integrasi API eksternal dan skrip kustom.
