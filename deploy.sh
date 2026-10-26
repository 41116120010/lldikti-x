#!/usr/bin/env bash
# ==============================================================================
# SIPERAPAT - LLDIKTI Wilayah X
# Enterprise Deployment & Code Update Automation Script
# ==============================================================================
# Standar: Enterprise Gov-Tech, Ponytail (Zero-Bloatware), High Durability
# Penggunaan:
#   ./deploy.sh            # Pembaruan standar lokal / staging
#   ./deploy.sh --prod     # Pembaruan produksi (dengan maintenance mode & no-dev)
#   ./deploy.sh --help     # Menampilkan panduan opsi
# ==============================================================================

set -euo pipefail

# Warna terminal ANSI
CLR_RESET="\033[0m"
CLR_INFO="\033[1;34m"
CLR_SUCCESS="\033[1;32m"
CLR_WARN="\033[1;33m"
CLR_ERR="\033[1;31m"
CLR_BOLD="\033[1m"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

IS_PROD=false
WITH_PULL=false

for arg in "$@"; do
    case "$arg" in
        --prod)
            IS_PROD=true
            ;;
        --pull)
            WITH_PULL=true
            ;;
        --help|-h)
            echo -e "${CLR_BOLD}SIPERAPAT Deploy Automation Script${CLR_RESET}"
            echo "Penggunaan: ./deploy.sh [opsi]"
            echo ""
            echo "Opsi:"
            echo "  --prod    Jalankan prosedur rilis produksi (down, no-dev, optimize, up)"
            echo "  --pull    Lakukan 'git pull --rebase' sebelum menjalankan migrasi/cache"
            echo "  --help    Tampilkan bantuan ini"
            exit 0
            ;;
        *)
            echo -e "${CLR_WARN}Peringatan: Opsi tidak dikenal '$arg'. Diabaikan.${CLR_RESET}"
            ;;
    esac
done

echo -e "${CLR_INFO}====================================================================${CLR_RESET}"
echo -e "${CLR_INFO}       SIPERAPAT — LLDIKTI Wilayah X (Deployment Automation)       ${CLR_RESET}"
echo -e "${CLR_INFO}====================================================================${CLR_RESET}"
echo -e "Direktori Proyek: ${CLR_BOLD}${SCRIPT_DIR}${CLR_RESET}"
echo -e "Waktu Eksekusi   : $(date '+%Y-%m-%d %H:%M:%S %Z')"
echo ""

# ------------------------------------------------------------------------------
# 1. Pengecekan Lingkungan & Dependensi Sistem
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[1/8] Memeriksa dependensi sistem...${CLR_RESET}"

if ! command -v php &>/dev/null; then
    echo -e "${CLR_ERR}Kesalahan: PHP CLI tidak ditemukan di PATH!${CLR_RESET}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo -e "      PHP Version : ${CLR_SUCCESS}${PHP_VERSION}${CLR_RESET}"

if ! command -v composer &>/dev/null; then
    echo -e "${CLR_WARN}      Composer    : Tidak ditemukan di global PATH (lewati composer install otomatis).${CLR_RESET}"
else
    echo -e "      Composer    : ${CLR_SUCCESS}$(composer --version 2>/dev/null | head -n 1)${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 2. Pembaruan Source Code via Git (Opsional)
# ------------------------------------------------------------------------------
if [ "$WITH_PULL" = true ]; then
    echo -e "${CLR_INFO}[2/8] Menarik perubahan kode terbaru (git pull)...${CLR_RESET}"
    git pull --rebase
else
    echo -e "${CLR_INFO}[2/8] Melewati git pull (gunakan flag --pull jika ingin auto-pull).${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 3. Mode Pemeliharaan (Khusus Mode Produksi)
# ------------------------------------------------------------------------------
MAINTENANCE_ACTIVE=false
cleanup() {
    if [ "$MAINTENANCE_ACTIVE" = true ]; then
        echo -e "\n${CLR_WARN}Memulihkan sistem dari maintenance mode...${CLR_RESET}"
        php artisan up || true
    fi
}
trap cleanup EXIT INT TERM

if [ "$IS_PROD" = true ]; then
    echo -e "${CLR_INFO}[3/8] Mengaktifkan Maintenance Mode...${CLR_RESET}"
    php artisan down --render="errors::503" || true
    MAINTENANCE_ACTIVE=true
else
    echo -e "${CLR_INFO}[3/8] Melewati Maintenance Mode (lingkungan non-prod).${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 4. Instalasi & Optimasi Autoloader Composer
# ------------------------------------------------------------------------------
if command -v composer &>/dev/null; then
    echo -e "${CLR_INFO}[4/8] Mengoptimalkan paket dependensi Composer...${CLR_RESET}"
    if [ "$IS_PROD" = true ]; then
        composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet
    else
        composer dump-autoload -o --quiet
    fi
    echo -e "      ${CLR_SUCCESS}✓ Autoloader berhasil dioptimasi.${CLR_RESET}"
else
    echo -e "${CLR_INFO}[4/8] Composer tidak terdeteksi, melewati step dependensi.${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 5. Migrasi Skema Basis Data Aman
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[5/8] Menjalankan migrasi basis data...${CLR_RESET}"
php artisan migrate --force --no-interaction
echo -e "      ${CLR_SUCCESS}✓ Skema basis data telah mutakhir.${CLR_RESET}"

# Sinkronisasi sequence PostgreSQL jika driver aktif
DB_DRIVER=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config('database.default');")
if [ "$DB_DRIVER" = "pgsql" ]; then
    echo -e "      Mendeteksi database PostgreSQL, mengeksekusi pgsql:sync-sequences..."
    php artisan pgsql:sync-sequences || true
fi

# ------------------------------------------------------------------------------
# 6. Pembersihan & Pembuatan Ulang Cache Performa Laravel
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[6/8] Memperbarui dan mengompilasi cache Laravel...${CLR_RESET}"
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

# Pastikan symbolic link storage/public telah aktif
php artisan storage:link 2>/dev/null || true
echo -e "      ${CLR_SUCCESS}✓ Seluruh cache performa (Config, Route, View) berhasil dikompilasi.${CLR_RESET}"

# ------------------------------------------------------------------------------
# 7. Verifikasi Layanan Scheduler & Crontab OS
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[7/8] Memeriksa status Cron Scheduler...${CLR_RESET}"
CRON_ENTRY="* * * * * cd ${SCRIPT_DIR} && php artisan schedule:run >> /dev/null 2>&1"

if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo -e "      ${CLR_SUCCESS}✓ Cron job Laravel scheduler terdeteksi aktif di crontab.${CLR_RESET}"
else
    echo -e "      ${CLR_WARN}Perhatian: Entri crontab belum terdeteksi.${CLR_RESET}"
    echo -e "      Untuk mengaktifkan otomasi auto-close rapat dan retensi log, pasang baris ini:"
    echo -e "      ${CLR_BOLD}${CRON_ENTRY}${CLR_RESET}"
    echo -e "      Atau jalankan perintah cepat berikut:"
    echo -e "      ${CLR_INFO}(crontab -l 2>/dev/null; echo \"${CRON_ENTRY}\") | crontab -${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 8. Penutupan Maintenance & Ringkasan Status
# ------------------------------------------------------------------------------
if [ "$MAINTENANCE_ACTIVE" = true ]; then
    echo -e "${CLR_INFO}[8/8] Menonaktifkan Maintenance Mode...${CLR_RESET}"
    php artisan up
    MAINTENANCE_ACTIVE=false
else
    echo -e "${CLR_INFO}[8/8] Memverifikasi integritas sistem...${CLR_RESET}"
fi

SANITY_CHECK=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo 'Agendas: '.App\Models\Agenda::count().' | Users: '.App\Models\User::count().' | Units: '.App\Models\Unit::count();")
echo -e "      Status Data: ${CLR_BOLD}${SANITY_CHECK}${CLR_RESET}"

echo ""
echo -e "${CLR_SUCCESS}====================================================================${CLR_RESET}"
echo -e "${CLR_SUCCESS}   PROSES DEPLOYMENT & PEMBARUAN KODE SELESAI DENGAN SUKSES!       ${CLR_RESET}"
echo -e "${CLR_SUCCESS}====================================================================${CLR_RESET}"
