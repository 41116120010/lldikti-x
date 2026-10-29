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

4. **Autentikasi & Pemulihan Akun Multi-Identifier Cerdas:**
   * Formulir login tunggal yang otomatis mendeteksi apakah input pengguna berupa **NIP (18 digit angka)** atau **Username**.
   * Fitur **Lupa Kata Sandi Mandiri (*Self-Service Password Reset*)** yang menerima **NIP (18 digit)**, **Username**, atau **Email Kedinasan**, terintegrasi dengan **Brevo Transactional Email** (mendukung SMTP Port 465 Implicit TLS `smtps`, Port 587 STARTTLS, serta fallback otomatis ke Brevo HTTP API v3 HTTPS Port 443).
   * Dilengkapi *rate limiter* terdistribusi untuk mencegah serangan *brute-force* serta pencabutan otomatis seluruh token sesi aktif saat kata sandi diperbarui.

5. **Integritas Transaksi Atomik & Konkurensi Ruangan:**
   * Seluruh mutasi berantai (presensi, pembuatan agenda, manipulasi berkas, dan pencatatan audit log) dibungkus secara mutlak dalam `DB::transaction()` untuk mencegah anomali data inkonsisten (*partial state*).
   * Pengecekan bentrok ruang rapat dilindungi oleh *advisory lock* native PostgreSQL (`pg_advisory_lock`) dan kolom ternormalisasi terindeks (`lokasi_ruang_normalized`).

---

## 3. Matriks Peran & Hak Akses (Role-Based Access Control)

Sistem menerapkan pembagian hak akses granular berbasis 3 tingkatan peran utama disertai delegasi penugasan sesi rapat (**Pimpinan Rapat** & **Notulis Rapat** lintas unit kerja):

| Fitur / Modul | Administrator (Superadmin) | Admin Unit (Kepala Pokja/Unit) | Staff (Pegawai ASN) |
|---|:---:|:---:|:---:|
| Login & Lupa Kata Sandi Mandiri | Ya (Multi-ID) | Ya (Multi-ID) | Ya (Multi-ID) |
| Kelola Master Data Unit Kerja | Akses Penuh (CRUD) | Tidak Diizinkan | Tidak Diizinkan |
| Kelola Pengguna Sistem | Seluruh Unit (CRUD) | Hanya Unit Sendiri (CRUD) | Tidak Diizinkan |
| Buat & Kelola Agenda Rapat | Seluruh Unit | Terbuka / Unit Sendiri | Tidak Diizinkan |
| Unggah Surat Edaran / Undangan | Ya (PDF/Gambar max 5MB) | Ya (PDF/Gambar max 5MB) | Hanya Unduh/Lihat |
| Manajemen Status Rapat (Mulai/Tutup) | Ya | Unit Penyelenggara / Pimpinan & Notulis Sesi Aktif | Tidak Diizinkan |
| Input Notulensi & Kesimpulan RTL | Ya | Unit Penyelenggara / Pimpinan & Notulis Sesi Aktif | Ya (Jika Ditunjuk Notulis/Pimpinan Saat Ongoing) |
| Unggah Dokumentasi Foto Rapat | Ya (Multi-Upload) | Unit Penyelenggara / Pimpinan & Notulis Sesi Aktif | Ya (Jika Ditunjuk Notulis/Pimpinan Saat Ongoing) |
| Pengisian Presensi (Selfie + TTD) | Ya | Ya | Ya (Sesuai Undangan / Penugasan) |
| Tanda Terima Digital Resmi | Ya | Ya | Ya |
| Riwayat Presensi Pribadi | Ya | Ya | Ya |
| Dashboard Rekapitulasi & Analitik | Global (Semua Pokja) | Scoped (Pokja Sendiri & Terkait) | Tidak Diizinkan |
| Ekspor Berita Acara (PDF & Word) | Ya | Ya (Agenda Terkait) | Tidak Diizinkan |
| Ekspor Tabulasi CSV / Excel (13 Kolom) | Ya | Ya (Agenda Terkait) | Tidak Diizinkan |
| Pemantauan Audit Trail Log | Akses Penuh (Instansi) | Aktivitas Pribadi | Aktivitas Pribadi |

---

## 4. Tech Stack & Environment Reference

* **Backend Framework:** PHP 8.3+ / Laravel 13.x / LTS Framework
* **Frontend Layer:** Laravel Blade Component + Tailwind CSS v4 + Vite + Vanilla JS
* **Iconography:** Pure Vector SVG Icons (Phosphor / Heroicons Standard)
* **Database Engine:** PostgreSQL 15+ (Primary Production Engine) / MariaDB 10.11+ & MySQL 8.0+ (Backward Compatible) / SQLite 3.35+ (Local Testing)
* **Transactional Email Delivery:** Brevo (Sendinblue) SMTP Relay Port 465 Implicit TLS (`smtps`) / Port 587 STARTTLS & Brevo HTTP API v3 (`symfony/brevo-mailer`)
* **Penyimpanan Berkas:** Local Filesystem Storage via Symlink (`storage/app/public/`) & Controller Streaming Terproteksi untuk Data Biometrik ASN
* **Format Ekspor:**
  * PDF: Binary PDF via LibreOffice Headless (dengan *slot concurrency pool*) & Fallback Print-ready Gov-Tech Standard View (`@media print`)
  * Microsoft Word: Native OpenXML Document (`.docx` via `DocxExportService`) & Word XML Document (`.doc`)
  * Data Tabular: CSV Format (13 kolom ringkasan agenda & tabulasi daftar hadir) dengan UTF-8 Byte Order Mark (BOM) dan proteksi CSV Formula Injection (CWE-1236)
* **Dokumentasi Terkait:**
  * Pedoman Arsitektur & Standar Kode: [`AGENTS.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/AGENTS.md)
  * Panduan Migrasi Database: [`MIGRATION_POSTGRES.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/MIGRATION_POSTGRES.md)
  * Kebijakan Backup & DR: [`BACKUP.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/BACKUP.md)
  * Spesifikasi REST API Mobile v1: [`API.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/API.md)
  * Spesifikasi Kebutuhan Produk: [`PRD.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/PRD.md)
  * Skema & Kamus Data: [`ERD.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/ERD.md)
  * Laporan Audit & Rekapitulasi Peningkatan: [`AUDIT_CODEBASE.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/AUDIT_CODEBASE.md)

---

## 5. Struktur Database & Hubungan Entitas

Sistem mengelola 7 tabel domain relasional utama beserta tabel infrastruktur keamanan dan sesi:

```
+----------------+       1:N       +----------------+       1:N       +---------------------+
|     units      | <-------------> |     users      | <-------------> |     attendances     |
+----------------+                 +----------------+                 +---------------------+
        |                                  |                                     |
        | 1:N        created_by /          | 1:N                                 |
        v        pimpinan_id / notulis_id  v                                     |
+----------------+       N:M       +----------------+                            |
|  agenda_units  | <-------------> |    agendas     | <--------------------------+
+----------------+                 +----------------+
                                           |
                                           | 1:N
                                           v
                                   +---------------------+
                                   |agenda_documentations|
                                   +---------------------+

+------------------------+   +-------------------------+   +---------------------+
|    activity_logs       |   |  password_reset_tokens  |   |personal_access_token|
| (Audit Trail Instansi) |   | (Reset Sandi via Brevo) |   | (Bearer Token API)  |
+------------------------+   +-------------------------+   +---------------------+
```

* **`units`**: Menyimpan data 11 subbagian resmi dan kelompok kerja penjaminan mutu di lingkungan LLDIKTI Wilayah X.
* **`users`**: Menyimpan 79 data aparatur/pegawai resmi instansi (1 Administrator dan 78 Staff/Admin), NIP (18 digit), username, email kedinasan, password hash (Bcrypt), role, dan status keaktifan.
* **`agendas`**: Menyimpan data induk rapat, pembuat (`created_by`), pemimpin rapat (`pimpinan_id`), notulis rapat (`notulis_id`), format (`offline`/`online`/`hybrid`), tautan daring, ruangan (`lokasi_ruang` & `lokasi_ruang_normalized`), surat edaran, notulensi, kesimpulan RTL, dan konfigurasi berita acara (`report_config`).
* **`agenda_units`**: Tabel pivot pemetaan undangan rapat lintas unit kerja.
* **`attendances`**: Menyimpan rekaman presensi sah, waktu presensi, relative path selfie, relative path tanda tangan, alamat IP, dan User-Agent.
* **`agenda_documentations`**: Galeri foto dokumentasi rapat beserta takarir (*caption*).
* **`activity_logs`**: Rekam jejak seluruh mutasi sistem untuk kepatuhan audit tata kelola pemerintahan.
* **`password_reset_tokens` & `personal_access_tokens`**: Tabel manajemen token pemulihan kata sandi mandiri dan autentikasi Bearer Token Sanctum untuk aplikasi mobile.

---

## 6. Fitur Unggulan Sistem

### 6.1. Alur Siklus Rapat Kedinasan & Otomatisasi Status (Meeting Lifecycle Workflow)
* Siklus rapat dikontrol melalui transisi status ketat:
  `Draft` -> `Scheduled (Terjadwal)` -> `Ongoing (Presensi Dibuka)` -> `Completed (Selesai/Presensi Ditutup)` -> `Cancelled (Dibatalkan)`.
* **Jendela Buka Presensi:** Sesi rapat dapat dimulai mulai 15 menit sebelum jadwal `waktu_mulai`.
* **Penutupan Otomatis (*Auto-Close Scheduler*):** Command terjadwal `php artisan agendas:sync-status` berjalan setiap menit untuk menutup secara otomatis sesi rapat `ongoing` yang telah melewati batas waktu selesai efektif.

### 6.2. Presensi Terverifikasi (WebRTC Live Selfie + Signature Pad)
* **Oval Face Guide:** Panduan visual bingkai wajah pada video stream kamera pengguna.
* **Fallback Camera:** Opsi otomatis unggah berkas kamera native jika izin WebRTC diblokir peramban pengguna.
* **Digital Signature Pad:** Kanvas responsif dengan deteksi sentuhan jari (*touch events*) pada layar smartphone dan kursor mouse pada desktop.
* **Anti Double Check-in:** Perlindungan database ganda untuk mencegah presensi berulang oleh pegawai yang sama pada satu sesi rapat.

### 6.3. Workstation Notulensi, Layanan Dokumen Kedinasan & Analitik Laporan
* **Editor Notulensi & Kesimpulan Terstruktur:** Dilengkapi pratinjau dokumen langsung (*live sheet preview*), *sticky action bar*, dukungan penyuntingan berulang untuk agenda aktif maupun selesai, serta tata letak cetak multi-halaman yang bebas dari duplikasi sub-judul.
* **Kop Surat Resmi & Berita Acara Otomatis:** Dilengkapi Kop Surat Kementerian Pendidikan Tinggi, Sains, dan Teknologi — LLDIKTI Wilayah X dengan penomoran otomatis `BA-RAPAT/YYYY/000X`, daftar hadir tersemat, dan blok pengesahan pimpinan serta notulis.
* **Dashboard Laporan & Rekapitulasi Simetris:** Menampilkan kartu analitik *Distribusi Format Pertemuan* berdampingan secara proporsional dengan *Partisipasi per Unit Kerja*, diikuti tabel daftar rekapitulasi rapat berlebar penuh (*full-width*).
* **Ekspor Ringkasan CSV 13 Kolom:** Mencakup *No, Judul Rapat, Jenis Pertemuan, Format, Lokasi / Ruang, Waktu Mulai, Waktu Selesai, Status Rapat (Bahasa Indonesia baku), Status Notulensi (Lengkap/Belum Diisi/Sedang Berlangsung/Belum Dimulai), Pemimpin Rapat, Notulis Rapat, Penyelenggara, dan Jumlah Peserta Hadir*.

### 6.4. Lupa Kata Sandi Mandiri (Self-Service Password Reset via Brevo)
* Pengguna dapat mereset kata sandi secara mandiri melalui halaman `/forgot-password` cukup dengan memasukkan **NIP (18 digit)**, **Username**, atau **Email Kedinasan**.
* Pengiriman email menggunakan **Brevo Transactional Email** dengan dukungan penuh terhadap **Port 465 Implicit TLS (`smtps`)** (solusi andal saat Port 587 diblokir oleh penyedia VPS/hosting) maupun **Brevo HTTP API v3 (Port 443)**.
* Alamat email tujuan disamarkan (*masked email*, misal `a***a@lldiktiwilayahx.kemdiktisaintek.go.id`) pada notifikasi antarmuka demi privasi ASN, dan seluruh sesi/token lama otomatis dicabut begitu kata sandi baru disimpan.

---

## 7. Petunjuk Instalasi & Menjalankan Sistem

### 7.1. Prasyarat Sistem
* PHP >= 8.3 dengan ekstensi: `pdo_pgsql`, `pgsql`, `pdo_mysql` (opsional untuk legacy), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo`, `gd`
* Composer >= 2.6
* Node.js >= 20.x & NPM
* Database Server: PostgreSQL 15+ (Direkomendasikan / Utama) atau MariaDB 10.11+ / MySQL 8.0+

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
   Salin file konfigurasi environment dan sesuaikan kredensial basis data serta pengiriman email:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

   Pastikan konfigurasi database dan email transaksional pada file `.env` telah sesuai:
   ```env
   # PostgreSQL (Standar Utama SIPERAPAT)
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=siperapat_db
   DB_USERNAME=<user_database_anda>
   DB_PASSWORD=<kata_sandi_yang_kuat>
   DB_SCHEMA=public
   DB_SSLMODE=prefer

   # Opsi Alternatif: MySQL / MariaDB (Legacy)
   # DB_CONNECTION=mysql
   # DB_HOST=127.0.0.1
   # DB_PORT=3306
   # DB_DATABASE=lldikti_db
   # DB_USERNAME=root
   # DB_PASSWORD=

   # Layanan Pengiriman Email Transaksional (Brevo SMTP Port 465 Implicit TLS)
   MAIL_MAILER=smtp
   MAIL_SCHEME=smtps
   MAIL_HOST=smtp-relay.brevo.com
   MAIL_PORT=465
   MAIL_USERNAME=<login_smtp_brevo_anda>
   MAIL_PASSWORD=<kunci_smtp_brevo_anda>
   MAIL_ENCRYPTION=ssl
   MAIL_FROM_ADDRESS="no-reply@lldiktiwilayahx.kemdiktisaintek.go.id"
   MAIL_FROM_NAME="SIPERAPAT LLDIKTI Wilayah X"

   # Opsi Alternatif: Brevo Transactional Email HTTP API v3 (Port 443 HTTPS)
   # MAIL_MAILER=brevo
   # BREVO_API_KEY=<xkeysib_api_key_brevo_anda>
   ```

4. **Eksekusi Migrasi & Database Seeder:**
   ```bash
   php artisan migrate --seed
   ```
   *Seeder utama sistem (`DatabaseSeeder`) secara otomatis menginisialisasi 11 subbagian resmi + Pokja Penjaminan Mutu (`UnitSeeder`) dan 79 akun aparatur/pegawai resmi LLDIKTI Wilayah X (`UserSeeder`). Data agenda rapat sengaja tidak dimasukkan ke `DatabaseSeeder` agar sistem memulai operasional dari kondisi bersih (*pristine zero-agenda state*).*

   *Apabila membutuhkan data simulasi untuk pengujian antarmuka atau uji beban (*load testing*), jalankan seeder atau generator agenda secara terpisah:*
   ```bash
   # Opsi A: Muat 18 skenario agenda rapat kurasi (berbagai status & format)
   php artisan db:seed --class=AgendaSeeder

   # Opsi B: Bangkitkan sejumlah agenda acak secara massal (bebas dependensi dev-only)
   php artisan agenda:generate 25 --attendees=15
   php artisan agenda:generate 10 --status=completed --jenis=Hybrid --unit=AKM --attendees=20
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

## 8. Data Awal & Dokumentasi Akun Bawaan (Default Seed Data)

Proses seeding aplikasi menginisialisasi struktur organisasi dan kepegawaian resmi di lingkungan **Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X**:

### 8.1. Struktur Akun Resmi Bawaan

| Peran Sistem | Nama Lengkap & Gelar | NIP Resmi (18 Digit) | Username | Subbagian / Unit Kerja | Hak Akses Utama |
|---|---|---|---|---|---|
| **Administrator** | Afdalisma, SH, M.Pd | `197012051992032002` | `afdalisma` | Lintas Unit (Pusat) | Kelola seluruh unit, pengguna lintas unit, seluruh agenda rapat, audit trail, rekapitulasi & ekspor laporan |
| **Staff** | Ely Susanti, SE, MM | `196708231991032001` | `ely_susanti` | Bagian Akademik dan Kemahasiswaan | Presensi selfie WebRTC + TTD digital, riwayat presensi pribadi, unduh surat edaran & materi |
| **Staff** | Febrina Fitri, SE, M.Si | `196502101991032002` | `febrina_fitri` | Bagian Sumber Daya Perguruan Tinggi | Presensi selfie WebRTC + TTD digital, riwayat presensi pribadi, unduh surat edaran & materi |
| **Staff** | Reri Anton, ST, MM | `197003081991031001` | `reri_anton` | Pokja Pendidik dan Tenaga Kependidikan | Presensi selfie WebRTC + TTD digital, riwayat presensi pribadi, unduh surat edaran & materi |
| *(Total 78 Staff)* | *(78 Pegawai ASN)* | *(18 digit NIP valid)* | *(Username unik)* | *(11 Subbagian resmi)* | *(Presensi rapat sesuai undangan unit kerja)* |

### 8.2. Standar Kredensial & Autentikasi
* **Password Default Seluruh Akun:** `Password123!` *(Wajib diperbarui saat implementasi produksi).*
* **Domain Email Resmi Instansi:** `@lldiktiwilayahx.kemdiktisaintek.go.id`.
* **Multi-Identifier Single Input:** Pegawai dapat login secara fleksibel menggunakan **NIP (18 Digit)** ataupun **Username** alfanumerik pada satu field formulir masuk yang responsif, serta memulihkan kata sandi mandiri menggunakan **NIP (18 Digit)**, **Username**, atau **Email Kedinasan**.
* **Pemisahan Data Operasional & Generator Simulasi (*Pristine State*):** `DatabaseSeeder` menjaga tabel `agendas`, `attendances`, dan `agenda_documentations` tetap bersih (0 record) agar instansi dapat langsung memulai administrasi rapat dinas tanpa perlu membersihkan data dummy. Untuk kebutuhan demonstrasi atau pengujian beban, tersedia `AgendaSeeder` dan perintah `php artisan agenda:generate` yang dapat dieksekusi kapan saja secara mandiri.

---

## 9. Pengujian Otomatis & Penjaminan Mutu (Automated QA)

Aplikasi dilengkapi test suite komprehensif menggunakan PHPUnit yang mencakup Unit Testing, Feature Testing, API Contract Testing, dan End-to-End User Acceptance Testing (UAT).

### 9.1. Menjalankan Seluruh Test Suite
```bash
php artisan test
```

### 9.2. Cakupan Pengujian
* **MultiIdentifierAuthenticationTest & ForgotPasswordTest:** Validasi login cerdas NIP vs Username, pemulihan kata sandi multi-ID (NIP/Username/Email), proteksi brute-force, pencabutan token Sanctum saat reset kata sandi, dan penolakan akun nonaktif.
* **BrevoMailConfigurationTest:** Validasi integrasi pengiriman email transaksional Brevo via SMTP Relay Port 465 (`smtps` / SSL Implicit TLS), Port 587 (`tls` / STARTTLS), HTTP API v3 (`brevo`), dan perintah diagnostik `php artisan mail:test`.
* **SubbagianAndEmployeeSeederTest & AgendaFactoryTest:** Validasi integritas 11 subbagian resmi, akun Administrator resmi Afdalisma, 78 pegawai staff, serta keandalan `AgendaFactory`, `AttendanceFactory`, `AgendaSeeder`, dan CLI `php artisan agenda:generate` tanpa dependensi helper development.
* **UnitManagementTest & UserManagementTest:** Validasi isolasi wewenang master unit kerja dan modifikasi data pengguna lintas unit.
* **AgendaManagementTest & AgendaStatusAutomationTest:** Validasi alur siklus rapat, jendela pembukaan rapat (15 menit sebelum mulai), penutupan otomatis rapat kedaluwarsa (`agendas:sync-status`), unggah surat edaran, notulensi (termasuk re-edit notulensi tersimpan), dan dokumentasi foto.
* **AttendanceCheckInTest & CrossUnitNotulisAttendanceTest:** Validasi selfie WebRTC, tanda tangan canvas, hak akses presensi & notulensi bagi Pimpinan/Notulis lintas unit kerja saat rapat `ongoing`, pencegahan presensi ganda, dan penerbitan tanda terima sah.
* **ReportAndExportTest, ExportIntegrityTest & ReportSummaryCsvExportTest:** Validasi agregasi analitik (Distribusi Format & Partisipasi Unit Kerja), ekspor Berita Acara PDF, ekspor Word (`.docx`), dan ekspor CSV Ringkasan 13 kolom berbahasa Indonesia.
* **NotulenPreviewParityTest & KopSuratStandardTest:** Validasi paritas visual notulensi, ketiadaan duplikasi sub-judul pada cetak multi-halaman, standarisasi Kop Surat kementerian, dan penomoran resmi.
* **MobileClientWorkflowE2ETest & Api\V1\*Test:** Validasi menyeluruh kontrak REST API v1 (`/api/v1/*`), autentikasi Sanctum Bearer Token, streaming media biometrik terproteksi, dan keseragaman respons JSON.
* **ActivityLogTest & EndToEndUserAcceptanceTest:** Validasi pencatatan audit trail otomatis pada setiap mutasi, pemangkasan log tua (`activity-logs:prune`), dan simulasi menyeluruh siklus operasional rapat dari hulu ke hilir.

Hasil eksekusi pengujian standar:
```
Tests: 464 passed (3663 assertions)
Duration: ~20-25s
Status: 100% PASS
```

> **Kemandirian Test Suite (*Zero-Bloatware Test Fixture*):** `Tests\TestCase` mengimplementasikan fixture pengujian dinamis yang berjalan di dalam `DatabaseTransactions`. Setiap pengujian yang membutuhkan agenda akan memperoleh data uji terisolasi yang otomatis di-*rollback* di akhir pengujian, sehingga database operasional instansi tetap murni berstatus 0 agenda.

### 9.3. Database Uji Terpisah (Disarankan)

Feature test melakukan penulisan data sungguhan (insert, update, delete) di dalam
`DatabaseTransactions`, sehingga **tidak boleh** dijalankan terhadap database yang
memakai data asli.

`Tests\TestCase` mencetak peringatan di STDERR bila nama database tidak mengandung
kata `test` (dan bukan SQLite), karena feature test bergantung pada data seed
aplikasi. Suite tetap berjalan normal; hanya ketegakannya yang opsional.

```bash
# 1. Buat database uji khusus:
# Untuk PostgreSQL (Standar Utama):
sudo -u postgres psql -c "CREATE DATABASE siperapat_db_test OWNER siperapat_user ENCODING 'UTF8';"

# Atau untuk MySQL / MariaDB (Legacy):
# mysql -u root -p -e "CREATE DATABASE lldikti_db_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Isi dengan skema + data seed yang sama
DB_DATABASE=siperapat_db_test php artisan migrate:fresh --seed

# 3. Simpan konfigurasi uji (tidak masuk version control)
cp .env .env.testing
# lalu pada .env.testing pastikan:
#   DB_DATABASE=siperapat_db_test
#   APP_ENV=testing

# Opsional: jadikan ketegakan wajib, bukan sekadar peringatan
#   SIPERAPAT_STRICT_TEST_DB=true

# 4. Sekarang suite dapat dijalankan
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
* **Driver session, cache, dan queue.**
  * *Opsi Default (Ponytail / Zero-Bloatware):* Menggunakan `database`. Sangat stabil, tabel `cache`, `cache_locks`, dan `sessions` sudah tersedia native dari migrasi skema.
  * *Opsi High-Throughput (Redis):* Jika menggunakan Redis (`SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`), pastikan sistem operasi server telah menginstal modul PHP Redis dan daemon Redis:
    ```bash
    sudo apt install -y redis-server php-redis  # atau php8.4-redis / php8.3-redis
    sudo systemctl enable --now redis-server
    ```
    Kredensial Redis diatur di `config/database.php` (`redis.cache` + `REDIS_CACHE_DB`), dan driver cache siap digunakan via variabel environment.

### 10.2. Environment Produksi

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://siperapat.lldiktiwilayahx.kemdiktisaintek.go.id

SESSION_DRIVER=redis
SESSION_SECURE_COOKIE=true
SESSION_ENCRYPT=true
CACHE_STORE=redis
QUEUE_CONNECTION=redis
PDF_EXPORT_SLOTS=2

MAIL_MAILER=smtp
MAIL_SCHEME=smtps
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=465
MAIL_USERNAME=<login_smtp_brevo_anda>
MAIL_PASSWORD=<kunci_smtp_brevo_anda>
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="no-reply@lldiktiwilayahx.kemdiktisaintek.go.id"
MAIL_FROM_NAME="SIPERAPAT LLDIKTI Wilayah X"

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=30
```

> **Catatan Firewall Cloud:** Apabila penyedia VPS memblokir port SMTP standar (`587`), gunakan Port `465` dengan `MAIL_SCHEME=smtps` dan `MAIL_ENCRYPTION=ssl` seperti di atas, atau beralih ke jalur HTTP API v3 (`MAIL_MAILER=brevo` + `BREVO_API_KEY=<kunci_api>`) yang berjalan di atas HTTPS Port `443`.

### 10.2b. Guard Kualitas Query (opsional, non-produksi)

Untuk menangkap N+1 dan atribut yang hilang diam-diam saat pengembangan:

```env
# .env.testing atau .env lokal
SIPERAPAT_STRICT_QUERIES=true
```

> `Tests\TestCase` mencetak peringatan bila nama database tidak mengandung `test`
> (lihat §9.3) dan dapat dijadikan ketegakan wajib dengan
> `SIPERAPAT_STRICT_TEST_DB=true`.

### 10.3. Perintah Deployment (Otomatis & Manual)

Repositori menyediakan skrip deployment otomatis [`deploy.sh`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/deploy.sh) yang menangani mode maintenance (`artisan down`), pembaruan kode, instalasi dependensi produksi, kompilasi aset Vite, migrasi database, pembangunan ulang cache (`config:cache`, `route:cache`, `view:cache`), serta restart queue worker (`queue:restart`):

```bash
# Eksekusi otomatis untuk lingkungan produksi (disertai git pull):
./deploy.sh --prod --pull
```

Apabila menjalankan langkah deployment secara manual:

```bash
# 1. Dependency produksi TANPA paket development
composer install --no-dev --optimize-autoloader --classmap-authoritative

# 2. Aset frontend
npm ci
npm run build

# 3. Migrasi
php artisan migrate --force

# 4. Bersihkan & bangun ulang cache konfigurasi, route, dan view
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart

# 5. Symlink storage
php artisan storage:link

# 6. Izin direktori
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

> **`--no-dev` bukan opsional.** Tanpa flag tersebut, paket development ikut
> terpasang, termasuk `psy/psysh` — REPL yang dapat mengeksekusi kode PHP
> arbitrer di mesin produksi. Seluruh generator agenda (`AgendaFactory`, `AttendanceFactory`, dan `php artisan agenda:generate`) telah dirancang menggunakan fungsi native PHP & Laravel (`Arr::random()`, `mt_rand()`, `Str::random()`) serta menempatkan `fakerphp/faker` pada dependensi utama sehingga aman dijalankan meskipun `--no-dev` aktif.

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

### 10.5. Penjadwalan Otomatis (Cron Scheduler)

Tambahkan entri cron sistem agar Laravel Scheduler dieksekusi setiap menit:

```cron
* * * * * cd /var/www/siperapat && php artisan schedule:run >> /dev/null 2>&1
```

Daftar tugas terjadwal yang dikonfigurasi pada [`routes/console.php`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/routes/console.php):
* **`agendas:sync-status` (Setiap Menit, `withoutOverlapping`):** Menutup secara otomatis rapat berstatus `ongoing` yang telah melewati batas waktu selesai menjadi `completed`, serta mencatat transisi ke `activity_logs`.
* **`activity-logs:prune` (Harian pukul 02:15 WIB, `withoutOverlapping`):** Memangkas log audit yang berusia lebih dari 730 hari (2 tahun) secara bertahap (*chunked delete*) untuk menjaga performa tabel `activity_logs`.

### 10.6. Strategi Cadangan (Backup & Disaster Recovery)

| Aspek | Rekomendasi |
|---|---|
| Database | Dump berkala terenkripsi (`pg_dump -Fc -Z 9`) per 6 jam & tengah malam dengan retensi GFS 30 hari. Lihat SOP lengkap di [`BACKUP.md`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/BACKUP.md) |
| Penyimpanan media | Backup rsync/snapshot `storage/app/public` (foto selfie, tanda tangan, surat edaran, foto dokumentasi) — **tidak tercakup backup database** |
| Konfigurasi | Simpan salinan terenkripsi `.env` di vault/cold storage di luar repositori |
| Uji pemulihan | Lakukan restore drill berkala (target RTO $\le$ 30 menit, RPO $\le$ 1 jam); backup yang tak pernah diuji bukan backup |

### 10.7. Verifikasi Pasca-Deployment

```bash
# TLS dan HSTS
curl -I https://siperapat.lldiktiwilayahx.kemdiktisaintek.go.id/up
# Harus 200 + header Strict-Transport-Security

# Area unggahan tidak boleh mengeksekusi skrip
curl -I https://siperapat.lldiktiwilayahx.kemdiktisaintek.go.id/storage/probe.php
# Harus 404

# Debug mode harus mati
grep -E '^APP_DEBUG' .env        # harus false

# Uji konektivitas & pengiriman email transaksional Brevo secara langsung
php artisan mail:test admin@lldiktiwilayahx.kemdiktisaintek.go.id

# Pesan error tidak boleh membocorkan stack trace
curl -s https://siperapat.lldiktiwilayahx.kemdiktisaintek.go.id/agenda-tidak-ada-12345 | grep -i "stack trace\|vendor/laravel" && echo "BAHAYA: debug aktif"
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
