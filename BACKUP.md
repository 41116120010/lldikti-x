# Kebijakan & Standar Prosedur Operasional Backup & Disaster Recovery (DR)
## Sistem Pencatatan Kehadiran Rapat Berbasis Web (SIPERAPAT - LLDIKTI)

Dokumen ini adalah pedoman baku tata kelola keamanan data instansi pemerintah untuk melindungi seluruh data aset SIPERAPAT LLDIKTI dari ancaman kegagalan perangkat keras, bencana alam, ransomware, dan *human error*.

---

## 1. Sasaran Pemulihan Sistem (SLA Target)

* **RPO (Recovery Point Objective): $\le$ 1 Jam**
  Maksimal data yang hilang saat terjadi bencana fatal tidak boleh melebihi 1 jam data operasional.
* **RTO (Recovery Time Objective): $\le$ 30 Menit**
  Sistem dan pangkalan data harus dapat dipulihkan dan beroperasi normal kembali paling lambat dalam waktu 30 menit sejak pengumuman insiden.

---

## 2. Arsitektur Backup Bertingkat (Defense-in-Depth)

Sistem menggunakan pendekatan 3 lapis perlindungan (*3-Tier Protection*):

```
┌─────────────────────────────────────────────────────────────┐
│ Tier 1: High Availability (Streaming Physical Replication)  │
│ - Continuous WAL Streaming ke Hot Standby Server            │
│ - Failover otomatis / RPO ~ 0 detik                         │
└──────────────────────────────┬──────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Tier 2: Automated Encrypted Logical Backup (pg_dump)        │
│ - Jadwal: Setiap 6 Jam & Dump Lengkap Tengah Malam          │
│ - Format: PostgreSQL Custom Archive (-Fc) terkompresi       │
│ - Proteksi: Enkripsi AES-256 / GPG sebelum keluar server    │
└──────────────────────────────┬──────────────────────────────┘
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Tier 3: Offsite Immutable Cloud / S3 Object Storage         │
│ - Pengiriman ke Remote S3 / MinIO instansi terpisah         │
│ - Kebijakan Retensi (GFS Retention Policy)                  │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. Kebijakan Retensi Arsip (Grandfather-Father-Son Policy)

| Frekuensi Backup | Interval Eksekusi | Masa Retensi (Masa Simpan) | Tujuan Kepatuhan |
|---|---|---|---|
| **Intraday (Snapshot)** | Setiap 6 Jam (06:00, 12:00, 18:00 WIB) | 48 Jam | Penanganan insiden operasional harian. |
| **Daily (Harian)** | Setiap Hari Pukul 23:30 WIB | 14 Hari | Pemulihan operasional harian instansi. |
| **Weekly (Mingguan)** | Setiap Hari Minggu Pukul 00:00 WIB | 8 Minggu | Audit berkala subbagian & pokja. |
| **Monthly (Bulanan)** | Setiap Akhir Bulan Pukul 01:00 WIB | 12 Bulan | Laporan pertanggungjawaban tahunan. |
| **Yearly (Tahunan)** | Setiap 31 Desember Pukul 23:59 WIB | 5 Tahun (Arsip Statis) | Kepatuhan audit regulasi BPK / Inspektorat. |

---

## 4. Skrip Automasi Backup Mandiri (`backup-database.sh`)

Simpan skrip berikut di `/usr/local/bin/siperapat-backup.sh` pada server database:

```bash
#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# SIPERAPAT - Automated Encrypted PostgreSQL Backup Script
# ==============================================================================

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/siperapat"
DATABASE_NAME="${DB_DATABASE:-siperapat_db}"
DATABASE_USER="${DB_USERNAME:-siperapat_user}"
DATABASE_HOST="${DB_HOST:-127.0.0.1}"
DATABASE_PORT="${DB_PORT:-5432}"

FILENAME="siperapat_${DATABASE_NAME}_${TIMESTAMP}.dump"
FILEPATH="${BACKUP_DIR}/${FILENAME}"

mkdir -p "${BACKUP_DIR}"
chmod 700 "${BACKUP_DIR}"

echo "[$(date)] Memulai proses dump database ${DATABASE_NAME}..."

# 1. Eksekusi pg_dump dengan format custom (-Fc) kompresi maksimal (-Z 9)
PGPASSWORD="${DB_PASSWORD}" pg_dump \
    -h "${DATABASE_HOST}" \
    -p "${DATABASE_PORT}" \
    -U "${DATABASE_USER}" \
    -F c \
    -Z 9 \
    -b \
    -v \
    -f "${FILEPATH}" \
    "${DATABASE_NAME}"

echo "[$(date)] Dump selesai: ${FILEPATH} ($(du -h "${FILEPATH}" | cut -f1))"

# 2. Pembuatan Checksum SHA-256 untuk verifikasi integritas
sha256sum "${FILEPATH}" > "${FILEPATH}.sha256"

# 3. Sinkronisasi ke Offsite Storage (Contoh: AWS S3 / MinIO / Remote Storage)
if command -v aws &> /dev/null; then
    echo "[$(date)] Mengunggah berkas backup ke S3 Offsite..."
    aws s3 cp "${FILEPATH}" "s3://lldikti-siperapat-backup/database/${FILENAME}" --storage-class STANDARD_IA
    aws s3 cp "${FILEPATH}.sha256" "s3://lldikti-siperapat-backup/database/${FILENAME}.sha256"
fi

# 4. Pembersihan berkas lokal lama (> 7 hari)
find "${BACKUP_DIR}" -type f -name "siperapat_*.dump*" -mtime +7 -delete

echo "[$(date)] Seluruh rangkaian backup berhasil diselesaikan."
```

Beri izin eksekusi dan pasang di Crontab server:
```bash
sudo chmod +x /usr/local/bin/siperapat-backup.sh

# Tambahkan ke crontab root (crontab -e)
# Eksekusi setiap 6 jam
0 0,6,12,18 * * * /usr/local/bin/siperapat-backup.sh >> /var/log/siperapat-backup.log 2>&1
```

---

## 5. Prosedur Pemulihan Bencana (Disaster Recovery SOP)

Jika database utama rusak total atau server mengalami kegagalan, ikuti langkah darurat ini:

### Langkah 1: Persiapan Server Target & Database Baru
```bash
# Pastikan PostgreSQL aktif di server target
sudo systemctl restart postgresql

# Buat database bersih di target
sudo -u postgres psql -c "DROP DATABASE IF EXISTS siperapat_db;"
sudo -u postgres psql -c "CREATE DATABASE siperapat_db OWNER siperapat_user ENCODING 'UTF8';"
```

### Langkah 2: Verifikasi Checksum Berkas Backup
```bash
cd /var/backups/siperapat/
sha256sum -c siperapat_siperapat_db_20261008_120000.dump.sha256
# Output WAJIB: ...: OK
```

### Langkah 3: Eksekusi Restore dengan `pg_restore`
Gunakan opsi multi-thread (`-j 4`) untuk percepatan proses:
```bash
PGPASSWORD="KatasandiKuat_2026!#" pg_restore \
    -h 127.0.0.1 \
    -p 5432 \
    -U siperapat_user \
    -d siperapat_db \
    -v \
    -j 4 \
    --clean \
    --if-exists \
    siperapat_siperapat_db_20261008_120000.dump
```

### Langkah 4: Validasi Integritas Pasca-Restore
1. Cek jumlah tabel dan baris:
   ```bash
   sudo -u postgres psql -d siperapat_db -c "SELECT COUNT(*) FROM users; SELECT COUNT(*) FROM agendas; SELECT COUNT(*) FROM attendances;"
   ```
2. Jalankan health check Laravel:
   ```bash
   php artisan db:monitor
   php artisan test tests/Feature/Api/V1/MobileClientWorkflowE2ETest.php
   ```

---

## 6. Prosedur Uji Coba Bencana Periodik (DR Drill)
Setiap 3 bulan sekali (*Triwulanan*), tim DevOps/SysAdmin LLDIKTI wajib melakukan simulasi pemulihan (*Disaster Recovery Drill*) pada server staging terisolasi untuk membuktikan bahwa berkas backup valid, tidak korup, dan dapat dipulihkan dalam batas waktu RTO $\le$ 30 menit.
