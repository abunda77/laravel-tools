# Dokumentasi API Bookmark

> Panduan REST API untuk modul **Bookmark** pada **Laravel Tools** — CRUD bookmark, pratinjau metadata URL, serta manajemen kategori bookmark. Seluruh endpoint membutuhkan token autentikasi.

**Status**: Tersedia (sudah diimplementasikan)
**Versi API**: `v1`
**Autentikasi**: Laravel **Sanctum** Personal Access Token (wajib pada semua endpoint)
**Dokumen ini ditujukan untuk**: developer frontend / mobile / Laravel project lain yang ingin berintegrasi.

---

## Daftar Isi

1. [Ringkasan](#1-ringkasan)
2. [Autentikasi API](#2-autentikasi-api)
3. [Endpoint Bookmark](#3-endpoint-bookmark)
4. [Endpoint Kategori Bookmark](#4-endpoint-kategori-bookmark)
5. [Format Response](#5-format-response)
6. [Kode Status & Error Handling](#6-kode-status--error-handling)
7. [Contoh Integrasi](#7-contoh-integrasi)
8. [Pedoman Penggunaan dari Frontend](#8-pedoman-penggunaan-dari-frontend)
9. [Catatan Keamanan & Limitasi](#9-catatan-keamanan--limitasi)

---

## 1. Ringkasan

Modul bookmark API menyediakan **CRUD lengkap** untuk bookmark dan kategori, plus endpoint khusus **pratinjau metadata** (Open Graph / Twitter Card) sebelum bookmark disimpan. Data selalu discope per `user_id` dari token — setiap user hanya melihat/mengubah bookmark miliknya.

Fitur yang tersedia:

- `GET /api/v1/bookmarks` — daftar bookmark (paginasi + pencarian + filter + sorting).
- `POST /api/v1/bookmarks` — simpan bookmark baru (metadata URL diambil otomatis).
- `GET /api/v1/bookmarks/{bookmark}` — detail satu bookmark.
- `PUT|PATCH /api/v1/bookmarks/{bookmark}` — ubah bookmark.
- `DELETE /api/v1/bookmarks/{bookmark}` — hapus bookmark.
- `POST /api/v1/bookmarks/preview` — pratinjau metadata URL (tanpa menyimpan).
- `GET /api/v1/bookmark-categories` — daftar kategori (dengan jumlah bookmark).
- `POST /api/v1/bookmark-categories` — buat kategori baru.
- `PUT|PATCH /api/v1/bookmark-categories/{category}` — ubah kategori.
- `DELETE /api/v1/bookmark-categories/{category}` — hapus kategori.

### Komponen utama

| Komponen | Lokasi |
|---|---|
| Controller API | `app/Http/Controllers/Api/V1/BookmarkController.php` |
| Route API | `routes/api.php` |
| Service | `app/Services/Internet/BookmarkService.php`, `BookmarkPreviewService.php` |
| Model | `app/Models/Bookmark.php`, `app/Models/BookmarkCategory.php` |

---

## 2. Autentikasi API

Semua endpoint di dokumen ini **wajib** membawa header berikut:

```
Authorization: Bearer <personal_access_token>
Accept: application/json
```

Token didapat dari `POST /api/v1/auth/login` (lihat [API-AUTH.md](API-AUTH.md)). Tanpa token, response `401 Unauthenticated`.

---

## 3. Endpoint Bookmark

Base URL:

```
https://your-domain.com/api/v1
```

### 3.1 `GET /api/v1/bookmarks`

Daftar bookmark milik user terautentikasi, terpaginasi.

**Query params** (semua opsional):

| Param | Tipe | Default | Keterangan |
|---|---|---|---|
| `search` | string | — | Cari pada `title`, `description`, `url`, `domain` (LIKE) |
| `category_id` | integer / `null` | — | Filter per kategori. Kirim `null` (atau string `"null"`) untuk bookmark **tanpa kategori** |
| `sort_by` | string | `created_at` | Salah satu: `created_at`, `title`, `domain`, `visited_count` |
| `sort_dir` | string | `desc` | `asc` atau `desc` |
| `per_page` | integer | `20` | Jumlah per halaman, maksimum `100` |
| `page` | integer | `1` | Halaman |

**Response `200`** — response ini **langsung** berupa struktur paginasi Laravel (bukan dibungkus `data`):

```json
{
    "current_page": 1,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "category_id": 2,
            "url": "https://laravel.com",
            "title": "Laravel - The PHP Framework",
            "description": "Laravel is a web application framework with expressive, elegant syntax.",
            "image_url": "https://laravel.com/img/og-image.png",
            "image_path": null,
            "favicon_url": "https://laravel.com/favicon.ico",
            "domain": "laravel.com",
            "metadata": {
                "title": "Laravel - The PHP Framework",
                "description": "Laravel is a web application framework with expressive, elegant syntax.",
                "image_url": "https://laravel.com/img/og-image.png",
                "favicon_url": "https://laravel.com/favicon.ico",
                "site_name": "Laravel",
                "url": "https://laravel.com",
                "domain": "laravel.com",
                "fetched_at": "2026-08-19T10:00:00.000000Z"
            },
            "is_active": true,
            "visited_count": 0,
            "last_visited_at": null,
            "created_at": "2026-08-19T10:00:00.000000Z",
            "updated_at": "2026-08-19T10:00:00.000000Z",
            "category": {
                "id": 2,
                "user_id": 1,
                "name": "Framework",
                "slug": "framework",
                "color": "#3b82f6",
                "sort_order": 0,
                "created_at": "2026-08-15T09:00:00.000000Z",
                "updated_at": "2026-08-15T09:00:00.000000Z"
            }
        }
    ],
    "first_page_url": "https://your-domain.com/api/v1/bookmarks?page=1",
    "from": 1,
    "last_page": 3,
    "last_page_url": "https://your-domain.com/api/v1/bookmarks?page=3",
    "links": [...],
    "next_page_url": "https://your-domain.com/api/v1/bookmarks?page=2",
    "path": "https://your-domain.com/api/v1/bookmarks",
    "per_page": 20,
    "prev_page_url": null,
    "to": 20,
    "total": 47
}
```

> Relasi `category` dimuat (`with('category')`). `category` bernilai `null` bila bookmark tanpa kategori.

### 3.2 `POST /api/v1/bookmarks`

Simpan bookmark baru. Sistem otomatis menormalkan URL dan **mengambil metadata** (title/description/image/favicon/domain) dari halaman target. Jika `title` / `description` dikirim, nilai tersebut **menimpa** hasil fetch.

**Body**:

```json
{
    "url": "https://laravel.com",
    "category_id": 2,
    "title": "Laravel",
    "description": "Deskripsi kustom"
}
```

**Aturan validasi**:

| Field | Rule |
|---|---|
| `url` | `required`, `string`, `max:2048` |
| `category_id` | `nullable`, `integer`, harus milik user (wajib `exists` di `bookmark_categories`) |
| `title` | `nullable`, `string`, `max:500` |
| `description` | `nullable`, `string`, `max:1000` |

**Response `201`**:

```json
{
    "data": {
        "id": 1,
        "user_id": 1,
        "category_id": 2,
        "url": "https://laravel.com",
        "title": "Laravel",
        "description": "Deskripsi kustom",
        "image_url": "https://laravel.com/img/og-image.png",
        "image_path": null,
        "favicon_url": "https://laravel.com/favicon.ico",
        "domain": "laravel.com",
        "metadata": { "...": "..." },
        "is_active": true,
        "visited_count": 0,
        "last_visited_at": null,
        "created_at": "2026-08-19T10:00:00.000000Z",
        "updated_at": "2026-08-19T10:00:00.000000Z",
        "category": { "...": "..." }
    }
}
```

**Error `409`** — URL sudah ada di bookmark (unik per user):

```json
{
    "message": "URL sudah ada di bookmark."
}
```

**Error `422`** — validasi gagal atau URL tidak dapat diambil metadatanya:

```json
{
    "message": "Tidak dapat terhubung ke URL.",
    "errors": {
        "url": [
            "The url field is required."
        ]
    }
}
```

> Pesan metadata seperti *"Tidak dapat terhubung ke URL."*, *"Gagal mengambil data dari URL."*, *"URL harus menggunakan skema http atau https."*, atau *"URL tidak valid."* dikembalikan dengan status `422` pada body `message` (bukan di `errors`).

### 3.3 `GET /api/v1/bookmarks/{bookmark}`

Detail satu bookmark beserta relasi kategori.

**Response `200`**:

```json
{
    "data": {
        "id": 1,
        "user_id": 1,
        "category_id": 2,
        "url": "https://laravel.com",
        "title": "Laravel - The PHP Framework",
        "description": "...",
        "image_url": "...",
        "image_path": null,
        "favicon_url": "...",
        "domain": "laravel.com",
        "metadata": { "...": "..." },
        "is_active": true,
        "visited_count": 3,
        "last_visited_at": "2026-08-18T20:00:00.000000Z",
        "created_at": "2026-08-19T10:00:00.000000Z",
        "updated_at": "2026-08-19T10:00:00.000000Z",
        "category": { "...": "..." }
    }
}
```

**Error `404`** — bookmark tidak ada atau bukan milik user:

```json
{
    "message": "Bookmark tidak ditemukan."
}
```

> Bookmark milik user lain dikembalikan `404` (bukan `403`) agar keberadaannya tidak bocor.

### 3.4 `PUT|PATCH /api/v1/bookmarks/{bookmark}`

Ubah bookmark. Semua field opsional (partial update). Jika `url` diubah, sistem menormalkan URL, memastikan belum dipakai bookmark lain, lalu **mengambil ulang metadata** — nilai `title`/`description` yang dikirim tetap menimpa hasil fetch.

**Body** (semua opsional):

```json
{
    "url": "https://laravel.com/docs",
    "category_id": 3,
    "title": "Laravel Docs",
    "description": "Dokumentasi resmi Laravel"
}
```

**Aturan validasi**:

| Field | Rule |
|---|---|
| `url` | `nullable`, `string`, `max:2048` |
| `category_id` | `nullable`, `integer`, harus milik user |
| `title` | `nullable`, `string`, `max:500` |
| `description` | `nullable`, `string`, `max:1000` |

**Response `200`**:

```json
{
    "data": {
        "id": 1,
        "...": "...",
        "url": "https://laravel.com/docs",
        "title": "Laravel Docs",
        "updated_at": "2026-08-19T11:00:00.000000Z"
    }
}
```

**Error `409`** — URL sudah dipakai bookmark lain:

```json
{
    "message": "URL sudah ada di bookmark."
}
```

**Error `404`** — bookmark tidak ditemukan / bukan milik user:

```json
{
    "message": "Bookmark tidak ditemukan."
}
```

### 3.5 `DELETE /api/v1/bookmarks/{bookmark}`

Hapus bookmark (soft delete).

**Response `204`** — tanpa body:

```
Status: 204 No Content
```

**Error `404`** — bookmark tidak ditemukan / bukan milik user:

```json
{
    "message": "Bookmark tidak ditemukan."
}
```

### 3.6 `POST /api/v1/bookmarks/preview`

Ambil metadata URL **tanpa menyimpan** ke database. Berguna untuk menampilkan pratinjau sebelum user menyimpan.

**Body**:

```json
{
    "url": "https://laravel.com"
}
```

**Aturan validasi**:

| Field | Rule |
|---|---|
| `url` | `required`, `string`, `max:2048` |

**Response `200`** — `already_saved` menunjukkan apakah URL tersebut sudah pernah disimpan oleh user (agar frontend bisa menonaktifkan tombol simpan):

```json
{
    "data": {
        "url": "https://laravel.com",
        "domain": "laravel.com",
        "fetched_at": "2026-08-19T10:00:00.000000Z",
        "title": "Laravel - The PHP Framework",
        "description": "Laravel is a web application framework with expressive, elegant syntax.",
        "image_url": "https://laravel.com/img/og-image.png",
        "favicon_url": "https://laravel.com/favicon.ico",
        "site_name": "Laravel",
        "already_saved": false
    }
}
```

**Error `422`** — URL tidak valid / tidak dapat diambil:

```json
{
    "message": "Gagal mengambil data dari URL."
}
```

---

## 4. Endpoint Kategori Bookmark

### 4.1 `GET /api/v1/bookmark-categories`

Daftar kategori milik user, diurutkan `sort_order` lalu `name`, dilengkapi jumlah bookmark (`bookmarks_count`).

**Response `200`**:

```json
{
    "data": [
        {
            "id": 2,
            "user_id": 1,
            "name": "Framework",
            "slug": "framework",
            "color": "#3b82f6",
            "sort_order": 0,
            "created_at": "2026-08-15T09:00:00.000000Z",
            "updated_at": "2026-08-15T09:00:00.000000Z",
            "bookmarks_count": 12
        },
        {
            "id": 1,
            "user_id": 1,
            "name": "Tanpa Kategori",
            "slug": "tanpa-kategori",
            "color": null,
            "sort_order": 1,
            "created_at": "2026-08-15T09:00:00.000000Z",
            "updated_at": "2026-08-15T09:00:00.000000Z",
            "bookmarks_count": 0
        }
    ]
}
```

### 4.2 `POST /api/v1/bookmark-categories`

Buat kategori baru. `slug` dibuat otomatis dari `name` bila tidak dikirim.

**Body**:

```json
{
    "name": "Framework",
    "color": "#3b82f6"
}
```

**Aturan validasi**:

| Field | Rule |
|---|---|
| `name` | `required`, `string`, `max:100` |
| `color` | `nullable`, `string`, format `regex:/^#[0-9A-Fa-f]{6}$/` (contoh `#3b82f6`) |

**Response `201`**:

```json
{
    "data": {
        "id": 2,
        "user_id": 1,
        "name": "Framework",
        "slug": "framework",
        "color": "#3b82f6",
        "sort_order": 0,
        "created_at": "2026-08-19T10:00:00.000000Z",
        "updated_at": "2026-08-19T10:00:00.000000Z"
    }
}
```

### 4.3 `PUT|PATCH /api/v1/bookmark-categories/{category}`

Ubah kategori (partial update).

**Body** (semua opsional):

```json
{
    "name": "PHP Framework",
    "color": "#10b981",
    "sort_order": 1
}
```

**Aturan validasi**:

| Field | Rule |
|---|---|
| `name` | `nullable`, `string`, `max:100` |
| `color` | `nullable`, `string`, format `#RRGGBB` |
| `sort_order` | `nullable`, `integer`, `min:0` |

**Response `200`**:

```json
{
    "data": {
        "id": 2,
        "user_id": 1,
        "name": "PHP Framework",
        "slug": "php-framework",
        "color": "#10b981",
        "sort_order": 1,
        "created_at": "2026-08-19T10:00:00.000000Z",
        "updated_at": "2026-08-19T11:00:00.000000Z"
    }
}
```

> `slug` tidak otomatis diperbarui saat `name` diubah — hanya diisi saat kategori pertama kali dibuat.

**Error `404`** — kategori tidak ditemukan / bukan milik user:

```json
{
    "message": "Kategori tidak ditemukan."
}
```

### 4.4 `DELETE /api/v1/bookmark-categories/{category}`

Hapus kategori. Seluruh bookmark pada kategori tersebut otomatis di-set menjadi `category_id = null` (tidak ikut terhapus).

**Response `204`** — tanpa body:

```
Status: 204 No Content
```

**Error `404`** — kategori tidak ditemukan / bukan milik user:

```json
{
    "message": "Kategori tidak ditemukan."
}
```

---

## 5. Format Response

Mengikuti pola yang sama dengan API lain di project ini:

- **Sukses** (non-paginasi): `{ "data": ... }`.
- **List** `GET /bookmarks`: langsung berupa **struktur paginasi Laravel** (`current_page`, `data`, `total`, dll) — perhatikan ini berbeda dari `GET /bookmark-categories` yang dibungkus `{ "data": [...] }`.
- **Hapus**: `204 No Content` tanpa body.
- **Error**: `{ "message": "...", "errors": { field: ["..."] } }` — `errors` muncul saat validasi gagal; `message` tunggal untuk konflik/not-found.

---

## 6. Kode Status & Error Handling

| Skenario | Status | Pesan contoh |
|---|---|---|
| Daftar / detail / kategori | 200 | — |
| Simpan / buat kategori berhasil | 201 | — |
| Hapus berhasil | 204 | — |
| URL sudah ada di bookmark (store/update) | 409 | `URL sudah ada di bookmark.` |
| URL tidak valid / gagal fetch metadata (store & preview) | 422 | `Tidak dapat terhubung ke URL.` |
| Validasi field gagal | 422 | `The given data was invalid.` |
| Bookmark tidak ditemukan / bukan milik user | 404 | `Bookmark tidak ditemukan.` |
| Kategori tidak ditemukan / bukan milik user | 404 | `Kategori tidak ditemukan.` |
| Token salah / tidak dikirim | 401 | `Unauthenticated.` |
| Rate limit terlampaui | 429 | `Too Many Attempts.` |
| Error server | 500 | — |

**Contoh error tanpa token (`401`)**:

```json
{
    "message": "Unauthenticated."
}
```

**Contoh error validasi (`422`)**:

```json
{
    "message": "The given data was invalid.",
    "errors": {
        "url": [
            "The url field is required."
        ]
    }
}
```

---

## 7. Contoh Integrasi

### 7.1 cURL

```bash
# Login -> dapatkan token
curl -X POST \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user@example.com","password":"rahasia123","device_name":"android"}' \
  "https://your-domain.com/api/v1/auth/login"

# Daftar bookmark (halaman 1, 10 per halaman, cari "laravel")
curl -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  "https://your-domain.com/api/v1/bookmarks?search=laravel&per_page=10&page=1"

# Pratinjau metadata URL
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"url":"https://laravel.com"}' \
  "https://your-domain.com/api/v1/bookmarks/preview"

# Simpan bookmark
curl -X POST \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"url":"https://laravel.com","category_id":2}' \
  "https://your-domain.com/api/v1/bookmarks"

# Ubah bookmark id 1
curl -X PATCH \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"Laravel Docs"}' \
  "https://your-domain.com/api/v1/bookmarks/1"

# Hapus bookmark id 1
curl -X DELETE \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  "https://your-domain.com/api/v1/bookmarks/1"

# Daftar kategori
curl -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  "https://your-domain.com/api/v1/bookmark-categories"
```

### 7.2 JavaScript / Fetch (Web)

```js
const API_BASE = "https://your-domain.com/api/v1";

async function api(path, { method = "GET", token, body } = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    method,
    headers: {
      "Accept": "application/json",
      ...(body ? { "Content-Type": "application/json" } : {}),
      ...(token ? { "Authorization": `Bearer ${token}` } : {}),
    },
    body: body ? JSON.stringify(body) : undefined,
  });
  if (!res.ok) throw new Error((await res.json()).message || "Request gagal");
  return res.status === 204 ? null : res.json();
}

async function listBookmarks(token, { search = "", per_page = 20 } = {}) {
  const qs = new URLSearchParams({ search, per_page });
  return api(`/bookmarks?${qs}`, { token });
}

async function addBookmark(token, url, categoryId) {
  return api(`/bookmarks`, { method: "POST", token, body: { url, category_id: categoryId } });
}

async function previewUrl(token, url) {
  return api(`/bookmarks/preview`, { method: "POST", token, body: { url } });
}

async function listCategories(token) {
  return api(`/bookmark-categories`, { token });
}
```

### 7.3 Dart / Flutter

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class BookmarkApi {
  final String baseUrl = "https://your-domain.com/api/v1";
  final String token;

  BookmarkApi(this.token);

  Future<List<dynamic>> listBookmarks({String search = ''}) async {
    final res = await http.get(
      Uri.parse('$baseUrl/bookmarks?search=$search'),
      headers: {'Accept': 'application/json', 'Authorization': 'Bearer $token'},
    );
    if (res.statusCode != 200) throw Exception('Gagal memuat bookmark');
    return (jsonDecode(res.body)['data'] as List).cast<dynamic>();
  }

  Future<Map<String, dynamic>> addBookmark(String url, int? categoryId) async {
    final res = await http.post(
      Uri.parse('$baseUrl/bookmarks'),
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: jsonEncode({'url': url, 'category_id': categoryId}),
    );
    final body = jsonDecode(res.body);
    if (res.statusCode != 201) throw Exception(body['message'] ?? 'Gagal simpan');
    return body['data'];
  }
}
```

### 7.4 PHP / Laravel (Http client)

```php
use Illuminate\Support\Facades\Http;

$token = '...'; // token dari POST /auth/login

$bookmarks = Http::withToken($token)
    ->acceptJson()
    ->get('https://your-domain.com/api/v1/bookmarks', [
        'search' => 'laravel',
        'per_page' => 10,
    ]);

$preview = Http::withToken($token)
    ->acceptJson()
    ->post('https://your-domain.com/api/v1/bookmarks/preview', [
        'url' => 'https://laravel.com',
    ]);

$created = Http::withToken($token)
    ->acceptJson()
    ->post('https://your-domain.com/api/v1/bookmarks', [
        'url' => 'https://laravel.com',
        'category_id' => 2,
    ]);

Http::withToken($token)->acceptJson()
    ->delete('https://your-domain.com/api/v1/bookmarks/1');
```

---

## 8. Pedoman Penggunaan dari Frontend

1. **Sertakan header Bearer** — setiap request wajib membawa `Authorization: Bearer <token>` dan `Accept: application/json`.
2. **Gunakan `preview` sebelum simpan** — tampilkan pratinjau metadata dari `POST /bookmarks/preview`; manfaatkan `already_saved` untuk menonaktifkan tombol simpan dan menampilkan pesan "sudah ada".
3. **Perhatikan format paginasi** — `GET /bookmarks` mengembalikan struktur paginasi langsung, sedangkan `GET /bookmark-categories` dibungkus `{ "data": [...] }`.
4. **Filter tanpa kategori** — kirim `category_id=null` (string `"null"` juga diterima) pada list bookmark untuk menampilkan bookmark yang tidak memiliki kategori.
5. **Handle `409`** — saat store/update mengembalikan `409`, tampilkan `message` ("URL sudah ada di bookmark.") sebagai informasi, mis. tombol beralih ke "Buka bookmark".
6. **Handle `404` sebagai tidak ada** — `404` berarti resource tidak ditemukan *atau* bukan milik user; jangan menganggapnya `403`.
7. **Hapus kategori** — hapus kategori **tidak** menghapus bookmark-nya; bookmark otomatis menjadi tanpa kategori.

---

## 9. Catatan Keamanan & Limitasi

### Keamanan

- **Isolasi per user** — seluruh query discope `user_id` dari token; bookmark/kategori user lain tak pernah terlihat.
- **`404` untuk resource milik user lain** — bukan `403`, agar keberadaan resource tidak bocor.
- **Validasi `category_id` milik user** — `Rule::exists` memastikan kategori yang dipilih memang milik user pemegang token.
- **URL dinormalkan & divalidasi** — hanya skema `http`/`https` yang diterima.

### Hal yang sengaja disederhanakan

- **Tanpa rate limiting khusus** — bergantung pada throttling bawaan aplikasi.
- **Tanpa soft-delete recovery di API** — `Bookmark` memakai `SoftDeletes`, namun belum ada endpoint untuk restore.
- **`slug` hanya diisi saat create** — mengubah `name` kategori tidak memperbarui `slug`.
- **`is_active` / `visited_count` / `last_visited_at` tidak dapat diubah via API** — field-field ini dikelola oleh proses lain (mis. fitur kunjungan).

### Limitasi (natural)

- **Fetch metadata bergantung pada server target** — bila target lambat/terblokir, store tetap berhasil menyimpan URL (title/description hasil fetch bisa `null`), tetapi endpoint `preview` dan store akan gagal dengan `422` bila koneksi/HTTP target bermasalah (timeout 10 detik, retry 2x, maks. 5 redirect).
- **Unik per user** — URL yang sama boleh ada pada user berbeda, tetapi tidak boleh dua kali pada user yang sama.

---

## Referensi Terkait

- Dokumen autentikasi API: `API-AUTH.md` (cara mendapatkan token)
- Modul dashboard: menu **Internet → Bookmark** (implementasi Livewire yang memakai logika service yang sama)
- Changelog project: `CHANGELOG.md`

---

© ERIE PUTRANTO — Laravel Tools