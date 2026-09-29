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
   * Memaksimalkan fitur native Laravel 13 (Policies, Form Requests, Observers, Custom Casts, Service Container) tanpa menambahkan package pihak ketiga yang redundan.
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

* **Backend Framework:** PHP 8.3+ / Laravel 13.x / LTS Framework
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
   git clone https://github.com/41116120010/lldikti-x.git
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
   DB_PORT=3306
   DB_DATABASE=lldikti_db
   DB_USERNAME=<user_database_anda>
   DB_PASSWORD=<kata_sandi_yang_kuat>
   ```

4. **Eksekusi Migrasi & Database Seeder:**
   ```bash
   php artisan migrate --seed
   ```

   > **Penting setelah upgrade:** migration
   > `2026_09_27_000001_add_lokasi_ruang_normalized_to_agendas_table` menambahkan
   > kolom `lokasi_ruang_normalized` beserta index kompositnya, lalu melakukan
   > backfill dalam chunk. Kolom inilah yang membuat pengecekan konflik ruangan
   > dapat memakai index — sebelumnya `LOWER(TRIM(lokasi_ruang)) = ?` membungkus
   > kolom dalam fungsi sehingga selalu full table scan. Jalankan
   > `php artisan migrate`; tanpa itu deteksi konflik ruangan tidak berjalan.

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

## 8. Data Awal (Demo)

Jalankan perintah berikut untuk mengisi data awal aplikasi:

```bash
php artisan migrate --seed
```

> **⚠️ Kredensial demo tidak lagi didokumentasikan pada repositori ini.**
>
> Repository bersifat publik, sehingga memuatkan nama pengguna, NIP, dan kata sandi
> akun di sini akan memberi akses gratis ke setiap instance yang memakai seed
> tersebut. Kredensial default dicatat pada **dokumentasi internal instansi** dan
> harus diganti segera setelah deployment.
>
> Untuk lingkungan evaluasi, tetapkan kredensial sendiri melalui seeder internal
> atau console:
>
> ```bash
> php artisan tinker
> >>> App\Models\User::where('role', 'administrator')->first()
>      ->update(['password' => Hash::make('kata-sandi-baru-yang-kuat')]);
> ```
>
> Lihat `AUDIT_CODEBASE.md` §1.13 untuk rationale lengkap.

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

### 9.3. Database Uji Terpisah (Disarankan)

Feature test melakukan penulisan data sungguhan (insert, update, delete) di dalam
`DatabaseTransactions`, sehingga **tidak boleh** dijalankan terhadap database yang
memakai data asli.

`Tests\TestCase` mencetak peringatan di STDERR bila nama database tidak mengandung
kata `test` (dan bukan SQLite), karena feature test bergantung pada data seed
aplikasi. Suite tetap berjalan normal; hanya ketegakannya yang opsional.

```bash
# Buat database uji khusus
mysql -u root -p -e "CREATE DATABASE lldikti_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Isi dengan skema + data seed yang sama
DB_DATABASE=lldikti_db_test php artisan migrate:fresh --seed

# Simpan konfigurasi uji (tidak masuk version control)
cp .env .env.testing
# lalu pada .env.testing pastikan:
#   DB_DATABASE=lldikti_db_test
#   APP_ENV=testing

# Opsional: jadikan ketegakan wajib, bukan sekadar peringatan
#   SIPERAPAT_STRICT_TEST_DB=true

# Sekarang suite dapat dijalankan
php artisan test
```

Bila suatu saat Anda perlu menjalankan suite terhadap skema tertentu yang bukan
bernama `test`, tambahkan secara eksplisit:

```env
SIPERAPAT_STRICT_TEST_DB=true
```

> **Status migrasi:** `phpunit.xml` sengaja tidak lagi memuat kredensial database.
> Nilai diambil dari `.env` / `.env.testing` milik developer.
>
> `Tests\TestCase` mencetak **peringatan** (bukan error) bila nama database tidak
> mengandung `test`, karena feature test memang bergantung pada data seed aplikasi.
> Ketegakan penuh dapat diaktifkan dengan `SIPERAPAT_STRICT_TEST_DB=1` di
> `.env.testing` setelah suite punya skema uji tersendiri. Untuk repository publik,
> `SQLite :memory:` + `RefreshDatabase` adalah target jangka panjang —
> lihat `AUDIT_CODEBASE.md` §2.23 (P1).

---

## 10. Deployment Produksi

### 10.1. Prasyarat (WAJIB)

* **TLS aktif.** Aplikasi mengirim foto selfie dan tanda tangan digital (data
  biometrik) setiap pegawai. Tanpa HTTPS seluruh data dapat disadap di jaringan.
  Ikuti langkah 5 pada `deploy/nginx/siperapat.conf`.
* **Blokir eksekusi PHP di `/storage/`.** Area unggahan berada di bawah symlink
  `public/storage`. Blok `location ^~ /storage/ { try_files $uri =404; }` pada
  konfigurasi nginx membuat berkas `.php`/`.phtml` di area tersebut tidak pernah
  diteruskan ke PHP-FPM. Jangan menghapus blok ini.
* **Driver session dan cache bukan database.** Dengan ratusan pegawai yang check-in
  bersamaan, session berbasis `database` menyebabkan lock contention. Gunakan
  Redis (lihat `config/cache.php` yang sudah menyediakan store `redis`).
* **Kredensial Redis sudah tersedia** di `config/database.php` (`redis.cache` +
  `REDIS_CACHE_DB`) tetapi belum dipakai — tidak ada package tambahan yang perlu
  dipasang, cukup variabel environment.

### 10.2. Environment Produksi

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siperapat.lldikti10.kemdikbud.go.id

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
CACHE_STORE=redis
QUEUE_CONNECTION=redis

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=30
```

### 10.2b. Guard Kualitas Query (opsional, non-produksi)

Untuk menangkap N+1 dan atribut yang hilang diam-diam saat pengembangan:

```env
# .env.testing atau .env lokal
SIPERAPAT_STRICT_QUERIES=true
```

> `Tests\TestCase` mencetak peringatan bila nama database tidak mengandung `test`
> (lihat §9.3) dan dapat dijadikan ketegakan wajib dengan
> `SIPERAPAT_STRICT_TEST_DB=true`.

### 10.3. Perintah Deployment

```bash
# 1. Dependency produksi TANPA paket development
composer install --no-dev --optimize-autoloader --classmap-authoritative

# 2. Aset frontend
npm ci
npm run build

# 3. Migrasi
php artisan migrate --force

# 4. Cache konfigurasi & route (env() tidak lagi dipanggil per request)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Symlink storage
php artisan storage:link

# 6. Izin direktori
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

> **`--no-dev` bukan opsional.** Tanpa flag tersebut, 33 paket development ikut
> terpasang, termasuk `psy/psysh` — REPL yang dapat mengeksekusi kode PHP
> arbitrer di mesin produksi.

### 10.4. Proses Latar (Supervisor)

```ini
# /etc/supervisor/conf.d/siperapat-worker.conf
[program:siperapat-worker]
command=php /var/www/siperapat/artisan queue:work --tries=3 --timeout=180 --max-time=3600
autostart=true
autorestart=true
numprocs=4
user=www-data
stopwaitsecs=180
redirect_stderr=true
stdout_logfile=/var/www/siperapat/storage/logs/worker.log
```

```bash
sudo supervisorctl reread && sudo supervisorctl update
```

### 10.5. Penjadwalan (Cron)

```cron
* * * * * cd /var/www/siperapat && php artisan schedule:run >> /dev/null 2>&1
```

### 10.6. Strategi Cadangan (Backup)

| Aspek | Rekomendasi |
|---|---|
| Database | Snapshot harian (`mysqldump --single-transaction`) dan simpan minimal 30 hari |
| Penyimpanan media | Backup `storage/app/public` (foto selfie, tanda tangan, surat edaran) — **tidak tercakup backup database** |
| Konfigurasi | Simpan salinan terenkripsi `.env` di luar repositori |
| Uji pemulihan | Lakukan restore drill secara berkala; backup yang tak pernah diuji bukan backup |

### 10.7. Verifikasi Pasca-Deployment

```bash
# TLS dan HSTS
curl -I https://siperapat.lldikti10.kemdikbud.go.id/up
# Harus 200 + header Strict-Transport-Security

# Area unggahan tidak boleh mengeksekusi skrip
curl -I https://siperapat.lldikti10.kemdikbud.go.id/storage/probe.php
# Harus 404

# Debug mode harus mati
grep -E '^APP_DEBUG' .env        # harus false

# Pesan error tidak boleh membocorkan stack trace
curl -s https://siperapat.lldikti10.kemdikbud.go.id/agenda-tidak-ada-12345 | grep -i "stack trace\|vendor/laravel" && echo "BAHAYA: debug aktif"
```

---

## 11. Standar Keamanan & Kepatuhan Pemerintah

1. **Proteksi Injeksi & XSS:** Seluruh data keluaran disanitasi menggunakan engine Blade auto-escaping `{{ ... }}` dan validasi ketat pada level Form Request. Dialog aplikasi hanya menulis markup miliknya sendiri lewat `innerHTML`; judul, pesan, dan nilai error selalu di-*set* melalui `textContent`.
2. **Keamanan Berkas Unggahan:** Pembatasan tipe MIME berkas secara ketat (`pdf, jpeg, jpg, png, webp`) dengan limitasi ukuran maksimal pada level request. Ekstensi berkas **tidak pernah** diambil dari nama berkas kiriman klien — selalu diturunkan dari magic bytes, dan area `/storage/` diblokir agar tidak dieksekusi PHP.
3. **Penyembunyian Data Sensitif:** Atribut sensitif (`password`, `remember_token`) disembunyikan secara otomatis dari serialisasi model Eloquent.
4. **Header HTTP Hardening:** Dilengkapi middleware keamanan kustom (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Strict-Transport-Security`) serta `Content-Security-Policy` pada level aplikasi.
5. **Otorisasi Berlapis:** Setiap halaman mutasi melewati Policy atau Form Request `authorize()`, ditambah verifikasi kepemilikan baris (resource binding) agar tidak ada akses lintas pengguna.
6. **Kredensial:** Tidak ada kredensial, kata sandi, atau credential yang dicantumkan dalam repositori maupun dokumentasi publik.

> Catatan: `X-XSS-Protection` sudah dihapus dari header karena dinonaktifkan di
> browser modern, dan CSP nonce-based masih menjadi target tahap berikutnya
> (`AUDIT_CODEBASE.md` §2.2, P2).

---

## 12. Hak Cipta & Lisensi

Dokumentasi dan kode sumber aplikasi ini dikembangkan untuk kebutuhan operasional kedinasan **Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X**, Kementerian Pendidikan Tinggi, Sains, dan Teknologi Republik Indonesia. Hak cipta dilindungi undang-undang.
