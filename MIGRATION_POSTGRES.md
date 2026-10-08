# Panduan Resmi Migrasi Basis Data: MySQL/MariaDB ke PostgreSQL
## Sistem Pencatatan Kehadiran Rapat Berbasis Web (SIPERAPAT - LLDIKTI)

Dokumen ini merupakan pedoman baku rekayasa perangkat lunak enterprise untuk melakukan migrasi basis data sistem SIPERAPAT dari MySQL/MariaDB ke PostgreSQL 15+ dengan garansi integritas data penuh (*zero data corruption*), performa tinggi, dan ketersediaan tinggi (*high availability*).

---

## 1. Arsitektur & Kesiapan Codebase (Agnostic Hardening)

Codebase SIPERAPAT telah diselaraskan agar 100% database-agnostic dan bebas dari dependensi eksklusif MySQL:
1. **Advisory Locking Transaksional:** Menggunakan fungsi native `pg_advisory_xact_lock(hashtext(?))` pada [`app/Services/AgendaConflictService.php`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/app/Services/AgendaConflictService.php) yang secara otomatis dilepas oleh PostgreSQL saat transaksi commit/rollback.
2. **Boolean Expression:** Menggunakan sintaks ANSI SQL murni (`CASE WHEN is_active THEN 1 ELSE 0 END`) pada [`app/Http/Controllers/UserController.php`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/app/Http/Controllers/UserController.php).
3. **Pencarian Teks Case-Insensitive:** Seluruh fitur pencarian menggunakan method native Laravel `whereLike()` dan `orWhereLike()` yang secara otomatis dikompilasi menjadi operator `ILIKE` di PostgreSQL dan `LIKE` di MySQL.
4. **Verifikasi Timezone Booting:** Telah disesuaikan di [`app/Providers/AppServiceProvider.php`](file:///home/daffiq/Documents/Semester-5/lldikti-x/lldikti-x/app/Providers/AppServiceProvider.php) menggunakan `SET TIME ZONE` dan `EXTRACT(TIMEZONE_HOUR FROM NOW())` di PostgreSQL.

---

## 2. Persyaratan Lingkungan Server (Prerequisites)

### 2.1. Server PostgreSQL
* **Engine:** PostgreSQL versi 15.0 atau lebih tinggi (direkomendasikan v16 LTS).
* **Encoding:** `UTF8`
* **Collation:** `en_US.UTF-8` atau `C.UTF-8`

### 2.2. Server Aplikasi (PHP)
Pastikan ekstensi PHP PostgreSQL telah terpasang dan aktif:
```bash
# Ubuntu / Debian
sudo apt update
sudo apt install -y php8.3-pgsql postgresql-client

# Verifikasi status ekstensi
php -m | grep -E "pdo_pgsql|pgsql"
# Output wajib:
# pdo_pgsql
# pgsql
```

---

## 3. Langkah Demi Langkah Migrasi Basis Data

### Langkah 1: Persiapan Database & Pengguna PostgreSQL
Masuk ke terminal server database PostgreSQL dan buat database baru beserta hak aksesnya:

```sql
-- Masuk sebagai superuser postgres
sudo -u postgres psql

-- Buat database dan role pengguna aplikasi
CREATE USER siperapat_user WITH ENCRYPTED PASSWORD 'KatasandiKuat_2026!#';
CREATE DATABASE siperapat_db OWNER siperapat_user ENCODING 'UTF8';

-- Berikan otorisasi skema public
GRANT ALL PRIVILEGES ON DATABASE siperapat_db TO siperapat_user;
\c siperapat_db
GRANT ALL ON SCHEMA public TO siperapat_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO siperapat_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO siperapat_user;
\q
```

---

### Langkah 2: Konfigurasi Environment Aplikasi Laravel (`.env`)
Ubah konfigurasi koneksi basis data di berkas `.env` aplikasi:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=siperapat_db
DB_USERNAME=siperapat_user
DB_PASSWORD=KatasandiKuat_2026!#
DB_SCHEMA=public
DB_SSLMODE=prefer
```

---

### Langkah 3: Eksekusi DDL Migrasi Skema
Jalankan migrasi Laravel untuk membangun seluruh tabel, index, foreign keys, dan constraints di PostgreSQL:

```bash
php artisan migrate --force
```

Verifikasi tabel yang terbentuk:
```bash
php artisan db:table users
php artisan db:table agendas
```

---

### Langkah 4: Pemindahan Data (Dua Skenario)

#### Skenario A: Instance Bersih Baru (Zero-Agenda Pristine Deployment)
Jika lingkungan baru dimulai dari kondisi awal tanpa agenda riil, jalankan seeder resmi instansi:
```bash
php artisan db:seed --class=DatabaseSeeder --force
```
*Hasil: 12 Unit Kerja resmi dan 79 Pegawai ASN resmi LLDIKTI langsung terisi dengan sempurna.*

#### Skenario B: Migrasi Data Operasional Eksisting dari MySQL (ETL via `pgloader`)
Jika sudah terdapat riwayat rapat, presensi, notulensi, dan log di MySQL yang harus dipindahkan ke PostgreSQL, gunakan tool standar industri **`pgloader`**:

1. Pasang pgloader:
   ```bash
   sudo apt install -y pgloader
   ```

2. Buat berkas konfigurasi `migration.load`:
   ```lisp
   LOAD DATABASE
        FROM mysql://root:password@127.0.0.1:3306/lldikti_db
        INTO postgresql://siperapat_user:KatasandiKuat_2026!#@127.0.0.1:5432/siperapat_db

   WITH include drop, create tables, no truncate,
        create indexes, reset sequences, foreign keys

   SET maintenance_work_mem to '512MB', work_mem to '64MB'

   CAST type tinyint when (= 1 precision) to boolean drop typemod using tinyint-to-boolean,
        type datetime to timestamp drop typemod,
        type date to date drop typemod;
   ```

3. Eksekusi proses ETL:
   ```bash
   pgloader migration.load
   ```

---

### Langkah 5: Sinkronisasi dan Validasi Sequence PostgreSQL
Setelah data dipindahkan, pastikan sequence ID (`nextval()`) di PostgreSQL telah disinkronkan ke nilai ID tertinggi untuk mencegah error *duplicate key violation* saat insert data baru:

```sql
-- Jalankan di psql siperapat_db
SELECT setval('users_id_seq', COALESCE((SELECT MAX(id) FROM users), 1));
SELECT setval('units_id_seq', COALESCE((SELECT MAX(id) FROM units), 1));
SELECT setval('agendas_id_seq', COALESCE((SELECT MAX(id) FROM agendas), 1));
SELECT setval('attendances_id_seq', COALESCE((SELECT MAX(id) FROM attendances), 1));
SELECT setval('agenda_units_id_seq', COALESCE((SELECT MAX(id) FROM agenda_units), 1));
SELECT setval('agenda_documentations_id_seq', COALESCE((SELECT MAX(id) FROM agenda_documentations), 1));
SELECT setval('activity_logs_id_seq', COALESCE((SELECT MAX(id) FROM activity_logs), 1));
SELECT setval('personal_access_tokens_id_seq', COALESCE((SELECT MAX(id) FROM personal_access_tokens), 1));
```

---

### Langkah 6: Validasi Rekonsiliasi & Uji Integritas
Lakukan pemeriksaan integritas data:
1. **Jumlah Baris Data:**
   Bandingkan jumlah baris antara database lama (MySQL) dan baru (PostgreSQL):
   ```sql
   SELECT 'users' AS table_name, COUNT(*) FROM users
   UNION ALL
   SELECT 'units', COUNT(*) FROM units
   UNION ALL
   SELECT 'agendas', COUNT(*) FROM agendas
   UNION ALL
   SELECT 'attendances', COUNT(*) FROM attendances;
   ```
2. **Uji Keselarasan Aplikasi:**
   Jalankan automated test suite Laravel:
   ```bash
   php artisan test
   ```
3. **Cek Log Aplikasi:**
   Pastikan tidak ada peringatan timezone atau syntax error pada `storage/logs/laravel.log`.

---

## 4. Rollback Plan (Rencana Darurat Pemulihan)

Jika selama proses *cut-over* terjadi kendala kritis yang tidak dapat diselesaikan dalam 15 menit:
1. Kembalikan variabel koneksi di berkas `.env` ke MySQL/MariaDB:
   ```env
   DB_CONNECTION=mysql
   DB_PORT=3306
   DB_DATABASE=lldikti_db
   ```
2. Jalankan pembersihan cache konfigurasi:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```
3. Restart PHP-FPM dan Nginx/Web Server. Layanan kembali berjalan normal pada basis data sebelumnya tanpa data hilang.
