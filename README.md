# SIPERAPAT — Sistem Informasi Presensi & Rapat Kedinasan
**Lembaga Layanan Pendidikan Tinggi (LLDIKTI) Wilayah X**  
*Kementerian Pendidikan Tinggi, Sains, dan Teknologi Republik Indonesia*

---

## 1. Ringkasan Sistem

**SIPERAPAT** adalah platform tata kelola rapat kedinasan terintegrasi untuk lingkungan LLDIKTI Wilayah X. Sistem mendigitalkan seluruh siklus rapat—mulai dari penjadwalan agenda, penerbitan surat undangan/edaran, verifikasi kehadiran biometrik, penulisan notulensi, hingga pelaporan resmi (PDF, DOCX, CSV)—melalui dua antarmuka yang saling terhubung:

1. **Aplikasi Web (Monolith Laravel 13 + Blade + Tailwind CSS v4):** Portal operasional penuh untuk seluruh siklus rapat, termasuk editor naskah Surat Edaran Digital dan kustomisasi kop/penandatangan Berita Acara.
2. **REST API Mobile v1 (Laravel Sanctum):** Layanan *stateless JSON API* (`/api/v1`) yang melayani aplikasi klien Android/iOS (`siperapat` React Native / Expo).

---

## 2. Arsitektur & Standar Rekayasa

* **Arsitektur Ramping (*Zero-Bloatware* / YAGNI):** Memaksimalkan fitur bawaan Laravel 13 (*Policies*, *Form Requests*, *Eloquent Scopes*, *Custom Casts*, dan *Service Classes*) tanpa dependensi pihak ketiga yang tidak esensial.
* **Otorisasi Terpusat:** Pemisahan hak akses berbasis peran (`administrator`, `admin`, `staff`) serta pembatasan lingkup unit kerja (`unit_id`) melalui `AgendaPolicy`, `AttendancePolicy`, `UserPolicy`, `UnitPolicy`, dan `ActivityLogPolicy`.
* **Integritas Transaksi & Konkurensi:**
  * Mutasi majemuk (presensi, unggah berkas, pencatatan audit log) dibungkus dalam `DB::transaction()`.
  * Deteksi bentrok ruang rapat menggunakan kolom ternormalisasi terindeks (`lokasi_ruang_normalized`) serta mekanisme *advisory lock* database (`pg_advisory_lock` di PostgreSQL / `GET_LOCK` di MySQL).
  * Validasi konsistensi zona waktu aplikasi dan sesi database (`Asia/Jakarta` / WIB) pada saat *boot* melalui `AppServiceProvider`.
* **Keamanan Berkas Biometrik:** Swafoto wajah dan tanda tangan digital disimpan pada disk privat/publik non-eksekusi (`storage/app/public`) dan disajikan melalui *streaming endpoint* terotorisasi, bukan URL publik terbuka.

---

## 3. Tech Stack

| Lapisan | Teknologi & Versi |
|---|---|
| **Backend** | PHP 8.3+ / 8.4, Laravel `^13.8`, Laravel Sanctum `^4.3` |
| **Frontend Web** | Laravel Blade, Tailwind CSS v4, Vite, Alpine.js / Vanilla JS |
| **Ikon Antarmuka** | Heroicons / Phosphor Icons (SVG) |
| **Basis Data** | PostgreSQL 15+ (utama produksi), MySQL 8.0+ / MariaDB 10.11+ (kompatibel), SQLite 3.35+ |
| **Ekspor Dokumen** | `dompdf/dompdf` (`^3.1`) untuk PDF, `phpoffice/phpword` (`^0.18.3`) untuk DOCX, CSV UTF-8 BOM |
| **Email Transaksional** | Brevo SMTP Relay (`465` Implicit TLS / `587` STARTTLS) & Brevo HTTP API v3 (`443` HTTPS) |
| **Pengujian** | PHPUnit `^12.5` |

---

## 4. Fitur Inti Sistem

* **Autentikasi & Pemulihan Sandi Multi-Identifier:** Login menggunakan **NIP (18 digit)** atau **Username**. Fitur lupa kata sandi menerima NIP, Username, atau Email Kedinasan dengan pengiriman tautan reset melalui Brevo serta pencabutan otomatis seluruh token aktif (`$user->tokens()->delete()`).
* **Siklus Status Agenda Otomatis:** State machine tervalidasi (`draft` → `scheduled` → `ongoing` → `completed` / `cancelled`). Sesi presensi dapat dibuka paling cepat 15 menit sebelum waktu mulai (`AGENDA_START_WINDOW_MINUTES`) dan ditutup otomatis setiap menit oleh scheduler `php artisan agendas:sync-status` setelah melewati batas akhir plus masa tenggang (`AGENDA_CLOSE_GRACE_MINUTES`).
* **3 Mode Sasaran Peserta (`target_type`):** Seluruh unit (`all_units`), unit tertentu (`selected_units`), atau pegawai ASN tertentu (`selected_users`), beserta delegasi lintas unit untuk **Pemimpin Rapat (`pimpinan_id`)** dan **Notulis (`notulis_id`)**.
* **Surat Edaran Hibrida:** Mendukung unggahan berkas resmi (`upload`: PDF/gambar maks. 5 MB) maupun penyusunan naskah digital langsung di portal web (`digital`).
* **Presensi Interaktif:** Kompresi swafoto di sisi klien (maks. 512 KB di server) + kanvas tanda tangan digital PNG + halaman tanda terima kehadiran.
* **Notulensi, Dokumentasi & Pelaporan:** Editor notulensi dan kesimpulan bersanitasi XSS, galeri foto dokumentasi (maks. 10 foto per unggahan, @4 MB), analitik partisipasi per unit, serta ekspor Berita Acara (PDF A4, DOCX valid ZIP, CSV daftar hadir, dan CSV ringkasan 13 kolom anti-CWE-1236).
* **Audit Trail Sistem:** Pencatatan seluruh aksi sensitif pada `activity_logs` dengan pemangkasan otomatis data berusia di atas 730 hari melalui `php artisan activity-logs:prune`.

---

## 5. Instalasi Pengembangan Lokal

### Prasyarat
* PHP `>= 8.3` dengan ekstensi `pdo_pgsql` / `pdo_mysql`, `gd`, `zip`, `mbstring`, `fileinfo`
* Composer 2.x, Node.js 20 LTS + NPM
* PostgreSQL 15+ (disarankan) atau MySQL 8.0+ / MariaDB 10.11+

### Langkah Setup
```bash
# 1. Masuk ke direktori proyek dan pasang dependensi
composer install
npm install

# 2. Siapkan environment lokal dan kunci aplikasi
cp .env.example .env
php artisan key:generate

# 3. Sesuaikan koneksi DB_* dan MAIL_* di berkas .env, lalu jalankan migrasi & seeder resmi
php artisan migrate --seed
php artisan storage:link

# 4. Sinkronkan sequence primary key jika menggunakan PostgreSQL
php artisan pgsql:sync-sequences

# 5. Jalankan server pengembangan dan kompilasi aset frontend
npm run build
php artisan serve
```

### Data Awal Resmi (`DatabaseSeeder`)
`DatabaseSeeder` hanya mengisi master data resmi instansi tanpa agenda dummy (*pristine zero-agenda state*):
* **`UnitSeeder`:** 12 entitas unit kerja resmi LLDIKTI Wilayah X (11 bagian/subbagian/pokja struktural dan Pokja Penjaminan Mutu & Akreditasi).
* **`UserSeeder`:** 69 akun pegawai ASN resmi aktif dengan kata sandi awal `Password123!`, terdiri dari:
  * **1 Administrator Utama Sistem:** `Apandi, ST` (NIP `198804022019031016`, username `apandi`, lintas unit).
  * **68 Pegawai ASN:** Terdistribusi pada unit kerja masing-masing.

Untuk simulasi antarmuka atau uji beban di lingkungan non-produksi, jalankan secara manual:
```bash
# Mengisi 12 contoh agenda rapat kedinasan beserta sampel presensi
php artisan db:seed --class=AgendaSeeder

# Atau membangkitkan agenda simulasi dalam jumlah kustom (aman di mode --no-dev)
php artisan agenda:generate 20 --attendees=15
```

---

## 6. Perintah Pemeliharaan & Pengujian Otomatis

```bash
# Menjalankan Unit Test terisolasi (tanpa ketergantungan koneksi DB)
php artisan test --testsuite=Unit

# Menjalankan seluruh test suite (membutuhkan database uji terkonfigurasi di .env / .env.testing)
php artisan test

# Diagnostik pengiriman email Brevo
php artisan mail:test tujuan@domain.go.id

# Menutup agenda melewati batas waktu & memangkas log audit > 730 hari (dijalankan cron)
php artisan agendas:sync-status
php artisan activity-logs:prune --days=730
```

> **Peringatan Database Uji:** `Tests\TestCase` menggunakan `DatabaseTransactions` dan menolak eksekusi apabila nama database aktif tidak terisolasi untuk pengujian. Rincian arsitektur pengujian tersedia di [AUDIT_CODEBASE.md](AUDIT_CODEBASE.md).

---

## 7. Peta Dokumentasi Ekosistem

Setiap dokumen memiliki cakupan tunggal tanpa duplikasi informasi:

| Dokumen | Cakupan Utama (Single Source of Truth) |
|---|---|
| [PRD.md](PRD.md) | Kebutuhan bisnis, matriks RBAC, aturan domain, paritas Web vs Mobile, dan NFR |
| [ERD.md](ERD.md) | Diagram relasi, kamus tabel 24 migrasi, indeks, dan aturan integritas data |
| [API.md](API.md) | Kontrak resmi REST API `/api/v1`, rate limiter, autentikasi Sanctum, dan payload |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Instalasi VPS Linux produksi, Nginx HTTPS, PHP-FPM, Cron, Supervisor, dan `.env` |
| [MIGRATION_POSTGRES.md](MIGRATION_POSTGRES.md) | Prosedur migrasi ETL MySQL/MariaDB ke PostgreSQL (`pgloader`) & sinkronisasi sequence |
| [BACKUP.md](BACKUP.md) | Kebijakan SLA (RPO/RTO), retensi GFS, skrip backup terenkripsi, dan prosedur *restore* |
| [AUDIT_CODEBASE.md](AUDIT_CODEBASE.md) | Ringkasan hasil audit keamanan, optimasi performa, dan status penjaminan mutu terkini |
| [AGENTS.md](AGENTS.md) | Pedoman baku rekayasa kode, arsitektur Laravel, UI/UX, dan larangan pengembangan |
| `demo-magang/PANDUAN_DEMO.md` | Panduan praktis menjalankan demonstrasi lokal pada perangkat Windows |
| **Klien Mobile (`siperapat`)** | Lihat `README.md` dan `docs/api-contract-alignment.md` pada repositori klien mobile |
