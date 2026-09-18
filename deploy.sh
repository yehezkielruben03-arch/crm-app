#!/bin/bash
echo "============================================"
echo " CRM Analys - Deployment ke Hosting"
echo "============================================"
echo ""
echo "Langkah 1: Setup .env"
echo "- Copy .env.example ke .env"
echo "- Edit .env: isi APP_URL, DB_DATABASE, DB_USERNAME, DB_PASSWORD"
echo ""
read -p "Tekan Enter setelah .env siap..."

echo ""
echo "Langkah 2: Generate APP_KEY"
php artisan key:generate

echo ""
echo "Langkah 3: Storage Link"
php artisan storage:link

echo ""
echo "Langkah 4: Migrate Database"
php artisan migrate --force

echo ""
echo "Langkah 5: Seed Role Permission"
php artisan db:seed --class=RolePermissionSeeder --force

echo ""
echo "Langkah 6: Cache Optimization"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize:clear

echo ""
echo "============================================"
echo "  DEPLOYMENT SELESAI!"
echo "============================================"