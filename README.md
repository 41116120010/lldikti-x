# SIPERAPAT — Sistem Pencatatan Kehadiran Rapat Kedinasan
## Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X
### Kementerian Pendidikan Tinggi, Sains, dan Teknologi Republik Indonesia

---

## 1. Executive Summary

**SIPERAPAT** (Sistem Pencatatan Kehadiran Rapat) adalah platform web terpadu berstandar enterprise yang dirancang untuk mengotomatisasi pencatatan kehadiran, penerbitan berita acara, dokumentasi visual, dan notulensi rapat kedinasan di lingkungan Lembaga Layanan Pendidikan Tinggi (LLDIKTI).

Sistem ini mentransformasikan alur kerja rapat konvensional berbasis kertas (*paper-based*) menjadi alur kerja digital nir-kertas (*paperless*) yang terverifikasi secara hukum melalui kombinasi **perekaman kamera wajah waktu nyata (WebRTC Face Capture)** dan **tanda tangan digital interaktif (HTML5 Canvas Signature Pad)** dengan proteksi transaksi atomik dan audit trail transparan.

---

## 2. Core Architecture & Engineering Principles

Pengembangan sistem SIPERAPAT berpedoman pada standar rekayasa perangkat lunak enterprise:

1. **Lightweight & High Concurrency (Ponytail / YAGNI Principle):**
   * Memaksimalkan fitur native Laravel 12+ (Policies, Form Requests, Observers, Custom Casts, Service Container) tanpa menambahkan package pihak ketiga yang redundan.
   * Dirancang untuk mampu menangani lonjakan akses simultan (*high concurrency*) dari ratusan pegawai pada jam pembukaan rapat tanpa degradasi performa.

2. **Client-Side Media Compression:**
   * Foto selfie wajah dikompresi secara asinkron di sisi browser pengguna melalui Canvas API (resolusi maksimal 600x800px, kualitas JPEG 0.75, ukuran payload < 150 KB) sebelum dikirimkan ke server, sehingga menghemat konsumsi bandwidth jaringan kantor.
   * Tanda tangan digital diekspor sebagai PNG transparan berbobot sangat ringan (< 30 KB).

3. **Integritas Penyimpanan Berkas Bersih:**
   * Dilarang keras menyimpan string mentah base64 ke dalam kolom database relational.
   * Seluruh media disimpan pada direktori penyimpanan fisik (`storage/app/public/`) dengan penamaan hash acak terenkripsi, dan hanya *relative path* yang disimpan di database.

4. **Autentikasi Multi-Identifier Cerdas:**
   * Formulir login tunggal yang otomatis mendeteksi apakah input pengguna berupa **NIP (18 digit angka)** atau **Username**.
   * Dilengkapi *rate limiter* terdistribusi untuk mencegah serangan *brute-force*.

5. **Integritas Transaksi Atomik:**
   * Seluruh mutasi berantai (presensi, pembuatan agenda, manipulasi berkas, dan pencatatan audit log) dibungkus secara mutlak dalam `DB::transaction()` untuk mencegah anomali data inkonsisten (*partial state*).

---

## 3. Matriks Peran & Hak Akses (Role-Based Access Control)

Sistem menerapkan pembagian hak akses granular berbasis 3 tingkatan peran:

| Fitur / Modul | Administrator (Superadmin) | Admin Unit (Kepala Pokja/Unit) | Staff (Pegawai) |
|---|:---:|:---:|:---:|
| Kelola Master Data Unit Kerja | Akses Penuh (CRUD) | Tidak Diizinkan | Tidak Diizinkan |
| Kelola Pengguna Sistem | Seluruh Unit (CRUD) | Hanya Unit Sendiri (CRUD) | Tidak Diizinkan |
| Buat & Kelola Agenda Rapat | Seluruh Unit | Terbuka / Unit Sendiri | Tidak Diizinkan |
| Unggah Surat Edaran / Undangan | Ya (PDF/Gambar max 5MB) | Ya (PDF/Gambar max 5MB) | Hanya Unduh/Lihat |
| Manajemen Status Rapat (Mulai/Tutup) | Ya | Hanya Agenda Buatan Sendiri | Tidak Diizinkan |
| Input Notulensi & Kesimpulan RTL | Ya | Hanya Agenda Buatan Sendiri | Hanya Lihat |
| Unggah Dokumentasi Foto Rapat | Ya (Multi-Upload) | Ya (Multi-Upload) | Hanya Lihat |
| Pengisian Presensi (Selfie + TTD) | Ya | Ya | Ya (Sesuai Undangan) |
| Tanda Terima Digital Resmi | Ya | Ya | Ya |
| Riwayat Presensi Pribadi | Ya | Ya | Ya |
| Dashboard Rekapitulasi & Analitik | Global (Semua Pokja) | Scoped (Pokja Sendiri) | Tidak Diizinkan |
| Ekspor Berita Acara (PDF & Word) | Ya | Ya | Tidak Diizinkan |
| Ekspor Tabulasi CSV / Excel | Ya | Ya | Tidak Diizinkan |
| Pemantauan Audit Trail Log | Akses Penuh | Tidak Diizinkan | Tidak Diizinkan |

---

## 4. Tech Stack & Environment Reference

* **Backend Framework:** PHP 8.3+ / Laravel 12.x / LTS Framework
* **Frontend Layer:** Laravel Blade Component + Tailwind CSS v4 + Vite + Vanilla JS
* **Iconography:** Pure Vector SVG Icons (Phosphor / Heroicons Standard)
* **Database Engine:** MariaDB 10.11+ / MySQL 8.0+ (Didukung SQLite untuk local testing suite)
* **Penyimpanan Berkas:** Local Filesystem Storage via Symlink (`storage/app/public/`)
* **Format Ekspor:**
  * PDF: Print-ready Gov-Tech Standard View dengan CSS `@media print`
  * Microsoft Word: Word XML Compliant Document (`.doc`)
  * Data Tabular: CSV Format dengan UTF-8 Byte Order Mark (BOM) untuk Microsoft Excel

---

## 5. Struktur Database & Hubungan Entitas

Sistem mengelola 7 tabel relasional utama:

```
+----------------+       1:N       +----------------+       1:N       +---------------------+
|     units      | <-------------> |     users      | <-------------> |     attendances     |
+----------------+                 +----------------+                 +---------------------+
        |                                  |                                     |
        | 1:N                              | 1:N                                 |
        v                                  v                                     |
+----------------+       N:M       +----------------+                            |
|  agenda_units  | <-------------> |    agendas     | <--------------------------+
+----------------+                 +----------------+
                                           |
                                           | 1:N
                                           v
                                   +---------------------+
                                   |agenda_documentations|
                                   +---------------------+

+---------------------+
|    activity_logs    | (Pencatatan Audit Trail Transparan)
+---------------------+
```

* **`units`**: Menyimpan data bagian, kelompok kerja (Pokja), dan subbagian instansi.
* **`users`**: Menyimpan identitas pegawai, NIP (18 digit), username, password hash (Bcrypt), role, dan status aktif.
* **`agendas`**: Menyimpan data induk rapat, format (offline/online/hybrid), tautan daring, ruangan, surat edaran, notulensi, dan kesimpulan RTL.
* **`agenda_units`**: Tabel pivot pemetaan undangan rapat lintas unit kerja.
* **`attendances`**: Menyimpan rekaman presensi sah, waktu presensi, relative path selfie, relative path tanda tangan, alamat IP, dan User-Agent.
* **`agenda_documentations`**: Galeri foto dokumentasi rapat beserta takarir (*caption*).
* **`activity_logs`**: Rekam jejak seluruh mutasi sistem untuk kepatuhan audit tata kelola pemerintahan.

---

## 6. Fitur Unggulan Sistem

### 6.1. Alur Siklus Rapat Kedinasan (Meeting Lifecycle Workflow)
Siklus rapat dikontrol melalui transisi status ketat:
`Draft` -> `Scheduled (Terjadwal)` -> `Ongoing (Presensi Dibuka)` -> `Completed (Selesai/Presensi Ditutup)` -> `Cancelled (Dibatalkan)`.

### 6.2. Presensi Terverifikasi (WebRTC Live Selfie + Signature Pad)
* **Oval Face Guide:** Panduan visual bingkai wajah pada video stream kamera pengguna.
* **Fallback Camera:** Opsi otomatis unggah berkas kamera native jika izin WebRTC diblokir peramban pengguna.
* **Digital Signature Pad:** Kanvas responsif dengan deteksi sentuhan jari (*touch events*) pada layar smartphone dan kursor mouse pada desktop.
* **Anti Double Check-in:** Perlindungan database ganda untuk mencegah presensi berulang oleh pegawai yang sama pada satu sesi rapat.

### 6.3. Layanan Dokumen Kedinasan (PDF & Word Export)
* **Kop Surat Resmi:** Dilengkapi Kop Surat Kementerian Pendidikan Tinggi, Sains, dan Teknologi — LLDIKTI Wilayah X.
* **Penomoran Berita Acara Otomatis:** Format formal `BA-RAPAT/YYYY/000X`.
* **Daftar Hadir Tersemat:** Menampilkan nama, NIP, unit kerja, jam hadir, dan tanda tangan digital tersemat langsung pada dokumen.
* **Tanda Tangan Pengesahan:** Blok pengesahan pimpinan rapat dan notulis instansi.

---

## 7. Petunjuk Instalasi & Menjalankan Sistem

### 7.1. Prasyarat Sistem
* PHP >= 8.3 dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`
* Composer >= 2.6
* Node.js >= 20.x & NPM
* Database Server: MariaDB / MySQL

### 7.2. Langkah Instalasi

1. **Clone Repositori:**
   ```bash
   git clone https://github.com/daffiq/lldikti-x.git
   cd lldikti-x
   ```

2. **Instal Dependensi Backend & Frontend:**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment:**
   Salin file konfigurasi environment dan sesuaikan kredensial basis data:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Pastikan konfigurasi database pada file `.env` telah sesuai:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3307
   DB_DATABASE=lldikti_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

4. **Eksekusi Migrasi & Database Seeder:**
   ```bash
   php artisan migrate --seed
   ```

5. **Hubungkan Storage Symlink:**
   ```bash
   php artisan storage:link
   ```

6. **Kompilasi Aset Frontend (Vite):**
   ```bash
   npm run build
   ```

7. **Jalankan Server Aplikasi:**
   ```bash
   php artisan serve
   ```
   Aplikasi dapat diakses melalui browser pada alamat: `http://127.0.0.1:8000`

---

## 8. Kredensial Akun Pengujian (Demo Data)

Kredensial bawaan hasil database seeder untuk keperluan pengujian:

| Role | Username | NIP (18 Digit) | Password | Deskripsi Hak Akses |
|---|---|---|---|---|
| Administrator | `superadmin` | `197501152000031001` | `Password123!` | Superadmin Tingkat Lembaga |
| Admin Unit | `admin_akademik` | `198005202005011002` | `Password123!` | Kepala Pokja Akademik (POKJA-AKM) |
| Admin Unit | `admin_kelembagaan` | `198508122008122003` | `Password123!` | Kepala Pokja Kelembagaan (POKJA-KLB) |
| Staff Pegawai | `staff_rizky` | `199402142020121004` | `Password123!` | Pegawai Pokja Akademik |
| Staff Pegawai | `staff_nurul` | `199611252022032005` | `Password123!` | Pegawai Pokja Kelembagaan |
| Staff Pegawai | `staff_ahmad` | `199806102023051006` | `Password123!` | Pegawai Bagian Tata Usaha (BAG-TU) |

---

## 9. Pengujian Otomatis & Penjaminan Mutu (Automated QA)

Aplikasi dilengkapi test suite komprehensif menggunakan PHPUnit yang mencakup Unit Testing, Feature Testing, dan End-to-End User Acceptance Testing (UAT).

### 9.1. Menjalankan Seluruh Test Suite
```bash
php artisan test
```

### 9.2. Cakupan Pengujian
* **MultiIdentifierAuthenticationTest:** Validasi login cerdas NIP vs Username, proteksi brute-force, dan penolakan akun nonaktif.
* **UnitManagementTest:** Validasi isolasi wewenang master unit kerja.
* **UserManagementTest:** Validasi isolasi modifikasi data pengguna lintas unit.
* **ActivityLogTest:** Validasi pencatatan audit trail otomatis pada setiap mutasi.
* **AgendaManagementTest:** Validasi alur siklus rapat, unggah surat edaran, notulensi, dan dokumentasi foto.
* **AttendanceCheckInTest:** Validasi selfie WebRTC, tanda tangan canvas, pencegahan presensi ganda, dan penerbitan tanda terima sah.
* **ReportAndExportTest:** Validasi agregasi analitik, ekspor Berita Acara PDF, ekspor Word (.doc), dan ekspor CSV.
* **EndToEndUserAcceptanceTest:** Simulasi menyeluruh siklus operasional rapat dari hulu ke hilir.

Hasil eksekusi pengujian standar:
```
Tests: 49 passed (190 assertions)
Duration: 2.33s
Status: 100% PASS
```

---

## 10. Standar Keamanan & Kepatuhan Pemerintah

1. **Proteksi Injeksi & XSS:** Seluruh data keluaran disanitasi menggunakan engine Blade auto-escaping `{{ ... }}` dan validasi ketat pada level Form Request.
2. **Keamanan Berkas Unggahan:** Pembatasan tipe MIME berkas secara ketat (`pdf, jpeg, jpg, png, webp`) dengan limitasi ukuran maksimal pada level request.
3. **Penyembunyian Data Sensitif:** Atribut sensitif (`password`, `remember_token`) disembunyikan secara otomatis dari serialisasi model Eloquent.
4. **Header HTTP Hardening:** Dilengkapi middleware keamanan kustom (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`).

---

## 11. Hak Cipta & Lisensi

Dokumentasi dan kode sumber aplikasi ini dikembangkan untuk kebutuhan operasional kedinasan **Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X**, Kementerian Pendidikan Tinggi, Sains, dan Teknologi Republik Indonesia. Hak cipta dilindungi undang-undang.
