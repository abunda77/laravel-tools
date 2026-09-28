# CEKWAKTU2 — World Clock & Time Converter

Dokumentasi untuk file [`cekwaktu2.php`](./cekwaktu2.php), sebuah halaman PHP mandiri untuk menampilkan waktu server, waktu Jakarta, jam dunia, dan konversi waktu dari Jakarta ke timezone lain.

## 1. Ringkasan

`cekwaktu2.php` menggabungkan beberapa teknologi dalam satu file:

- PHP untuk mengambil waktu awal dan membangun daftar timezone.
- HTML untuk struktur halaman.
- CSS internal untuk tampilan, dark mode, card, tabel, dan responsive layout.
- JavaScript untuk memperbarui jam secara realtime dan melakukan konversi waktu tanpa reload halaman.
- Intl DateTime API browser untuk memformat waktu berdasarkan timezone IANA.

File ini tidak membutuhkan database, framework, API eksternal, atau dependency tambahan.

## 2. Fitur

### 2.1 Waktu server

Menampilkan:

- Nama timezone default server.
- Jam server.
- Tanggal server.

Timezone server diperoleh dari:

```php
$server_timezone = date_default_timezone_get();
$server_time = new DateTime('now');
```

> Pastikan timezone PHP/server sudah dikonfigurasi dengan benar. File ini membaca konfigurasi timezone yang sedang aktif, bukan menetapkan timezone server secara paksa.

### 2.2 Waktu Jakarta

Menampilkan waktu berdasarkan timezone IANA `Asia/Jakarta`:

```php
$jakarta_timezone = new DateTimeZone('Asia/Jakarta');
$jakarta_time = new DateTime('now', $jakarta_timezone);
```

Waktu Jakarta ditampilkan bersama timezone dan offset `GMT+7`.

### 2.3 World Clock

Menampilkan jam dan tanggal realtime untuk **25 lokasi** yang dikelompokkan berdasarkan region:

| Region | Lokasi |
|---|---|
| Asia | Jakarta, Tokyo, Singapore, Shanghai, Seoul, Dubai, Bangkok, Kolkata |
| Europe | London, Paris, Berlin, Moscow, Rome |
| Americas | New York, Los Angeles, Chicago, Toronto, Mexico City, Sao Paulo |
| Australia & Pacific | Sydney, Perth, Auckland |
| Africa | Cairo, Johannesburg, Lagos |

Setiap entri timezone memiliki format:

```php
[timezone, nama_negara, emoji_bendera, offset_tampilan]
```

Contoh:

```php
["Asia/Tokyo", "Japan", "🇯🇵", "GMT+9"]
```

Timezone diurutkan berdasarkan offset aktual menggunakan `DateTimeZone::getOffset()`. Karena menggunakan timezone IANA, perhitungan dapat mengikuti aturan timezone yang berlaku, termasuk perubahan daylight saving time pada wilayah yang mendukungnya.

### 2.4 Konverter waktu

Konverter menerima waktu Jakarta dalam format 24 jam dan mengubahnya ke:

- Timezone server.
- Semua timezone yang tersedia pada daftar world clock.

Alur konversi:

1. Pengguna memilih waktu Jakarta.
2. Pengguna memilih timezone tujuan.
3. JavaScript membaca input sebagai waktu `Asia/Jakarta`, bukan timezone browser.
4. Waktu diubah menjadi timestamp.
5. Timestamp diformat menggunakan timezone tujuan.
6. Hasil ditampilkan tanpa reload halaman.

### 2.5 Dark mode

Dark mode menggunakan CSS variables dan class `.dark-mode`.

Preferensi pengguna disimpan pada `localStorage` dengan key:

```text
dark-mode
```

Nilai yang digunakan:

- `enabled` — dark mode aktif.
- `disabled` — dark mode nonaktif.

Saat halaman dibuka kembali, pilihan tema sebelumnya dipulihkan secara otomatis.

### 2.6 Responsive layout

Pada layar dengan lebar maksimal `768px`:

- Header berubah menjadi layout vertikal.
- Tabel dapat digeser secara horizontal.
- Form konverter berubah menjadi satu kolom.
- Toggle tema dipindahkan ke bawah judul.

## 3. Struktur file

`cekwaktu2.php` adalah file tunggal dengan bagian-bagian berikut:

```text
cekwaktu2.php
├── Inisialisasi waktu server dan Jakarta
├── Daftar timezone
├── Pengurutan timezone
├── Fungsi getCurrentTime()
├── Fungsi getCurrentDate()
├── HTML document
│   ├── CSS internal
│   ├── Header dan dark mode toggle
│   ├── Tabel Current Time
│   ├── Form Time Converter
│   ├── Tabel World Clock
│   └── Footer
└── JavaScript
    ├── Dark mode
    ├── Format waktu timezone-aware
    ├── Update clock server dan Jakarta
    ├── Update world clock
    ├── Konversi waktu Jakarta
    └── Interval update setiap detik
```

## 4. Fungsi PHP

### `getCurrentTime($timezone)`

Mengambil waktu saat ini dari timezone tertentu.

```php
function getCurrentTime($timezone) {
    $dt = new DateTime('now', new DateTimeZone($timezone));
    return $dt->format('H:i:s');
}
```

**Parameter:**

- `$timezone` — nama timezone IANA, misalnya `Asia/Tokyo`.

**Return:**

- String waktu dengan format `HH:MM:SS`.

### `getCurrentDate($timezone)`

Mengambil tanggal saat ini dari timezone tertentu.

```php
function getCurrentDate($timezone) {
    $dt = new DateTime('now', new DateTimeZone($timezone));
    return $dt->format('d M Y');
}
```

**Parameter:**

- `$timezone` — nama timezone IANA.

**Return:**

- String tanggal dengan format `DD Mon YYYY`.

## 5. Fungsi JavaScript utama

### `currentPageTime(timestamp)`

Menghasilkan waktu berdasarkan timestamp PHP dengan menambahkan selisih waktu sejak halaman dimuat. Cara ini menjaga clock server dan Jakarta tetap berjalan berdasarkan waktu awal dari server.

### `formatDateTime(date, timezone)`

Memformat objek `Date` untuk timezone tertentu menggunakan:

```javascript
Intl.DateTimeFormat('en-GB', { timeZone: timezone })
```

Fungsi ini menghasilkan object:

```javascript
{
    time: 'HH:MM:SS',
    date: 'DD Mon YYYY'
}
```

### `renderClock(clock, date, timezone)`

Mengisi elemen jam dan tanggal pada halaman berdasarkan hasil `formatDateTime()`.

### `updateFixedClocks()`

Memperbarui:

- Jam server.
- Tanggal server.
- Jam Jakarta.
- Tanggal Jakarta.

### `updateWorldClock()`

Memperbarui seluruh elemen `.tz-clock` dan `.tz-date` menggunakan timezone yang disimpan pada atribut HTML:

```html
<div class="clock tz-clock" data-timezone="Asia/Tokyo">
```

Jika timezone gagal diproses browser, elemen tersebut ditampilkan sebagai `Unavailable` tanpa menghentikan pembaruan timezone lainnya.

### `getTimezoneOffset(date, timezone)`

Menghitung offset aktual suatu timezone menggunakan `Intl.DateTimeFormat`. Offset ini dipakai untuk mengubah waktu input Jakarta menjadi timestamp yang benar.

### `jakartaTimeToDate(time)`

Mengubah input seperti `14:30` menjadi objek `Date` yang merepresentasikan pukul 14:30 di `Asia/Jakarta`, terlepas dari timezone lokal browser pengguna.

## 6. Siklus pembaruan realtime

Saat halaman selesai dimuat:

```javascript
updateFixedClocks();
updateWorldClock();
```

Kemudian kedua kelompok clock diperbarui setiap satu detik:

```javascript
setInterval(() => {
    updateFixedClocks();
    updateWorldClock();
}, 1000);
```

Waktu world clock dibuat ulang dengan `new Date()` pada setiap siklus. Ini memastikan clock tidak berhenti pada timestamp saat halaman pertama kali dibuka.

## 7. Persyaratan sistem

- PHP 7.4 atau lebih baru.
- Ekstensi PHP `date`.
- Web server seperti Apache, Nginx, Laragon, XAMPP, atau PHP built-in server.
- Browser modern dengan dukungan:
  - `Intl.DateTimeFormat`.
  - `Intl.DateTimeFormat.prototype.formatToParts`.
  - `localStorage`.
  - JavaScript ES6.

## 8. Cara menjalankan

### Menggunakan PHP built-in server

Dari folder project:

```bash
php -S localhost:8000
```

Buka:

```text
http://localhost:8000/cekwaktu2.php
```

### Menggunakan Laragon

1. Letakkan project pada folder web Laragon, misalnya `D:/php/cek_produkmastah`.
2. Jalankan Apache melalui Laragon.
3. Buka file melalui URL virtual host atau URL localhost yang sesuai.

Contoh:

```text
http://localhost/cek_produkmastah/cekwaktu2.php
```

## 9. Menambah lokasi timezone

Tambahkan item baru ke array `$timezones`:

```php
["Asia/Manila", "Philippines", "🇵🇭", "GMT+8"],
```

Format field:

| Index | Isi |
|---:|---|
| `0` | Nama timezone IANA |
| `1` | Nama negara atau wilayah |
| `2` | Emoji bendera |
| `3` | Offset untuk tampilan |

Contoh timezone IANA yang valid:

```text
Asia/Manila
Europe/Madrid
America/Vancouver
Africa/Nairobi
```

Gunakan nama timezone IANA, bukan hanya offset seperti `GMT+8`, agar perhitungan waktu lebih akurat.

## 10. Catatan konfigurasi timezone

Field offset pada array, seperti `GMT+9` atau `GMT-5`, hanya digunakan sebagai label tampilan pada dropdown dan tabel.

Perhitungan aktual menggunakan nama timezone IANA pada field pertama. Karena itu, label offset perlu diperbarui jika aturan timezone suatu wilayah berubah.

Contoh:

```php
["America/New_York", "USA (Eastern)", "🇺🇸", "GMT-5"]
```

Perhitungan aktual menggunakan `America/New_York`, bukan string `GMT-5`.

## 11. Validasi dan penanganan error

Konverter mengabaikan proses jika input waktu kosong.

Jika browser gagal memproses timezone tujuan:

- Hasil konversi diubah menjadi `Conversion failed. Please try again.`.
- Container hasil tetap ditampilkan agar pengguna mengetahui proses gagal.

Jika satu timezone pada world clock tidak valid:

- Jam timezone tersebut ditampilkan sebagai `Unavailable`.
- Clock timezone lain tetap berjalan.

## 12. Pemeriksaan sintaks

Gunakan perintah berikut untuk memeriksa sintaks PHP:

```bash
php -l cekwaktu2.php
```

Output yang diharapkan:

```text
No syntax errors detected in cekwaktu2.php
```

## 13. Catatan pengembangan

### Bahasa tampilan

Sebagian besar teks antarmuka saat ini menggunakan bahasa Inggris, misalnya:

- `Current Time`
- `Time Converter`
- `Convert`
- `World Clock`
- `Local Time`

Jika diperlukan, teks tersebut dapat diterjemahkan tanpa mengubah logika PHP atau JavaScript.

### Format tanggal

Format tanggal yang digunakan JavaScript adalah format pendek berbahasa Inggris, contohnya:

```text
25 Dec 2025
```

Format dapat diubah dengan mengganti locale pada `Intl.DateTimeFormat`, misalnya dari `en-GB` menjadi `id-ID`.

### Akurasi waktu

Clock pada halaman berjalan berdasarkan timestamp saat halaman dirender dan waktu browser setelahnya. Jika tab browser ditangguhkan oleh sistem operasi, browser dapat menunda eksekusi timer. Saat tab aktif kembali, waktu akan dihitung ulang berdasarkan `Date.now()`.

## 14. Batasan

- Konverter hanya menerima waktu Jakarta sebagai sumber.
- Konverter hanya menampilkan jam dan menit pada hasil, tanpa tanggal.
- Daftar timezone harus diperbarui manual di dalam file.
- File belum menggunakan backend API untuk sinkronisasi waktu eksternal.
- Tidak ada autentikasi atau penyimpanan data pengguna di server.

## 15. Ringkasan alur aplikasi

```text
Request halaman
    ↓
PHP membaca timezone server dan waktu saat ini
    ↓
PHP membangun daftar timezone dan tabel HTML
    ↓
Browser menerima timestamp server/Jakarta
    ↓
JavaScript memperbarui clock setiap 1 detik
    ↓
Pengguna memasukkan waktu Jakarta
    ↓
JavaScript mengubahnya ke timestamp Asia/Jakarta
    ↓
Timestamp diformat dalam timezone tujuan
    ↓
Hasil konversi ditampilkan tanpa reload halaman
```
