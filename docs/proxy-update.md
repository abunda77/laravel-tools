# Proxy Fetcher & Validator

Aplikasi desktop Python (berbasis CustomTkinter) untuk melakukan *fetch*, filter, dan validasi proxy secara langsung dari sumber raw di GitHub.

## Fitur Utama

- **Fetch Data Proxy**: Mengambil daftar proxy terkini (HTTP, SOCKS4, SOCKS5) dari repositori publik.
- **Advanced Filtering & Sorting**: Menyaring proxy berdasarkan *Country*, *Protocol*, *Anonymity*, atau pencarian kata kunci (*host*, IP, dll). Klik heading tabel untuk mengurutkan data (ascending/descending).
- **Concurrent Validation**: Memvalidasi proxy yang dipilih atau seluruh hasil filter secara paralel menggunakan *thread pool*, menjaga antarmuka tetap responsif.
- **SOCKS Support**: Mendukung pemeriksaan proksi HTTP, SOCKS4, dan SOCKS5 sekaligus dapat mengekstraksi alamat IP yang terdeteksi dari endpoint penguji.
- **Copy Actions**: Kemudahan menyalin IP:Port dari proxy, baik yang sedang dipilih, hasil filter, maupun informasi penuh beserta status dan *response time*.
- **Cooperative Cancel**: Menghentikan *task* validasi massal kapan saja dengan aman tanpa mengakibatkan aplikasi *freeze* atau *crash*.

## Syarat Sistem

- Python 3.10 atau lebih baru.
- Sistem operasi Windows, Linux, atau macOS.

## Cara Instalasi & Menjalankan

```bash
# 1. Buat virtual environment (opsional namun direkomendasikan)
python -m venv .venv

# 2. Aktivasi virtual environment
# Windows:
.venv\Scripts\activate
# Linux/macOS:
# source .venv/bin/activate

# 3. Install semua requirement (CustomTkinter, requests[socks])
pip install -r requirements.txt

# 4. Jalankan aplikasi
python main.py
```

## Arsitektur & Rencana
Dokumen mengenai arsitektur internal aplikasi (PEMISAHAN model, ui, proxy fetcher, proxy validator) dan perencanaan lengkap bisa dibaca pada [PLANNING.md](PLANNING.md).

## Catatan Penting

- Proses validasi proxy sangat bergantung pada kondisi proxy itu sendiri serta stabilitas layanan API endpoint pihak ketiga (seperti `httpbin.org` atau `ipify.org`).
- Banyak proxy gratis yang tersedia secara publik memiliki latensi yang tinggi atau sering *timeout*. Kondisi ini sepenuhnya normal dan aplikasi dirancang khusus untuk menangani respons yang lambat.
