#!/bin/bash
set -e

echo "========================================================"
echo "   🚀 DEPLOYMENT CRM ANALYST - PT. PEDIA TECHNOLOGY    "
echo "========================================================"

# Hapus file compose.yaml bawaan Laravel Sail jika ada agar tidak bentrok
rm -f compose.yaml

# Pastikan file .env ada
if [ ! -f .env ]; then
    echo "📋 Menyalin konfigurasi .env.docker menjadi .env..."
    cp .env.docker .env
fi

# Matikan container Sail / lama jika ada yang bentrok
docker compose down 2>/dev/null || true

# Jalankan container Docker produksi
echo "🐳 Membangun dan menyalakan container (App, Web Nginx, MySQL)..."
docker compose -f docker-compose.yml up -d --build

# Install vendor jika belum ada
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo "📦 Menginstall dependensi PHP (vendor) di dalam container..."
    docker compose -f docker-compose.yml exec -T app composer install --no-dev --optimize-autoloader
fi

# Tunggu database MySQL siap menerima koneksi
echo "⏳ Menunggu database MySQL siap menerima koneksi..."
retries=30
while [ $retries -gt 0 ]; do
    if docker compose -f docker-compose.yml exec -T db mysqladmin ping -h 127.0.0.1 --silent >/dev/null 2>&1; then
        echo "✓ Database MySQL siap!"
        break
    fi
    echo "   Database sedang booting, menunggu 3 detik ($retries sisa percobaan)..."
    sleep 3
    retries=$((retries - 1))
done

# Eksekusi migrasi & konfigurasi di dalam container
echo "⏳ Menjalankan migrasi database di container app..."
docker compose -f docker-compose.yml exec -T app php artisan migrate --force
docker compose -f docker-compose.yml exec -T app php artisan db:seed --class=RolePermissionSeeder --force
docker compose -f docker-compose.yml exec -T app php artisan storage:link || true
docker compose -f docker-compose.yml exec -T app php artisan optimize

SERVER_IP=$(hostname -I | awk '{print $1}')

echo ""
echo "========================================================"
echo "   ✅ DEPLOYMENT BERHASIL & AKTIF 24 JAM!              "
echo "========================================================"
echo "Aplikasi CRM sudah berjalan penuh di dalam Docker."
echo ""
echo "👉 Buka Browser di komputer/HP mana saja di jaringan:"
echo "   http://${SERVER_IP}"
echo ""
echo "Perintah penting Docker:"
echo " • Cek status container : docker compose ps"
echo " • Cek log aplikasi     : docker compose logs -f app"
echo " • Matikan container    : docker compose down"
echo " • Nyalakan kembali     : docker compose up -d"
echo "========================================================"