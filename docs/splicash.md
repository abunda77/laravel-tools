# 💳 Split Cash

> Aplikasi web sederhana untuk membagi uang tunai secara acak. Cukup buka di browser, tidak perlu instalasi.

![Preview](https://img.shields.io/badge/UI-Modern%20Dark%20Elegance-5C6BC0?style=flat-square)
![Stack](https://img.shields.io/badge/Stack-HTML%20%7C%20CSS%20%7C%20JS-brightgreen?style=flat-square)

---

## 📋 Fitur Utama

| Fitur | Penjelasan |
|-------|-----------|
| 🔢 Format otomatis | Saat mengetik, angka otomatis ditampilkan dengan titik pemisah ribuan (contoh: `1.000.000`). |
| 🎲 Bagi acak | Uang dibagi secara random, dengan selisih maksimum 25% antar penerima. |
| 💵 Bulat ribuan | Setiap bagian dibulatkan ke kelipatan **Rp 1.000** agar mudah dihitung. |
| ✅ Total tepat | Total semua bagian **tetap sama** dengan jumlah uang awal. |
| 📋 Salin nominal | Klik ikon salin untuk menyalin nilai ke clipboard. |
| 🔄 Pilih jumlah bagian | Dapat membagi untuk **2 sampai 6 orang**. |

---

## 🗂️ Struktur Project

```
split-cash/
├── index.html   # Tampilan utama
├── style.css    # Styling (CSS)
├── app.js       # Logika pembagian uang (JavaScript)
└── README.md    # Dokumentasi ini
```

---

## 🚀 Cara Menggunakan

1. Buka file `index.html` di browser (Chrome, Firefox, Edge, dll.).
2. Masukkan **total uang** yang ingin dibagi.
3. Pilih **jumlah orang** (antara 2‑6).
4. Klik tombol **Proses Pembagian**.
5. Hasil akan muncul. Klik ikon 📋 di samping tiap nilai untuk menyalin nominal.

---

## ⚙️ Cara Kerja Pembagian

1. Membuat nilai acak untuk tiap orang (dari -25% sampai +25% dari bagian rata‑rata).
2. Mengonversi nilai ke rupiah dan membulatkannya ke bawah ke kelipatan 1.000.
3. Menghitung selisih antara total awal dan total hasil pembulatan.
4. Menambahkan selisih secara acak ke beberapa orang.
5. Mengacak urutan hasil akhir agar tidak dapat diprediksi.

**Contoh:** Membagi Rp 100.000 menjadi 4 orang:
```
Orang 1 → Rp 22.000
Orang 2 → Rp 28.000
Orang 3 → Rp 24.000
Orang 4 → Rp 26.000
---------------------
Total    = Rp 100.000 ✓
```

---

## 🛠️ Teknologi yang Digunakan

- **HTML5** – Struktur halaman.
- **CSS3** – Styling dan animasi.
- **JavaScript (ES6+)** – Logika pembagian tanpa library tambahan.
- **Google Fonts** – Font *Plus Jakarta Sans*.

---

## 📄 Lisensi

MIT License – bebas dipakai, dimodifikasi, dan dibagikan.
