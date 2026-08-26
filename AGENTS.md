# AGENTS.md — Development Guidelines & Architectural Standards
## Sistem Pencatatan Kehadiran Rapat Berbasis Web (SIPERAPAT - LLDIKTI)

Dokumen ini adalah pedoman baku dan instruksi operasional untuk AI Agent dan Software Engineer yang bekerja pada repositori ini. Seluruh kontribusi kode wajib mematuhi standar enterprise, prinsip kesederhanaan (*Ponytail / YAGNI*), performa tinggi, dan standar kualitas instansi pemerintahan yang tercantum di bawah ini.

---

## 1. Core Persona & Nilai Rekayasa Perangkat Lunak

Sebagai Senior Software Engineer dengan standar Enterprise:
* **Scalable & Lightweight:** Mengutamakan performa eksekusi cepat, konsumsi memori rendah, dan arsitektur yang mudah diskalakan tanpa ketergantungan *over-engineering*.
* **YAGNI & Zero-Bloatware (Ponytail Principle):** Jangan membuat layer abstraksi yang tidak diminta. Maksimalkan fitur native Laravel 12+ (Policies, Form Requests, Observers, Custom Casts, Blade Components) sebelum memutuskan menambah package eksternal.
* **Durabilitas & Ketersediaan Tinggi:** Sistem harus tahan banting saat diakses bersamaan oleh ratusan pegawai pada jam pembukaan rapat.
* **Keamanan Tingkat Pemerintahan:** Proteksi CSRF, sanitasi XSS, validasi MIME-type ketat, otorisasi berbasis Role & Unit Scoping yang ketat, dan audit trail transparan.

---

## 2. Tech Stack & Environment Reference

* **Backend:** PHP 8.3+ / Laravel 12+ (Framework `^13.8` / LTS)
* **Frontend:** Laravel Blade + Tailwind CSS v4 + Vite + Alpine.js / Vanilla JS
* **Icon Library:** Phosphor Icons / Heroicons (Format SVG murni). **Dilarang memakai emoji sebagai icon tombol/navigasi**.
* **Database:** MySQL 8.0+ / PostgreSQL 15+ (Didukung SQLite untuk local test suite)
* **Dokumentasi Terkait:**
  * Spesifikasi Kebutuhan Produk: [`PRD.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/PRD.md)
  * Skema & Kamus Data: [`ERD.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/ERD.md)

---

## 3. Aturan Arsitektur & Standar Kode

### 3.1. Struktur Folder & Tanggung Jawab Kode
* **`app/Http/Controllers/`:** Controller bertindak sebagai *traffic coordinator* yang ramping (*thin controller*). Hindari logika bisnis kompleks bersarang di controller.
* **`app/Http/Requests/`:** Seluruh validasi formulir wajib menggunakan *Form Request classes* khusus (contoh: `StoreAgendaRequest`, `AttendanceCheckInRequest`).
* **`app/Policies/`:** Seluruh pengecekan izin akses role dan unit scope wajib menggunakan Laravel Policy (contoh: `AgendaPolicy`, `UserPolicy`).
* **`app/Services/`:** Logika kompleks seperti *Activity Logging*, *PDF Generation*, dan *Word Document Export* diletakkan di dalam Service Class.
* **`app/Models/`:** Definisikan relasi Eloquent lengkap dengan *type hints*, *casts*, dan *local scopes* (misal: `scopeActive()`, `scopeForUnit($unitId)`).

### 3.2. Penanganan Media & Kompresi Berkas (Wajib)
* **Selfie Kamera Wajah:**
  * Wajib dikompresi di sisi klien (*Client-side Canvas Compression*) sebelum di-upload ke server (Target: JPEG/WebP, resolusi max 600x800px, quality 0.75, ukuran < 150 KB).
  * Backend tetap melakukan validasi ulang ukuran dan tipe MIME berkas (`image/jpeg, image/png, image/webp`, max: 512 KB).
* **Tanda Tangan Digital:**
  * Diambil via HTML5 Canvas murni dan disimpan sebagai PNG transparan berukuran ringan (< 30 KB).
* **Aturan Penyimpanan:**
  * Dilarang keras menyimpan *base64 data URI* langsung ke kolom database MySQL/PostgreSQL.
  * Media disimpan ke direktori `storage/app/public/` dengan penamaan hash acak, dan hanya *relative path*-nya yang disimpan di database.

### 3.3. Autentikasi Multi-Identifier
* Login menerima satu input field yang otomatis mengidentifikasi apakah kredensial yang dimasukkan adalah **NIP (18 digit)** atau **Username**.
* Dilengkapi dengan *Rate Limiter* bawaan Laravel untuk mencegah *brute-force attack*.

### 3.4. Transaksi & Integritas Data
* Operasi mutasi berantai (seperti presensi yang melibatkan upload file, insert tabel `attendances`, dan insert `activity_logs`) **wajib** dibungkus dalam `DB::transaction(function () { ... })`.

---

## 4. Standar Desain UI/UX (Clean Gov-Tech)

1. **Palet Warna Institusi:**
   * Primary: Deep Navy `#0F172A` / `#1E3A8A`
   * Accent / Status Hadir: Emerald `#059669`
   * Background: Slate Light `#F8FAFC` & `#FFFFFF`
2. **Touch Targets & Responsivitas:**
   * Seluruh elemen interaktif (tombol, input, checkbox, canvas tanda tangan) harus memiliki area sentuh minimal **44x44px** agar nyaman digunakan pada smartphone.
3. **Kompatibilitas Layar Kamera:**
   * Frame kamera selfie harus menyediakan *visual guide* (panduan bingkai oval wajah) dan tombol capture yang tegas.
   * Wajib menyediakan *fallback file upload* jika kamera WebRTC diblokir oleh izin peramban pengguna.

---

## 5. Protokol Kerja Bertahap (Step-by-Step Workflow)

Pengembangan sistem ini dilakukan secara bertahap dalam 6 fase. **Setiap agen dilarang melompati fase tanpa verifikasi dan konfirmasi eksplisit dari pengguna.**

```
┌─────────────────────────────────────────────────────────────┐
│ Fase 1: Fondasi Database, Seeder & Auth Login Multi-ID     │
└──────────────────────────────┬──────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Fase 2: Master Data Unit & Scoped User Management           │
└──────────────────────────────┬──────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Fase 3: Manajemen Agenda Rapat, Surat Edaran & Notulensi    │
└──────────────────────────────┬──────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Fase 4: Presensi Interaktif (Selfie WebRTC + Signature Pad) │
└──────────────────────────────┬──────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Fase 5: Dashboard Rekapitulasi & Ekspor Laporan (PDF/Word)  │
└──────────────────────────────┬──────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Fase 6: Security Hardening, Polish UI/UX, & UAT Testing     │
└─────────────────────────────────────────────────────────────┘
```

---

## 6. Daftar Larangan (Strict Anti-Patterns)

❌ **DILARANG** menginstal package berat yang redundan jika Laravel sudah menyediakannya secara native.  
❌ **DILARANG** menyimpan string base64 gambar langsung ke dalam kolom teks database.  
❌ **DILARANG** melakukan query berulang di dalam loop (*N+1 problem*). Selalu gunakan eager loading `with()`.  
❌ **DILARANG** menggunakan emoji untuk ikon struktural antarmuka (gunakan SVG Phosphor/Heroicons).  
❌ **DILARANG** mengeksekusi migrasi berbahaya (`migrate:fresh`) di lingkungan produksi tanpa konfirmasi pengguna.
