# Changelog

Semua perubahan penting pada proyek ini akan dicatat di file ini.

Format berdasarkan [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
dan proyek ini mengikuti [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- Downloader: YouTube Shorts provider (endpoint `/downloader/ytshorts`) + sidebar menu "Download Youtube Short"
- Image AI: Image2Prompt
- ApiFreaks: IP Geolocation Lookup
- Dependency: `binarybuilds/laritor-client` (^3.0)

### Changed
- Downloader: workbench `mount()` menerima `selectedProvider` opsional agar halaman ytshorts bisa pre-select provider
- Downloader: validation rule `selectedProvider` ditambah `ytshorts`; stat provider count dijadikan dinamis
- Update README — dokumentasi fitur Downloader (Instagram, TikTok, Facebook, YouTube Shorts)
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
