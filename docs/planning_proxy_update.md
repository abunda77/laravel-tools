# Proxy Fetcher & Validator GUI - Planning Document

## Tujuan

Membangun aplikasi desktop Python untuk:

- mengambil daftar proxy dari sumber GitHub publik,
- mem-parse dan memfilter data proxy,
- memvalidasi proxy secara paralel,
- menampilkan hasil validasi dengan status yang mudah dibaca,
- menyalin hasil terpilih ke clipboard.

Fokus utama aplikasi adalah akurasi validasi dan responsivitas UI, bukan sekadar menampilkan daftar proxy.

## Review Singkat Dokumen Sebelumnya

Beberapa hal pada draft awal perlu diperbaiki:

- Encoding file rusak sehingga markdown sulit dibaca.
- Rencana terlalu berpusat pada GUI, sementara core logic fetch, parse, filter, dan validate belum dipisah jelas.
- Validasi semua proxy lewat `requests` saja tidak cukup karena SOCKS butuh dukungan `requests[socks]` atau `PySocks`.
- Endpoint uji sebaiknya bisa dikonfigurasi dan tidak hanya `http://httpbin.org/ip`.
- Perlu model data dan state yang jelas agar filter, status validasi, dan hasil copy konsisten.

Dokumen ini mengganti draft sebelumnya dengan versi yang lebih siap diimplementasikan.

## Scope Aplikasi

### In Scope

- Fetch daftar proxy dari satu atau beberapa endpoint raw GitHub.
- Parse format baris:

```text
IP:PORT | PROTOCOL | COUNTRY_CODE | ANONYMITY_LEVEL
```

- Filter berdasarkan:
  - negara,
  - protocol,
  - anonymity,
  - kata kunci IP/host.
- Validasi proxy HTTP, SOCKS4, dan SOCKS5.
- Menampilkan:
  - status valid / invalid / unchecked,
  - response time,
  - optional external IP hasil pengecekan.
- Copy data yang dipilih.
- Menjaga GUI tetap responsif saat fetch dan validasi.

### Out of Scope untuk v1

- Menyimpan ke database.
- Auto-refresh berkala di background.
- Benchmark multi-endpoint yang kompleks.
- Ekspor ke Excel/PDF.
- Proxy chaining atau rotating session.

## Sumber Data

Sumber utama: repository `anutmagang/Free-HighQuality-Proxy-Socks`.

Contoh endpoint yang akan didukung sebagai konfigurasi:

- `results/all.txt`
- `results/http.txt`
- `results/socks5.txt`
- `results/countries/<CODE>.txt`

Catatan:

- Repo sumber tidak menyediakan file `socks4.txt` terpisah. Proxy SOCKS4 hanya tersedia melalui `all.txt`.
- URL final sebaiknya disimpan dalam konstanta terpusat, bukan hardcoded di banyak tempat.
- Parser harus toleran terhadap baris kosong, spasi ekstra, dan format rusak.

## Kebutuhan Fungsional

### 1. Fetch Proxy Data

- User memilih sumber data dari dropdown.
- Aplikasi mengambil data melalui thread worker agar UI tidak freeze.
- Hasil fetch diparse menjadi model proxy internal.
- Jika fetch gagal, tampilkan error yang jelas tanpa crash.

Output minimal per item:

- `host`
- `port`
- `protocol`
- `country`
- `anonymity`
- `status`
- `response_time_ms`
- `last_checked_at`
- `detected_ip`
- `error_message`

### 2. Filtering

- Filter bekerja terhadap dataset hasil fetch, bukan mem-fetch ulang.
- Perubahan filter memperbarui tabel secara instan.
- Search box mendukung pencarian berdasarkan `host`, `host:port`, atau substring.
- Country list dibangun secara dinamis: extract unique country codes dari data yang sudah di-fetch, sort alphabetically, dan tambahkan opsi "All" di posisi pertama.

### 3. Validation

- User bisa:
  - validate selected,
  - validate all filtered.
- Validasi berjalan paralel dengan batas worker yang bisa dikonfigurasi.
- Timeout harus bisa diatur, default konservatif 5-8 detik.
- Hasil validasi memperbarui status per baris tanpa menunggu semua task selesai.

### 4. Copy Actions

- Copy selected IP:PORT.
- Copy all filtered IP:PORT.
- Copy full info:

```text
IP:PORT | PROTOCOL | COUNTRY | ANONYMITY | STATUS | RESPONSE_MS
```

### 5. Feedback UI

- Progress bar saat fetch dan validate.
- Status bar berisi:
  - total fetched,
  - total visible,
  - valid,
  - invalid,
  - unchecked,
  - active task state.

## Strategi Validasi Proxy

Ini area paling penting dari aplikasi.

### Prinsip Validasi

Proxy dianggap valid jika:

- koneksi ke endpoint uji berhasil,
- respons diterima dalam timeout,
- dan, bila memungkinkan, external IP berhasil dibaca dari respons.

### Endpoint Uji

Gunakan daftar endpoint cadangan, misalnya:

- `https://httpbin.org/ip`
- `https://api.ipify.org?format=json`
- `https://ifconfig.me/all.json`

Strategi:

- Coba endpoint pertama.
- Jika gagal karena endpoint issue, boleh fallback ke endpoint berikutnya.
- Jangan menandai proxy invalid hanya karena satu endpoint publik sedang down, jika kegagalannya jelas berasal dari target endpoint.

### Dukungan Protocol

- HTTP/HTTPS proxy: via `requests`.
- SOCKS4/SOCKS5 proxy: via `requests[socks]` atau `PySocks`.

Catatan implementasi:

- Untuk `requests`, format proxy biasanya:
  - `http://host:port`
  - `socks4://host:port`
  - `socks5://host:port`

### Output Hasil Validasi

Minimal hasil validasi satu proxy:

```python
{
    "status": "valid" | "invalid",
    "response_time_ms": 1234,
    "detected_ip": "1.2.3.4" | None,
    "error_message": "timeout" | "connection refused" | None,
}
```

### Risiko Teknis

- Banyak proxy publik lambat atau mati; invalid ratio tinggi adalah normal.
- Thread terlalu banyak dapat membuat aplikasi terlihat hang atau diblokir OS/network stack.
- Tidak semua proxy anonim walaupun sumber menandai `Elite` atau `Anonymous`; hasil real bisa berbeda.
- Beberapa proxy hanya mendukung target tertentu.

Karena itu:

- default `max_workers` jangan langsung 50 tanpa alasan,
- mulai dari 20-30 lebih aman,
- sediakan pengaturan untuk mengubah nilai ini.

## Arsitektur Aplikasi

Pisahkan aplikasi menjadi 4 layer:

### 1. Model Layer

Menentukan struktur data proxy dan hasil validasinya.

Disarankan memakai `dataclasses`.

Contoh:

```python
@dataclass
class ProxyRecord:
    host: str
    port: int
    protocol: str
    country: str
    anonymity: str
    status: str = "unchecked"
    response_time_ms: int | None = None
    detected_ip: str | None = None
    error_message: str | None = None
    last_checked_at: datetime | None = None
```

### 2. Service Layer

Berisi logic non-UI.

Untuk v1 yang ringkas, service layer cukup terdiri dari:

- `proxy_fetcher.py`
- `proxy_validator.py`

Tanggung jawab modul:

- `proxy_fetcher.py`
  - menyimpan source mapping / URL source,
  - fetch raw text,
  - parse proxy list,
  - helper filtering sederhana bila belum dipisah ke modul sendiri.
- `proxy_validator.py`
  - validasi satu proxy,
  - validasi batch,
  - progress callback,
  - cooperative cancellation.

Jika kompleksitas bertambah, source config dan filtering dapat dipisah kemudian ke:

- `proxy_sources.py`
- `filtering.py`

Layer ini harus bisa dites tanpa GUI.

### 3. UI Layer

Berisi widget, event binding, tabel, status bar, dialog, progress.

### 4. App Controller / State Layer

Menghubungkan UI dengan service:

- menyimpan `all_proxies`,
- menghitung `filtered_proxies`,
- mengatur task fetch/validate,
- memastikan update UI aman dari thread background.

## Tech Stack

| Komponen | Pilihan |
|---|---|
| Bahasa | Python 3.10+ |
| GUI | CustomTkinter + `ttk.Treeview` |
| HTTP Client | `requests` |
| SOCKS Support | `requests[socks]` |
| Concurrency | `concurrent.futures.ThreadPoolExecutor` |
| Data Model | `dataclasses` |
| Testing | `pytest` |

## Struktur File (v1)

```text
d:\python\proxy-checked\
|-- main.py                 # Entry point
|-- app.py                  # Controller / state management
|-- models.py               # ProxyRecord dataclass
|-- proxy_fetcher.py        # Source mapping, fetch, parse, filtering sederhana
|-- proxy_validator.py      # Validasi proxy (threaded) + cooperative cancel
|-- requirements.txt
|-- README.md
`-- PLANNING.md
```

Model dipisah dari GUI agar service layer bisa dites secara independen. Folder `ui/`, `tests/`, `proxy_sources.py`, dan `filtering.py` bisa ditambahkan di versi berikutnya jika kompleksitas bertambah.

## Desain UI

Layout v1:

- Toolbar atas:
  - source dropdown,
  - country filter,
  - protocol filter,
  - anonymity filter,
  - search box.
- Action row:
  - fetch,
  - validate selected,
  - validate all filtered,
  - stop current task.
- Main table:
  - select,
  - host:port,
  - protocol,
  - country,
  - anonymity,
  - status,
  - response time,
  - detected IP,
  - last checked.
- Footer:
  - progress bar,
  - status summary,
  - copy actions.

Catatan:

- Jangan terlalu banyak tombol duplikat.
- `Copy Selected`, `Copy Filtered`, dan `Copy Full Info` sudah cukup untuk v1.
- Fitur checkbox per-row di `Treeview` cukup rumit; untuk v1 lebih sederhana memakai multi-select bawaan `Treeview`.
- `Stop` pada v1 berarti meminta proses batch berhenti secara kooperatif: task yang belum mulai tidak dijalankan, task yang sedang berjalan dibiarkan selesai atau timeout.

## Threading dan Update UI

Tkinter tidak thread-safe.

Aturan implementasi:

- Network call berjalan di worker thread.
- Update widget dilakukan hanya di main thread.
- Gunakan queue atau `after()` callback untuk mengirim hasil dari worker ke UI.
- Untuk cancel, gunakan shared cancel flag / event yang dicek sebelum submit task baru dan sebelum memproses hasil callback.

Jangan:

- mengubah `Treeview` langsung dari worker thread,
- menjalankan validasi ratusan item di main thread.

### Catatan Cancel Task

Dengan `ThreadPoolExecutor`, request network yang sudah sedang berjalan umumnya tidak bisa dihentikan paksa secara aman.

Karena itu, perilaku `Stop` untuk v1 didefinisikan sebagai:

- menghentikan penjadwalan task baru,
- mengabaikan hasil baru jika batch sudah dibatalkan,
- menunggu task aktif selesai sendiri atau timeout,
- mengembalikan UI ke state idle setelah batch aktif selesai.

Ini cukup untuk UX v1 dan jauh lebih realistis daripada menjanjikan hard cancel.

## Error Handling

Harus ditangani minimal untuk:

- request timeout,
- DNS/connection error,
- malformed source line,
- invalid port,
- dependency SOCKS belum terpasang,
- user menekan validate saat data kosong,
- user menutup app saat task masih berjalan.

## Dependencies

`requirements.txt` (production):

```text
customtkinter>=5.2.0
requests[socks]>=2.31.0
```

Dev dependencies (opsional, untuk testing):

```text
pytest>=8.0.0
```

Jika `requests[socks]` tidak dipakai, maka dukungan SOCKS pada planning ini tidak benar-benar terpenuhi.

## Rencana Implementasi Bertahap

### Fase 1 - Core Logic

- buat model `ProxyRecord`,
- implement parser,
- implement fetcher,
- implement filtering,
- implement validator tunggal.

Deliverable:

- fungsi fetch dan parse bekerja tanpa GUI.

### Fase 2 - Batch Validation

- implement `validate_proxies(...)`,
- progress callback,
- hasil per-proxy,
- timeout dan worker config.

Deliverable:

- validasi batch stabil lewat test atau smoke test.

### Fase 3 - GUI Dasar

- window utama,
- filter controls,
- tabel hasil,
- fetch action,
- validate action,
- stop/cancel task (penting agar user tidak perlu force-close saat validasi berjalan),
- status bar.

Deliverable:

- user bisa fetch, filter, validate, cancel, dan copy dari GUI.

### Fase 4 - Polish

- context menu (right-click),
- sorting kolom,
- persist setting sederhana,
- README dan packaging.

## Acceptance Criteria

Aplikasi dianggap memenuhi target v1 jika:

1. User dapat mengambil daftar proxy dari sumber yang dipilih.
2. Data tampil rapi dan bisa difilter tanpa fetch ulang.
3. User dapat memvalidasi item terpilih atau seluruh hasil filter.
4. Status validasi dan response time tampil per item.
5. GUI tetap responsif selama fetch dan validasi.
6. Proxy SOCKS4 dan SOCKS5 dapat divalidasi jika dependency SOCKS terpasang.
7. Error umum tidak membuat aplikasi crash.

## Verification Plan

### Functional Check

1. Jalankan `python main.py`.
2. Fetch source `all.txt`.
3. Pastikan tabel terisi dan jumlah total benar.
4. Ubah filter country, protocol, anonymity, dan search.
5. Validasi beberapa item terpilih.
6. Validasi seluruh hasil filter.
7. Copy selected dan paste hasilnya.

### Failure Check

1. Simulasikan source URL salah.
2. Simulasikan timeout endpoint validasi.
3. Jalankan tanpa data lalu klik validate.
4. Uji minimal satu proxy SOCKS untuk memastikan dependency benar-benar bekerja.

### Quality Check

1. Parser mengabaikan baris rusak tanpa menghentikan proses seluruh file.
2. UI tetap bisa diinteraksi saat validasi berjalan.
3. Menutup aplikasi saat task aktif tidak menyebabkan traceback.

## Rekomendasi Implementasi

Jika proyek ini benar-benar akan dikerjakan, urutan yang paling masuk akal adalah:

1. bangun dan tes core service tanpa GUI,
2. baru hubungkan ke CustomTkinter,
3. terakhir polish UX.

Kalau urutannya dibalik, bug akan menumpuk di UI padahal sumber masalah ada di network/parsing/validation layer.
