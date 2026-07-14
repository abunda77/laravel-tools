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
| AI SDK | Laravel AI (`laravel/ai`) |
| Export | dompdf/dompdf (PDF), phpoffice/phpspreadsheet (XLSX) |
| Testing | PHPUnit |
| Permission | spatie/laravel-permission *(planned)* |
| Activity Log | spatie/laravel-activitylog *(planned)* |

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
- Holiday API docs: [https://docs.api.co.id/products/holiday/](https://docs.api.co.id/products/holiday/)
- Holiday endpoint base URL: `https://use.api.co.id`
- Holiday authentication header: `x-api-co-id`
- Wilayah Indonesia API docs: [https://docs.api.co.id/products/indonesia-regional/](https://docs.api.co.id/products/indonesia-regional/)
- Wilayah Indonesia endpoint base URL: `https://use.api.co.id`
- Wilayah Indonesia authentication header: `x-api-co-id`

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
  Ai/
    Agents/
      ChatBotAgent.php
  Livewire/
    Actions/
      Logout.php
    ApiFreaks/
      ApiFreaksComponent.php  (base class)
      CommoditySymbols.php
      CreditUsage.php
      DomainSearch.php
      DomainWhoisHistoryLookup.php
      DomainWhoisLookup.php
      HistoricalCommodityPrices.php
      IpGeolocationLookup.php
      LiveCommodityPrices.php
      SubdomainLookup.php
    ApifyScraper/
      GmapsScraper.php
    ExternalApi/
      DownloaderWorkbench.php
    Forms/
      LoginForm.php
    Generation/
      ImageGeneration.php
      Index.php
      VideoGeneration.php
    ImageAi/
      ImageToPrompt.php
      ImprovePrompt.php
    Internet/
      CurrencyExchangeRate.php
      ProxyValidate.php
      Whois.php
    Operations/
      ApiKeyBackupManager.php
    Search/
      AnimeQuoteSearch.php
      FreepikImage.php
      GoogleImageSearch.php
      TiktokVideoSearch.php
      TokopediaSearch.php
      UnsplashSearch.php
      YoutubeChannel.php
      YoutubeFinder.php
      YoutubeSearch.php
    Settings/
      ApiKeyManager.php
      GeneralSettings.php
      LlmModelManager.php
    Tools/
      CekResi.php
      Holiday.php
      PvcCalculator.php
      SendWhatsapp.php
      WallMeter.php
      WilayahApi.php
    Workspace/
      ChatBot.php
  Models/
    ApiKey.php
    AppSetting.php
    ChatAttachment.php
    ChatCitation.php
    ChatMessage.php
    ChatSession.php
    LlmModel.php
    User.php
  Services/
    Ai/
      ChatResponder.php
      ChatResponse.php
      LlmCredentialResolver.php
      PerplexityClient.php
    ApiFreaks/
      ApiFreaksService.php
      CommoditySymbolsService.php
      CreditUsageService.php
      DomainSearchService.php
      DomainWhoisHistoryLookupService.php
      DomainWhoisLookupService.php
      HistoricalCommodityPricesService.php
      IpGeolocationService.php
      LiveCommodityPricesService.php
      SubdomainLookupService.php
    ApiKeys/
      ApiKeyBackupService.php
    Apify/
      GmapsScraperService.php
    ExternalApi/
      DownloaderService.php
    Freepik/
      ImageGenerationService.php
      ImageToPromptService.php
      ImprovePromptService.php
      VideoGenerationService.php
    ImageAi/
      FreeimageHostService.php
      Image2PromptService.php
    Internet/
      CurrencyExchangeRateService.php
      HolidayService.php
      ProxyValidateService.php
      WhoisService.php
    Search/
      AnimeQuoteSearchService.php
      FreepikImageSearchService.php
      GoogleImageSearchService.php
      TiktokVideoSearchService.php
      TokopediaSearchService.php
      UnsplashSearchService.php
      YoutubeChannelService.php
      YoutubeFinderService.php
      YoutubeSearchService.php
    Tools/
      CekResiService.php
      SendWhatsappService.php
      WilayahApiService.php
  Support/
    Registries/
    Settings/
      SystemSettings.php
config/
  ai.php
  tools.php
database/
  migrations/
  seeders/
docs/
  table.md
  table (1-5).md
  file.md
resources/
  views/
    tools/
      character-sheet.blade.php  (view-only, no Livewire component)
      split-cash.blade.php       (view-only, Alpine.js logic)
```

---

## Sidebar Navigasi

```text
Workspace
|-- Dashboard
|-- ChatBot
|-- Downloader
|   |-- Overview
|   `-- Download Youtube Short
`-- Custom Scripts

Modules
|-- Search
|   |-- Overview
|   |-- Tokopedia
|   |-- Unsplash
|   |-- Freepik Image (jika FREEPIK_ENABLED=true)
|   |-- Google Image
|   |-- TikTok Video
|   |-- Quotes Anime
|   |-- Youtube
|   |-- Youtube Finder
|   `-- Youtube Channel
|-- Tools
|   |-- Character Sheet
|   |-- Split Cash
|   |-- Calculator PVC
|   |-- Wall Meter
|   |-- Cek Resi
|   |-- Holiday
|   |-- Wilayah API
|   |-- Kirim WA / Send Whatsapp
|   `-- QR Code
|-- Image AI (jika FREEPIK_ENABLED=true)
|   |-- Generation Image
|   |-- Image2Prompt
|   `-- Improve Prompt
|-- Video AI (jika FREEPIK_ENABLED=true)
|   `-- Generation Video
|-- Internet
|   |-- Overview
|   |-- Kurs Mata Uang
|   |-- Proxy Validate
|   `-- Whois
|-- ApiFreaks Tools
|   |-- Overview
|   |-- Credit Usage
|   |-- Domain WHOIS Lookup
|   |-- WHOIS History
|   |-- Domain Search
|   |-- Subdomain Lookup
|   |-- Commodity Symbols
|   |-- Live Commodity Prices
|   |-- Historical Commodity Prices
|   `-- IP Geolocation
`-- Apify Scraper
    `-- GMaps 1.0

Operations
|-- Backup Data ApiKey
|-- Settings
|   |-- API Keys
|   |-- LLM Models
|   `-- General Settings
`-- Profile
```

---

Ringkasan sidebar saat ini:
- `Workspace`: `Dashboard`, `ChatBot`, `Downloader`, `Custom Scripts`
- `Modules`: `Search`, `Tools`, `Image AI`*, `Video AI`*, `Internet`, `ApiFreaks Tools`, `Apify Scraper`
- `Operations`: `Backup Data ApiKey`, `Settings` (`API Keys`, `LLM Models`, `General Settings`), `Profile`

Catatan:
- Integrasi Freepik dimatikan secara default dengan `FREEPIK_ENABLED=false`, sehingga menu `Freepik Image`, `Image AI`, dan `Video AI` tidak tampil di dashboard.
- Menu bertanda `*` hanya tampil jika `FREEPIK_ENABLED=true`.
- `Custom Scripts` (Workspace) dan `Execution History` (Operations) tercatat di sidebar namun belum terimplementasi (masih planned, lihat Roadmap).
- Submenu `Settings` mencakup `API Keys` (`ApiKeyManager`), `LLM Models` (`LlmModelManager`), dan `General Settings` (`GeneralSettings`).

Catatan modul Search:
- `Overview`
- `Tokopedia`
- `Unsplash`
- `Freepik Image` *(jika `FREEPIK_ENABLED=true`)*
- `Google Image`
- `TikTok Video`
- `Quotes Anime`
- `Youtube`
- `Youtube Finder`
- `Youtube Channel`

---

Catatan modul Tools:
- `Character Sheet`
- `Split Cash`
- `Calculator PVC`
- `Wall Meter`
- `Cek Resi`
- `Holiday`
- `Wilayah API`
- `Kirim WA / Send Whatsapp`
- `QR Code`

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
- `IP Geolocation`

---

Catatan modul Apify Scraper:
- `GMaps 1.0`

---

Catatan modul Video AI:
- `Generation Video` *(hanya tampil jika `FREEPIK_ENABLED=true`)*

Catatan modul Image AI:
- `Generation Image` *(hanya tampil jika `FREEPIK_ENABLED=true`)*
- `Image2Prompt` *(hanya tampil jika `FREEPIK_ENABLED=true`)*
- `Improve Prompt` *(hanya tampil jika `FREEPIK_ENABLED=true`)*

---

## Fitur ChatBot

Menu **Workspace -> ChatBot** menyediakan antarmuka chat AI multi-provider dengan dukungan percakapan berkelanjutan.

- Mendukung provider: `OpenAI`, `Gemini`, `Anthropic`, dan `Perplexity`.
- Setiap provider memiliki daftar model aktif yang dikelola di tabel `llm_models`.
- Sesi chat disimpan per user dengan title otomatis berdasarkan prompt pertama.
- Mendukung upload attachment (image: jpg, jpeg, png, webp; document: pdf, txt, md, csv, json, doc, docx) dengan batas 12 MB.
- Fitur **Web Search** menggabungkan hasil pencarian web dari Perplexity sebagai konteks tambahan sebelum dikirim ke LLM.
- Conversation history disimpan di tabel `chat_sessions` dan `chat_messages`, dengan limit 40 pesan terakhir per request.
- Citations dari respons LLM ditampilkan sebagai referensi sumber.
- Sesi bisa dibuat baru, dipilih ulang, atau dihapus beserta attachment-nya.

### Konfigurasi Environment

```env
OPENAI_API_KEY=
GEMINI_API_KEY=
ANTHROPIC_API_KEY=
PERPLEXITY_API_KEY=
```

### Model yang Didukung

Model LLM dikelola di tabel `llm_models` dengan field:
- `provider`: `openai`, `gemini`, `anthropic`, `perplexity`
- `name`: nama model (contoh: `gpt-4o`, `claude-sonnet-4-20250514`, `gemini-2.0-flash`)
- `label`: nama tampilan di UI
- `is_active`: status aktif/nonaktif
- `sort_order`: urutan tampil

---

## Fitur Downloader

Menu **Workspace -> Downloader** menyediakan satu workbench untuk mengunduh konten media (video) dari beberapa provider dalam satu antarmuka.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Semua endpoint memakai method `GET` dengan parameter query `link` dan `apikey`.
- Provider yang didukung:
  - **Instagram Downloader** — endpoint `/downloader/instagram`.
  - **TikTok Downloader** — endpoint `/downloader/tiktok`.
  - **Facebook Downloader** — endpoint `/downloader/facebook`.
  - **YouTube Shorts Downloader** — endpoint `/downloader/ytshorts`.
- Tombol download otomatis muncul sesuai key URL video yang tersedia di payload (mis. `hd`, `sd`, `dlink`, `play`, `download`).
- API key bersifat fleksibel: bisa memakai saved setting dari halaman Settings atau override manual langsung di form.
- Halaman **Download Youtube Short** (`external-api/ytshorts`) me-render workbench yang sama dengan provider otomatis ter-select ke `ytshorts`.

---

## Fitur Character Sheet

Menu **Modules -> Tools -> Character Sheet** menyediakan resource hub berisi kumpulan shortcut ke generator character sheet, storyboard, dan prompt tools eksternal.

- Halaman ini bersifat view-only (tidak menggunakan Livewire component), hanya mengumpulkan link ke tools eksternal.
- Tools yang tersedia:
  - **Character Sheet Generator** (ChatGPT) - Generator untuk menyusun character sheet dasar dari ide karakter.
  - **Image to JSON Prompt Engineer** (ChatGPT) - Konversi referensi visual menjadi prompt JSON.
  - **Story Board Generator** (ChatGPT) - Memecah alur visual menjadi urutan storyboard.
  - **Image to JSON Prompt** (Gemini) - Alternatif Gemini untuk prompt JSON dari gambar.
  - **Character Sheet Director** (Gemini) - Asisten untuk detail karakter, pose, dan ekspresi.
  - **Video & Image Prompt Generator** (Gemini) - Generator prompt untuk gambar dan video.
- Setiap tool ditampilkan sebagai card dengan provider badge, judul, deskripsi, dan tombol "Buka" yang mengarah ke URL eksternal.

---

## Fitur Split Cash

Menu **Modules -> Tools -> Split Cash** menyediakan kalkulator untuk membagi sejumlah uang tunai ke beberapa porsi secara acak dengan hasil yang pas dan bulat.

- Halaman ini bersifat view-only (tidak menggunakan Livewire component); seluruh logika berjalan di sisi klien memakai Alpine.js (`splitCash()` di `resources/js/app.js`).
- Input utama: total uang (Rp) dan jumlah porsi.
- Algoritma membagi total ke dalam porsi acak yang masing-masing bulat (tanpa sisa) dan jumlahnya persis sama dengan total.
- Hasil pembagian ditampilkan sebagai daftar porsi beserta nilainya.

---

## Fitur Calculator PVC

Menu **Modules -> Tools -> Calculator PVC** menyediakan workbench untuk menghitung estimasi kebutuhan lembar PVC berdasarkan dimensi bidang.

- Fitur utama:
  - Pilih preset ukuran produk PVC (Panel strip, PVC board).
  - Input dimensi bidang dalam satuan meter atau centimeter.
  - Input dimensi per lembar PVC (lebar, panjang, tebal).
  - Input harga per lembar (dalam Rupiah).
  - Cadangan potongan (waste percentage) opsional, default 10%.
- Hasil perhitungan:
  - Luas bidang total (m²).
  - Luas per lembar PVC (m²).
  - Kebutuhan dasar (jumlah minimum lembar).
  - Rekomendasi + cadangan (jumlah lembar setelah ditambah toleransi).
  - Estimasi biaya total (tanpa cadangan dan dengan cadangan).
- Preset harga mengacu pada kisaran harga pasar umum untuk panel strip 20x300 cm, 25x300 cm, dan PVC board 122x244 cm.
- Sidebar referensi menyediakan catatan cara membaca hasil dan harga preset pasar.

---

## Fitur Quotes Anime

Menu **Modules -> Search -> Quotes Anime** menyediakan workbench untuk menampilkan kutipan anime acak dari API publik.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `downloader_provider`.
- Base URL yang dipakai adalah `https://api.ferdev.my.id`.
- Endpoint yang dipanggil adalah `/random/anime-quotes`.
- Tidak memerlukan parameter query; setiap request mengembalikan satu kutipan acak.
- Hasil menampilkan: `quote`, `character` (nama karakter), `anime` (judul anime), dan `url` (sumber kutipan).
- Tersedia tombol refresh untuk mengambil kutipan baru.
- Raw JSON response tetap ditampilkan untuk inspeksi payload provider.

---

## Fitur Generation Image

Integrasi ini saat ini **dinonaktifkan secara default** dan hanya muncul jika `FREEPIK_ENABLED=true`.

Saat diaktifkan, menu **Modules -> Image AI -> Generation Image** menyediakan workbench untuk generate gambar memakai Freepik Image Generation dengan API key `freepik_provider`.

- Endpoint generate yang dipakai: Freepik Image Generation API.
- Endpoint history task: `GET` task history.
- Endpoint task by id: `GET /v1/ai/image/{task_id}`.
- Parameter utama:
  - `prompt`: deskripsi gambar yang akan dihasilkan.
  - `imageSize`: `square`, `square_hd`, `portrait_3_4`, `portrait_9_16`, `landscape_4_3`, `landscape_16_9`.
- Setelah submit, sistem menyimpan `task_id`, menampilkan status task, lalu melakukan polling berkala sampai task selesai atau gagal.
- Jika task selesai, hasil gambar ditampilkan di halaman dan bisa diunduh langsung.
- Riwayat task terbaru ditampilkan pada panel history (maksimal 10 task) dengan cache 30 detik.

---

## Fitur Image2Prompt

Integrasi ini saat ini **dinonaktifkan secara default** dan hanya muncul jika `FREEPIK_ENABLED=true`.

Saat diaktifkan, menu **Modules -> Image AI -> Image2Prompt** menyediakan workbench untuk menghasilkan deskripsi prompt dari sebuah gambar menggunakan Freepik API.

- Input: `image URL` atau `upload file gambar` (maks 5 MB, format jpg, jpeg, png, webp).
- Opsi `webhook URL` untuk callback notifikasi task selesai.
- Setelah submit, sistem menyimpan `task_id` dan melakukan polling status task.
- Hasil berupa array `generated` yang berisi deskripsi prompt berbasis gambar yang dianalisis.
- Status task: `CREATED`, `PROCESSING`, `COMPLETED`, `FAILED`, `ERROR`.
- Tersedia tombol "Clear Result" untuk mereset hasil sebelumnya.
- **Upload File**: File gambar diupload ke Freeimage.host dulu oleh `FreeimageHostService` (API key `freeimage_host`) untuk mendapatkan URL publik, lalu URL dikirim ke API Image2Prompt. Mendukung format base64 dengan validasi ukuran maksimum 5120 KB.

---

## Fitur Improve Prompt

Integrasi ini saat ini **dinonaktifkan secara default** dan hanya muncul jika `FREEPIK_ENABLED=true`.

Saat diaktifkan, menu **Modules -> Image AI -> Improve Prompt** menyediakan workbench untuk meningkatkan kualitas prompt menggunakan Freepik API.

- Input: `prompt` (maks 2500 karakter), `type` (`image` atau `video`), `language` (kode bahasa 2 karakter, contoh: `en`, `id`).
- Opsi `webhook URL` untuk callback notifikasi task selesai.
- Setelah submit, sistem menyimpan `task_id` dan melakukan polling status task.
- Hasil berupa array `generated` yang berisi prompt-prompt yang sudah ditingkatkan kualitasnya.
- Status task: `CREATED`, `PROCESSING`, `COMPLETED`, `FAILED`, `ERROR`.
- Tersedia tombol "Clear Result" untuk mereset hasil sebelumnya.

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

## Fitur Search Freepik Image

Integrasi ini saat ini **dinonaktifkan secara default** dan hanya muncul jika `FREEPIK_ENABLED=true`.

Saat diaktifkan, menu **Modules -> Search -> Freepik Image** menyediakan workbench untuk mencari stock image dan template Freepik lewat **Magnific API**.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `freepik_provider`.
- Base URL yang dipakai adalah `https://api.magnific.com`.
- Header auth yang dipakai adalah `x-magnific-api-key`.
- Endpoint yang dipakai adalah `GET /v1/resources`, `GET /v1/resources/{id}`, dan `GET /v1/resources/{id}/download/{format}`.
- Parameter query utama adalah `term`, dengan opsi `limit` dan `order`, contoh `white t-shirt mockup`.
- Hasil pencarian dirender dalam card dan tabel dengan informasi `thumbnail`, `title`, `type`, `orientation`, `author`, `downloads`, `likes`, `published_at`, dan daftar format yang tersedia.
- Saat satu resource dipilih, UI memuat detail resource, hasil download per format, dan raw JSON untuk inspeksi payload provider.

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

## Fitur Holiday

Menu **Modules -> Tools -> Holiday** menyediakan workbench untuk melihat jadwal libur nasional Indonesia, cuti bersama, dan hari kebesaran melalui API.co.id.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `apicoid_provider`.
- Base URL yang dipakai adalah `https://use.api.co.id`.
- Semua request memakai header autentikasi `x-api-co-id`.
- Endpoint yang dipakai: `GET /holidays/indonesia` (daftar per tahun) dan `GET /holidays/indonesia/check/date` (cek satu tanggal).
- Tiga fitur utama:
  - **Daftar libur per tahun** — input tahun (2000–2100), menampilkan daftar libur dengan nama, tanggal, tipe, status cuti bersama, dan hari kebesaran.
  - **Cek tanggal tertentu** — input tanggal format `YYYY-MM-DD`, menampilkan status libur, hari, dan akhir pekan.
  - **Libur mendatang** — dihitung dari field `is_upcoming` / `days_until` pada endpoint `/holidays/indonesia` tahun berjalan (endpoint `/holidays/indonesia/upcoming` tidak tersedia di API; dikerjakan lewat filter client-side).
- Hasil setiap request menampilkan ringkasan, daftar item, dan raw JSON response untuk inspeksi.
- Implementasi:
  - `app/Services/Internet/HolidayService.php` — service class dengan wrapper `listHolidays()`, `checkDate()`, dan `upcomingHolidays()`.
  - `app/Livewire/Tools/Holiday.php` — Livewire component.
  - `resources/views/livewire/tools/holiday.blade.php` — UI blade.

---

## Fitur Wilayah API

Menu **Modules -> Tools -> Wilayah API** menyediakan workbench untuk menelusuri wilayah administrasi Indonesia melalui API.co.id.

- Menggunakan API key tersimpan di tabel `api_keys` dengan identifier `apicoid_provider`.
- Base URL yang dipakai adalah `https://use.api.co.id`.
- Semua request memakai header autentikasi `x-api-co-id`.
- Endpoint yang dipakai (hierarki bertahap):
  - `GET /regional/indonesia/provinces` — daftar provinsi (filter nama opsional).
  - `GET /regional/indonesia/provinces/{code}/regencies` — daftar kabupaten/kota dalam provinsi.
  - `GET /regional/indonesia/regencies/{code}/districts` — daftar kecamatan dalam kabupaten/kota.
  - `GET /regional/indonesia/districts/{code}/villages` — daftar desa/kelurahan dalam kecamatan (memuat `postal_codes` dan `is_courier_support`).
- Alur penggunaan: pilih provinsi → kabupaten/kota → kecamatan → desa/kelurahan secara bertahap (setiap level memunculkan level berikutnya).
- Setiap level mendukung filter nama untuk mempersempit pencarian.
- Tiap desa/kelurahan menampilkan kode pos dan status `is_courier_support` (apakah wilayah sudah didukung untuk cek ongkos kirim).
- Hasil setiap request menampilkan ringkasan, daftar item, dan raw JSON response untuk inspeksi.
- Catatan: endpoint premium (search dan postal-code) tidak diimplementasikan pada modul ini.
- Implementasi:
  - `app/Services/Tools/WilayahApiService.php` — service class dengan wrapper `listProvinces()`, `listRegencies()`, `listDistricts()`, dan `listVillages()`.
  - `app/Livewire/Tools/WilayahApi.php` — Livewire component.
  - `resources/views/livewire/tools/wilayah-api.blade.php` — UI blade.

---

## Fitur Wall Meter

Menu **Modules -> Tools -> Wall Meter** menyediakan workbench untuk menghitung tinggi dinding menggunakan metode trigonometri sudut elevasi.

- Input utama memakai slider untuk `Jarak (d)`, `Sudut elevasi (alpha)`, dan `Tinggi alat (h1)`.
- Rumus yang dipakai: `h2 = d x tan(alpha)` lalu `H = h1 + h2`.
- Sistem juga menghitung `Panjang garis bidik (L) = d / cos(alpha)`.
- Hasil dihitung real-time dan menampilkan langkah perhitungan numerik agar mudah diverifikasi.
- Validasi rentang input mengikuti batas aman perhitungan sudut dan jarak.

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

## Fitur QR Code

Menu **Modules -> Tools -> QR Code** menyediakan workbench untuk membuat QR Code langsung dari dashboard tanpa layanan eksternal.

- Menerima input teks bebas (URL, teks, kontak) maksimal 5000 karakter; wajib diisi.
- Generate file QR Code dalam format **PNG** dan **JPG** sekaligus, masing-masing dengan nama file unik berbasis UUID.
- Menampilkan **preview langsung** dari hasil generate menggunakan data URI base64 (tanpa file publik).
- Menyediakan tombol **Download PNG** dan **Download JPG** lewat route `qr-code.download` dengan validasi nama file (UUID-like, ekstensi `png`/`jpg`) dan header MIME otomatis.
- Pembersihan file temporary:
  - Otomatis saat generate baru (service menghapus file yang lebih tua dari 24 jam).
  - Manual lewat tombol **Hapus Temporary** di halaman.
  - Terpusat via command `php artisan cleanup:temporary-uploads`.
- File hasil disimpan di disk `local` pada direktori `qr-codes-tmp` (`storage/app/qr-codes-tmp`) dan kedaluwarsa setelah 24 jam.
- Implementasi utama:
  - `app/Services/QrCodeTemporaryFileService.php` — generate, delete, deleteMany, cleanupExpiredFiles, validasi path, dan mimeType (memakai `BaconQrCode\Writer` + `GDLibRenderer`, ukuran 800px, margin 4).
  - `resources/views/pages/qr-code/generate.blade.php` — Volt single-file component (input, `generate()`, `clearTemporaryFiles()`, preview & download).
  - `routes/web.php` — route halaman `qr-code.generate` (Volt) dan route download `qr-code.download`.

---

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

### IP Geolocation Lookup API

- Route: `apifreaks-tools.ip-geolocation-lookup`
- Endpoint: `/v2.0/geolocation/lookup`
- Method: `GET`
- Parameter utama: `ip`, `lang` (`en`/`id`), `fields` (contoh: `location`), `include` (contoh: `security,hostnameFallbackLive`)
- Menampilkan tabel per-section: Location, Country Metadata, Network, Currency, ASN, Company, dan Time Zone (termasuk DST start/end jika tersedia).
- Mendukung parameter `lang` untuk response dalam Bahasa Inggris (`en`) atau Bahasa Indonesia (`id`).

---

## Fitur Generation Video

Integrasi ini saat ini **dinonaktifkan secara default** dan hanya muncul jika `FREEPIK_ENABLED=true`.

Saat diaktifkan, menu **Modules -> Video AI -> Generation Video** menyediakan workbench untuk generate video memakai Freepik Kling v3 Standard dengan API key `freepik_provider`.

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

## Fitur General Settings

Menu **Operations -> Settings -> General Settings** menyediakan konfigurasi global aplikasi yang berlaku untuk seluruh modul API eksternal.

- Mengatur `request_timeout_seconds` (batas waktu request, default 30 detik).
- Mengatur `request_retry_times` (jumlah retry, default 1) dan `request_retry_sleep_ms` (delay antar retry, default 500ms).
- Mengatur `queue_connection` untuk eksekusi task berat (`sync` atau `database`).
- Nilai disimpan via `App\Support\Settings\SystemSettings` (singleton) ke tabel `app_settings`.
- Perubahan langsung memengaruhi perilaku service-service yang memakai pengaturan dari `config('tools.*')`.

---

## Fitur LLM Model Manager

Menu **Operations -> Settings -> LLM Models** menyediakan pengelolaan daftar model LLM per provider yang dipakai oleh ChatBot.

- Mengelola entitas pada tabel `llm_models` (provider, name, label, is_active, sort_order).
- Provider yang didukung: `openai`, `gemini`, `anthropic`, `perplexity`.
- Hanya model dengan `is_active=true` yang muncul di pilihan model pada UI ChatBot.
- Mendukung toggle aktif/nonaktif dan pengaturan urutan tampil.

---

## Struktur Database

### `api_keys` *(aktif)*
Penyimpanan tersentralisasi untuk semua API key yang dibutuhkan modul eksternal. Nilai API key dienkripsi di database (`Crypt::encryptString` via accessor/mutator `ApiKey`).

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| name | string | Identifier unik (contoh: `downloader_provider`) |
| label | string | Nama tampilan di UI |
| description | text | Deskripsi kegunaan (opsional) |
| value | text | Nilai API key (encrypted) |
| is_active | boolean | Status aktif/nonaktif key |

### `app_settings` *(aktif)*
Konfigurasi global aplikasi (Timeout, Retry, Queue Mode). Backed by `App\Models\AppSetting`.

| Kolom | Tipe |
|---|---|
| key | string |
| value | text |

### `llm_models` *(aktif)*
Daftar model LLM per provider yang dikelola lewat `LlmModelManager`.

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint | Primary key |
| provider | string | `openai`, `gemini`, `anthropic`, `perplexity` |
| name | string | Nama model (contoh: `gpt-4o`) |
| label | string | Nama tampilan di UI |
| is_active | boolean | Status aktif/nonaktif |
| sort_order | integer | Urutan tampil |

### `chat_sessions` & `chat_messages` *(aktif)*
Menyimpan percakapan ChatBot per user, beserta `chat_attachments` dan `chat_citations`.

### `api_modules` *(planned)*
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

### `custom_scripts` *(planned)*
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

### `execution_histories` *(planned)*
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

# Full first-time setup (install + migrate + build)
composer run setup

# Atau jalankan langkah manual:
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

### Konfigurasi `.env`

Sesuaikan variabel berikut:

```env
APP_NAME="Laravel Tools"
APP_URL=http://localhost

DB_CONNECTION=sqlite
# atau sesuaikan untuk MySQL/PostgreSQL

# API Key untuk layanan eksternal dikelola lewat UI (Settings -> API Keys),
# BUKAN via .env. Pengecualian hanya untuk ChatBot AI provider keys di bawah.

# ChatBot AI provider keys (satu-satunya key yang berasal dari .env)
OPENAI_API_KEY=
GEMINI_API_KEY=
ANTHROPIC_API_KEY=
PERPLEXITY_API_KEY=

# Freepik integration gate (default false)
FREEPIK_ENABLED=false
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
# Jalankan test suite (clears config, then runs PHPUnit dengan in-memory SQLite)
composer run test

# Atau langsung dengan PHP artisan
php artisan test

# Run a single test file / filter
php artisan test --compact tests/Feature/Search/TokopediaSearchTest.php
php artisan test --compact --filter=test_can_search
```

Tests memakai PHPUnit (bukan Pest) dengan in-memory SQLite, array cache, dan sync queue. Test fitur berada di `tests/Feature/<Domain>/` mengikuti struktur Livewire component.

---

## Tahapan Pengembangan (Roadmap)

### Phase 1 - Foundation *(selesai)*
- [x] Inisialisasi Laravel 13
- [x] Install Breeze + Livewire + Volt
- [x] Konfigurasi Tailwind CSS
- [x] Buat layout dashboard + sidebar
- [x] Auth flow (login, logout, proteksi route)

### Phase 2 - External API Module
- [ ] Config registry dari folder `docs`
- [ ] Halaman daftar kategori API
- [ ] Halaman daftar tools per kategori
- [ ] Form parameter dinamis + execute endpoint
- [ ] Tampil hasil response (JSON, image, link)
- [x] Modul Downloader: Instagram, TikTok, Facebook, YouTube Shorts (provider switcher + auto download button)
- [x] Modul Search: Tokopedia (card view + table view + raw JSON)
- [x] Modul Search: Unsplash (gallery card + table URL + raw JSON)
- [x] Modul Search: Freepik Image (card + table + detail resource + download per format, saat ini dimatikan default)
- [x] Modul Search: Google Image (preview image + table URL + raw JSON)
- [x] Modul Search: TikTok Video (preview video + table URL + raw JSON)
- [x] Modul Search: Quotes Anime (kutipan anime acak + raw JSON)
- [x] Modul Search: Youtube (card view + table view + raw JSON)
- [x] Modul Search: Youtube Finder (table view + pagination + YouTube Data API v3)
- [x] Modul Search: Youtube Channel (profil channel + daftar upload + pencarian dalam channel)
- [x] Modul Tools: Character Sheet (resource hub view-only)
- [x] Modul Tools: Split Cash
- [x] Modul Tools: Calculator PVC (estimasi kebutuhan lembar PVC + kalkulasi biaya)
- [x] Modul Tools: Wall Meter (perhitungan tinggi dinding dengan slider trigonometri)
- [x] Modul Tools: Cek Resi (tracking paket + timeline vertikal)
- [x] Modul Tools: Kirim WA / Send Whatsapp
- [x] Modul Tools: QR Code (generator PNG/JPG + preview + download + cleanup temporary)
- [x] Modul Internet: Kurs Mata Uang (API.co.id Exchange Rate)
- [x] Modul Internet / Tools: Holiday (API.co.id Holiday Calendar — daftar libur, cek tanggal, libur mendatang)
- [x] Modul Tools: Wilayah API (API.co.id Indonesia Regional — hierarki provinsi, kabupaten/kota, kecamatan, desa/kelurahan)
- [x] Modul Internet: Proxy Validate (filter, bulk select, validate, export, progress)
- [x] Modul Internet: Whois (lookup domain + raw WHOIS record)
- [x] Modul ApiFreaks Tools: Credit Usage, Domain WHOIS Lookup/History, Domain Search, Subdomain Lookup, Commodity Symbols, Live/Historical Commodity Prices, IP Geolocation
- [x] Modul Apify Scraper: GMaps 1.0 (data bisnis Google Maps + export CSV/XLSX/PDF)
- [x] Modul Image AI: Generation Image (Freepik text-to-image + task polling + history)
- [x] Modul Image AI: Image2Prompt (gambar ke deskripsi prompt + task polling)
- [x] Modul Image AI: Improve Prompt (peningkatan kualitas prompt + task polling)
- [x] Modul Video AI: Generation Video (Freepik Kling v3 text-to-video + task polling + history)
- [x] Modul Workspace: ChatBot (multi-provider AI chat dengan conversation persistence, file upload, web search)

### Phase 3 - Custom Script Module
- [ ] Registry custom script
- [ ] Script executor aman (whitelist-based)
- [ ] Log eksekusi script

### Phase 4 - Settings & Security
- [x] Settings management (API key terpusat, timeout, queue mode, LLM model manager)
- [x] Backup dan restore API key dari file backup
- [x] AI ChatBot dengan multi-provider (OpenAI, Gemini, Anthropic, Perplexity)
- [ ] Role & Permission (spatie/laravel-permission)
- [ ] Audit log (spatie/laravel-activitylog)
- [ ] Execution History (logging eksekusi API/script)

### Phase 5 - Reliability
- [ ] Queue untuk task berat (downloader, OCR, dll.)
- [ ] Retry & timeout configuration
- [ ] Health check provider API
- [x] Test automation (PHPUnit)

---

## Catatan Keamanan

- **Custom Script Executor**: Hindari menjalankan shell command bebas dari input user. Prioritaskan `Artisan command` atau `PHP class handler`. Jika shell command diperlukan, gunakan **whitelist** command yang diizinkan.
- **API Key**: Semua input `value` dari halaman manajemen API Keys akan dienkripsi dari bawaan sistem sebelum masuk ke database (`Crypt::encryptString`) untuk faktor keamanan.
- **API Key Internet / Exchange Rate, Holiday & Wilayah**: Modul Kurs Mata Uang, Holiday, dan Wilayah API mengambil key dari `api_keys` dengan identifier `apicoid_provider` dan mengirimkannya melalui header `x-api-co-id`.
- **API Key Ferdev Provider**: Modul Downloader (Instagram, TikTok, Facebook, YouTube Shorts), Search -> Tokopedia, Search -> Unsplash, Search -> Google Image, Search -> TikTok Video, Search -> Quotes Anime, Search -> Youtube, Tools -> Cek Resi, dan Internet -> Whois mengambil key dari `api_keys` dengan identifier `downloader_provider` dan mengirimkannya sebagai parameter query `apikey`.
- **API Key Freepik / Magnific Provider**: Dipakai hanya jika `FREEPIK_ENABLED=true`. Saat aktif, modul Search -> Freepik Image, Image AI -> Generation Image, Image AI -> Image2Prompt, Image AI -> Improve Prompt, dan Video AI -> Generation Video mengambil key dari `api_keys` dengan identifier `freepik_provider`.
- **API Key YouTube Data API**: Modul Search -> Youtube Finder dan Search -> Youtube Channel mengambil key dari `api_keys` dengan identifier `youtubeapi_provider` untuk request ke YouTube Data API v3.
- **API Key Apify**: Modul Apify Scraper -> GMaps 1.0 mengambil key dari `api_keys` dengan identifier `apify_provider` untuk request ke Apify actor API.
- **API Key ChatBot Providers**: Modul Workspace -> ChatBot menggunakan API key dari environment `OPENAI_API_KEY`, `GEMINI_API_KEY`, `ANTHROPIC_API_KEY`, dan `PERPLEXITY_API_KEY`. API key Perplexity dipakai juga sebagai web search grounding untuk provider lain selain Perplexity itu sendiri.
- **API Key FreeimageHost**: Modul Image AI -> Image2Prompt menggunakan `FreeimageHostService` yang mengambil key dari `api_keys` dengan identifier `freeimage_host`. Key ini dipakai untuk upload gambar ke Freeimage.host API v1 (`POST /api/1/upload`) guna mendapatkan URL publik sebelum dikirim ke endpoint Image2Prompt.
- **Attachment ChatBot**: File attachment dari ChatBot disimpan di disk `local` pada direktori `chatbot-attachments`. Akses file dibatasi per sesi chat milik masing-masing user.
- **Backup API Key**: File backup API key berisi secret asli agar dapat direstore. Simpan file backup di lokasi aman dan jangan commit file dari `storage/app/private/api-key-backups`.
- **Permission**: Batasi akses menu tertentu menggunakan role-based access control.

---

## Lisensi

Project ini open-source dan tersedia di bawah [MIT License](https://opensource.org/licenses/MIT).
By ERIE PUTRANTO
