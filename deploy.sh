#!/usr/bin/env bash
# ==============================================================================
# SIPERAPAT - LLDIKTI Wilayah X
# Enterprise Deployment & Code Update Automation Script
# ==============================================================================
# Standar: Enterprise Gov-Tech, Ponytail (Zero-Bloatware), High Availability & Durability
# Penggunaan:
#   ./deploy.sh            # Pembaruan standar lokal / staging
#   ./deploy.sh --prod     # Pembaruan produksi (dengan maintenance mode & no-dev)
#   ./deploy.sh --pull     # Melakukan git pull origin <branch> sebelum deployment
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
            echo "  --pull    Lakukan 'git pull origin <branch> --rebase' sebelum menjalankan migrasi/build/cache"
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
# FAIL-SAFE RECOVERY TRAP (Mencegah Aplikasi Tertinggal dalam Status 503)
# ------------------------------------------------------------------------------
cleanup() {
    local exit_code=$?
    if [ -f "${SCRIPT_DIR}/storage/framework/down" ]; then
        echo -e "\n${CLR_WARN}Memulihkan sistem dari maintenance mode (fail-safe recovery)...${CLR_RESET}"
        php artisan up 2>/dev/null || rm -f "${SCRIPT_DIR}/storage/framework/down" || true
    fi
    if [ "$exit_code" -ne 0 ]; then
        echo -e "\n${CLR_ERR}Deployment terhenti karena kesalahan (Exit Code: ${exit_code}).${CLR_RESET}"
        echo -e "${CLR_WARN}Sistem dipastikan dalam status LIVE untuk mencegah 503 Service Unavailable.${CLR_RESET}"
    fi
}
trap cleanup EXIT INT TERM HUP

# ------------------------------------------------------------------------------
# 1. Pengecekan Lingkungan & Dependensi Sistem (Termasuk Database Probe)
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[1/9] Memeriksa dependensi sistem...${CLR_RESET}"

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

# Auto-discovery NVM / Node jika npm tidak langsung berada di system PATH (misal eksekusi via sudo / non-login shell)
if ! command -v npm &>/dev/null; then
    for NVM_CANDIDATE in "$HOME/.nvm/nvm.sh" "/root/.nvm/nvm.sh" "/var/www/.nvm/nvm.sh"; do
        if [ -s "$NVM_CANDIDATE" ]; then
            export NVM_DIR="$(dirname "$NVM_CANDIDATE")"
            # shellcheck disable=SC1090
            [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh" 2>/dev/null || true
            break
        fi
    done
fi
if ! command -v npm &>/dev/null && [ -d "/usr/local/bin" ]; then
    export PATH="/usr/local/bin:$PATH"
fi

if command -v npm &>/dev/null; then
    NODE_VER=$(node -v 2>/dev/null || echo "unknown")
    NPM_VER=$(npm -v 2>/dev/null || echo "unknown")
    echo -e "      Node / NPM  : ${CLR_SUCCESS}${NODE_VER} / ${NPM_VER}${CLR_RESET}"
else
    echo -e "${CLR_WARN}      Node / NPM  : Tidak ditemukan di PATH! Pastikan Node.js terinstal untuk kompilasi Vite.${CLR_RESET}"
fi

# Database Health Probe: pastikan DB hidup sebelum masuk ke mode maintenance
echo -e "      Memeriksa konektivitas basis data..."
DB_PROBE=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); try { Illuminate\Support\Facades\DB::connection()->getPdo(); echo 'OK'; } catch (\Throwable \$e) { echo 'FAIL: ' . \$e->getMessage(); }" 2>/dev/null || echo "FAIL")
if [[ "$DB_PROBE" != "OK" ]]; then
    echo -e "${CLR_ERR}Kesalahan: Basis data tidak dapat diakses (${DB_PROBE})!${CLR_RESET}"
    echo -e "${CLR_WARN}Membatalkan prosedur sebelum maintenance mode untuk mencegah 503 Service Unavailable.${CLR_RESET}"
    exit 1
fi
echo -e "      Database    : ${CLR_SUCCESS}✓ Koneksi basis data terverifikasi (Online)${CLR_RESET}"

# Cache & Redis Probe: Jika driver cache/session/queue menggunakan redis, pastikan ekstensi & koneksi tersedia
echo -e "      Memeriksa konfigurasi dan konektivitas Cache..."
CACHE_PROBE=$(php -r "
    require 'vendor/autoload.php';
    \$app = require_once 'bootstrap/app.php';
    \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    \$store = config('cache.default');
    if (\$store === 'redis' || config('session.driver') === 'redis' || config('queue.default') === 'redis') {
        if (!extension_loaded('redis') && !class_exists('Redis')) {
            echo 'FAIL_EXT: Ekstensi PHP ext-redis belum terpasang. Jalankan: sudo apt install php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '-redis';
            exit(0);
        }
        try {
            Illuminate\Support\Facades\Redis::connection()->ping();
            echo 'OK_REDIS';
        } catch (\Throwable \$e) {
            echo 'WARN_REDIS: Tidak dapat terhubung ke Redis server (' . \$e->getMessage() . ')';
        }
    } else {
        echo 'OK_STORE:' . \$store;
    }
" 2>/dev/null || echo "FAIL_UNKNOWN")

if [[ "$CACHE_PROBE" == FAIL_EXT* ]]; then
    echo -e "${CLR_ERR}Kesalahan Cache: ${CACHE_PROBE#FAIL_EXT: }${CLR_RESET}"
    echo -e "${CLR_WARN}Saran: Pasang ekstensi redis atau setel CACHE_STORE=database di .env${CLR_RESET}"
    exit 1
elif [[ "$CACHE_PROBE" == WARN_REDIS* ]]; then
    echo -e "${CLR_WARN}Peringatan: ${CACHE_PROBE#WARN_REDIS: }${CLR_RESET}"
    echo -e "${CLR_WARN}Pastikan service redis berjalan: sudo systemctl start redis-server${CLR_RESET}"
elif [[ "$CACHE_PROBE" == "OK_REDIS" ]]; then
    echo -e "      Cache Engine: ${CLR_SUCCESS}✓ Redis terverifikasi (Ekstensi PHP & Redis Server Online)${CLR_RESET}"
else
    echo -e "      Cache Engine: ${CLR_SUCCESS}✓ Cache store terverifikasi (${CACHE_PROBE#OK_STORE:})${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 2. Pembaruan Source Code via Git (Opsional)
# ------------------------------------------------------------------------------
if [ "$WITH_PULL" = true ]; then
    CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")
    echo -e "${CLR_INFO}[2/9] Menarik perubahan kode terbaru (git pull origin ${CURRENT_BRANCH} --rebase)...${CLR_RESET}"
    git pull origin "${CURRENT_BRANCH}" --rebase
else
    echo -e "${CLR_INFO}[2/9] Melewati git pull (menggunakan kode lokal saat ini).${CLR_RESET}"
    echo -e "      ${CLR_WARN}Pemberitahuan: Jalankan './deploy.sh --pull' jika ingin menarik commit terbaru dari remote repository.${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 3. Mode Pemeliharaan (Khusus Mode Produksi)
# ------------------------------------------------------------------------------
if [ "$IS_PROD" = true ]; then
    echo -e "${CLR_INFO}[3/9] Mengaktifkan Maintenance Mode...${CLR_RESET}"
    php artisan down --render="errors.503" --status=503 || true
else
    echo -e "${CLR_INFO}[3/9] Melewati Maintenance Mode (lingkungan non-prod).${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 4. Instalasi & Optimasi Autoloader Composer
# ------------------------------------------------------------------------------
if command -v composer &>/dev/null; then
    echo -e "${CLR_INFO}[4/9] Mengoptimalkan paket dependensi Composer...${CLR_RESET}"
    if [ "$IS_PROD" = true ]; then
        composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --quiet
    else
        composer dump-autoload -o --quiet
    fi
    echo -e "      ${CLR_SUCCESS}✓ Autoloader berhasil dioptimasi.${CLR_RESET}"
else
    echo -e "${CLR_INFO}[4/9] Composer tidak terdeteksi, melewati step dependensi Composer.${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 5. Instalasi Dependensi NPM & Kompilasi Aset Frontend (Vite)
# ------------------------------------------------------------------------------
if command -v npm &>/dev/null; then
    echo -e "${CLR_INFO}[5/9] Menginstal dependensi NPM dan mengompilasi aset frontend (Vite)...${CLR_RESET}"
    if [ "$IS_PROD" = true ]; then
        npm ci --prefer-offline --no-audit --progress=false 2>/dev/null || npm install --no-audit --progress=false
    else
        npm install --no-audit --progress=false
    fi
    npm run build
    echo -e "      ${CLR_SUCCESS}✓ Aset Vite berhasil dikompilasi (public/build/manifest.json terbarui).${CLR_RESET}"
else
    echo -e "${CLR_WARN}[5/9] NPM tidak terdeteksi! Melewati instalasi paket dan kompilasi aset frontend.${CLR_RESET}"
    echo -e "      ${CLR_WARN}Pemberitahuan: Jika terdapat pembaruan tampilan Blade/CSS/JS, jalankan 'npm install && npm run build' secara manual.${CLR_RESET}"
fi

# ------------------------------------------------------------------------------
# 6. Migrasi Skema Basis Data Aman
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[6/9] Menjalankan migrasi basis data...${CLR_RESET}"
php artisan migrate --force --no-interaction
echo -e "      ${CLR_SUCCESS}✓ Skema basis data telah mutakhir.${CLR_RESET}"

# Sinkronisasi sequence PostgreSQL jika driver aktif
DB_DRIVER=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo config('database.default');")
if [ "$DB_DRIVER" = "pgsql" ]; then
    echo -e "      Mendeteksi database PostgreSQL, mengeksekusi pgsql:sync-sequences..."
    php artisan pgsql:sync-sequences || true
fi

# ------------------------------------------------------------------------------
# 7. Pembersihan & Pembuatan Ulang Cache Performa Laravel
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[7/9] Memperbarui dan mengompilasi cache Laravel...${CLR_RESET}"
php artisan config:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

# Pastikan symbolic link storage/public telah aktif
php artisan storage:link 2>/dev/null || true

# Normalisasi hak akses direktori storage, cache, dan build aset
mkdir -p storage/logs public/build
touch storage/logs/laravel.log storage/logs/cron.log 2>/dev/null || true
find storage bootstrap/cache public/build -type d -exec chmod 775 {} + 2>/dev/null || true
find storage bootstrap/cache public/build -type f -exec chmod 664 {} + 2>/dev/null || true
echo -e "      ${CLR_SUCCESS}✓ Seluruh cache performa (Config, Route, View) berhasil dikompilasi.${CLR_RESET}"

# ------------------------------------------------------------------------------
# 8. Verifikasi Layanan Scheduler & Crontab OS
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[8/9] Memeriksa status Cron Scheduler...${CLR_RESET}"
PHP_BIN=$(command -v php || echo "/usr/bin/php")
CRON_ENTRY="* * * * * cd ${SCRIPT_DIR} && ${PHP_BIN} artisan schedule:run >> ${SCRIPT_DIR}/storage/logs/cron.log 2>&1"

if crontab -l 2>/dev/null | grep -q "schedule:run"; then
    echo -e "      ${CLR_SUCCESS}✓ Cron job Laravel scheduler terdeteksi aktif di crontab.${CLR_RESET}"
else
    echo -e "      ${CLR_INFO}Mendaftarkan cron job Laravel scheduler ke crontab...${CLR_RESET}"
    if (crontab -l 2>/dev/null | grep -v -F "schedule:run"; echo "${CRON_ENTRY}") | crontab - 2>/dev/null; then
        echo -e "      ${CLR_SUCCESS}✓ Cron job berhasil disimpan secara otomatis ke crontab.${CLR_RESET}"
    else
        echo -e "      ${CLR_WARN}Tidak dapat menulis otomatis ke crontab. Jalankan perintah manual:${CLR_RESET}"
        echo -e "      ${CLR_INFO}(crontab -l 2>/dev/null; echo \"${CRON_ENTRY}\") | crontab -${CLR_RESET}"
    fi
fi

# ------------------------------------------------------------------------------
# 9. Penutupan Maintenance, Reload Layanan, & Verifikasi Ketersediaan Layanan
# ------------------------------------------------------------------------------
echo -e "${CLR_INFO}[9/9] Memulihkan dan memvalidasi ketersediaan layanan...${CLR_RESET}"

# Jaminan mutlak: hapus status maintenance mode jika masih ada
if [ -f "${SCRIPT_DIR}/storage/framework/down" ]; then
    echo -e "      Mengaktifkan kembali sistem (php artisan up)..."
    php artisan up 2>/dev/null || rm -f "${SCRIPT_DIR}/storage/framework/down" || true
fi

# Me-restart antrean pekerja latar belakang agar memuat bytecode PHP terbaru
echo -e "      Menyegarkan antrean worker (php artisan queue:restart)..."
php artisan queue:restart 2>/dev/null || true

# Me-reload worker PHP-FPM dengan prioritas versi aktif (misal: php8.4-fpm.service)
if command -v systemctl &>/dev/null; then
    PHP_FPM_UNIT=""
    TARGET_PHP_FPM="php${PHP_VERSION}-fpm.service"

    if systemctl is-active --quiet "$TARGET_PHP_FPM" 2>/dev/null || systemctl is-enabled --quiet "$TARGET_PHP_FPM" 2>/dev/null; then
        PHP_FPM_UNIT="$TARGET_PHP_FPM"
    elif systemctl is-active --quiet "php-fpm.service" 2>/dev/null; then
        PHP_FPM_UNIT="php-fpm.service"
    else
        PHP_FPM_UNIT=$(systemctl list-units --type=service --state=running 2>/dev/null | grep -oE "php[0-9\.]*-fpm\.service" | head -n 1 || true)
    fi

    if [ -n "$PHP_FPM_UNIT" ]; then
        echo -e "      Me-reload worker PHP-FPM (${PHP_FPM_UNIT})..."
        if sudo systemctl reload "${PHP_FPM_UNIT}" 2>/dev/null; then
            echo -e "      ${CLR_SUCCESS}✓ Layanan ${PHP_FPM_UNIT} berhasil di-reload (OPcache disegarkan).${CLR_RESET}"
        else
            echo -e "      ${CLR_WARN}Pemberitahuan: Tidak dapat me-reload ${PHP_FPM_UNIT} secara otomatis (memerlukan izin sudo).${CLR_RESET}"
            echo -e "      ${CLR_INFO}Saran: Jalankan manual 'sudo systemctl reload ${PHP_FPM_UNIT}' untuk menyegarkan OPcache.${CLR_RESET}"
        fi
    fi
fi

# Validasi HTTP Health Check Internal
HTTP_PROBE=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$kernel = \$app->make(Illuminate\Contracts\Http\Kernel::class); \$res = \$kernel->handle(Illuminate\Http\Request::create('/up', 'GET')); echo \$res->getStatusCode();" 2>/dev/null || echo "FAIL")

if [ "$HTTP_PROBE" = "200" ]; then
    echo -e "      Health Check: ${CLR_SUCCESS}✓ HTTP 200 OK (/up siap melayani request)${CLR_RESET}"
else
    echo -e "      Health Check: ${CLR_WARN}Status respon '${HTTP_PROBE}'. Menjalankan fail-safe live recovery...${CLR_RESET}"
    php artisan up 2>/dev/null || rm -f "${SCRIPT_DIR}/storage/framework/down" || true
fi

SANITY_CHECK=$(php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); echo 'Agendas: '.App\Models\Agenda::count().' | Users: '.App\Models\User::count().' | Units: '.App\Models\Unit::count();" 2>/dev/null || echo "Data check skipped")
echo -e "      Status Data : ${CLR_BOLD}${SANITY_CHECK}${CLR_RESET}"

echo ""
echo -e "${CLR_SUCCESS}====================================================================${CLR_RESET}"
echo -e "${CLR_SUCCESS}   PROSES DEPLOYMENT & PEMBARUAN KODE SELESAI DENGAN SUKSES!       ${CLR_RESET}"
echo -e "${CLR_SUCCESS}====================================================================${CLR_RESET}"
