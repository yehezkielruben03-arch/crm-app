<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateSqliteToMysql extends Command
{
    protected $signature = 'db:migrate-sqlite-to-mysql
        {--target=mysql : Nama koneksi MySQL target di config/database.php}
        {--wipe : Hapus data existing di tabel MySQL sebelum insert}
        {--yes : Lewati konfirmasi}';

    protected $description = 'Salin semua data dari SQLite ke MySQL';

    // Urutan tabel — child tables belakangan biar FK gak error
    private const TABLE_ORDER = [
        'users', 'role_permissions', 'password_reset_tokens',
        'customers', 'customer_contacts', 'sales_targets',
        'purchase_orders', 'purchase_order_items',
        'activity_logs', 'approval_histories', 'notifications',
    ];

    private const SYSTEM_TABLES = [
        'migrations', 'cache', 'cache_locks', 'sessions',
        'jobs', 'job_batches', 'failed_jobs',
    ];

    public function handle(): int
    {
        $targetName = $this->option('target');

        // ── Verifikasi koneksi ──
        try {
            $source = DB::connection('sqlite');
            $source->getPdo();
        } catch (\Throwable $e) {
            $this->error("SQLite tidak bisa dibaca: {$e->getMessage()}");
            return Command::FAILURE;
        }

        try {
            $target = DB::connection($targetName);
            $target->getPdo();
        } catch (\Throwable $e) {
            $this->error("Koneksi MySQL '{$targetName}' gagal: {$e->getMessage()}");
            $this->warn("Pastikan MySQL berjalan dan kredensial di .env benar.");
            $this->warn("Set DB_CONNECTION=mysql, DB_DATABASE=..., DB_USERNAME=..., DB_PASSWORD=...");
            return Command::FAILURE;
        }

        // ── Verifikasi driver ──
        if ($source->getDriverName() !== 'sqlite') {
            $this->error("Source harus SQLite (saat ini: {$source->getDriverName()})");
            return Command::FAILURE;
        }
        if ($target->getDriverName() !== 'mysql') {
            $this->error("Target harus MySQL (saat ini: {$target->getDriverName()})");
            return Command::FAILURE;
        }

        // ── Dapatkan daftar tabel ──
        $sqliteTables = collect($source->select("SELECT name FROM sqlite_master WHERE type='table'"))
            ->pluck('name')
            ->flip();

        $available = collect(self::TABLE_ORDER)
            ->filter(fn($t) => $sqliteTables->has($t));

        if ($available->isEmpty()) {
            $this->warn('Tidak ada tabel data yang ditemukan di SQLite.');
            return Command::FAILURE;
        }

        $this->line("Source: SQLite (" . $source->getDatabaseName() . ")");
        $this->line("Target: MySQL (" . $target->getDatabaseName() . "@{$target->getConfig('host')})");
        $this->line("Tabel yang akan dimigrasi: {$available->count()}");
        $this->newLine();

        if (!$this->option('yes') && !$this->confirm('Lanjutkan migrasi data?', false)) {
            $this->info('Dibatalkan.');
            return Command::SUCCESS;
        }

        // ── Wipe jika diminta ──
        if ($this->option('wipe')) {
            $this->line('Menghapus data existing di MySQL...');
            $target->statement('SET FOREIGN_KEY_CHECKS=0');
            foreach (array_reverse(self::TABLE_ORDER) as $table) {
                if ($sqliteTables->has($table)) {
                    $target->table($table)->truncate();
                }
            }
            $target->statement('SET FOREIGN_KEY_CHECKS=1');
            $this->info('Data existing dihapus.');
        }

        // ── Migrasi data ──
        $totalRows = 0;
        $target->statement('SET FOREIGN_KEY_CHECKS=0');

        $bar = $this->output->createProgressBar($available->count());
        $bar->setFormat("  %current%/%max% [%bar%] %message%\n");
        $bar->start();

        foreach ($available as $table) {
            $rows = $source->table($table)->orderBy('id')->get();

            if ($rows->isEmpty()) {
                $bar->setMessage("{$table}: 0 rows (skip)");
                $bar->advance();
                continue;
            }

            $data = $rows->map(fn($r) => (array) $r)->toArray();

            foreach (array_chunk($data, 100) as $chunk) {
                $target->table($table)->insert($chunk);
            }

            $totalRows += count($data);
            $bar->setMessage("{$table}: " . count($data) . " rows");
            $bar->advance();
        }

        $target->statement('SET FOREIGN_KEY_CHECKS=1');
        $bar->finish();

        $this->newLine(2);
        $this->info("Migrasi selesai! {$totalRows} baris dari {$available->count()} tabel disalin.");
        $this->warn("Jalankan ulang: php artisan cache:clear");

        return Command::SUCCESS;
    }
}
