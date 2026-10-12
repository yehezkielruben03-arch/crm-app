<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ResetRfqTransactions extends Command
{
    protected $signature = 'crm:reset-rfq {--no-backup : Lewati pembuatan backup otomatis} {--force : Jalankan tanpa konfirmasi}';
    protected $description = 'Backup dan bersihkan seluruh transaksi testing RFQ, Quotation, PO, dan item tanpa menghapus customer';

    public function handle()
    {
        $noBackup = $this->option('no-backup');
        $force = $this->option('force');

        if (!$force && !$this->confirm('Apakah Anda yakin ingin MERESET SEMUA TRANSAKSI TESTING (RFQ, Quotation, PO) di CRM? Data Customer dan User TIDAK akan dihapus.')) {
            $this->info('Operasi dibatalkan.');
            return 0;
        }

        $timestamp = date('Ymd_His');
        $backupDir = database_path('backups');
        File::ensureDirectoryExists($backupDir);

        $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'rfq_backup_' . $timestamp . '.json';
        $fileBackupDir = $backupDir . DIRECTORY_SEPARATOR . 'files_' . $timestamp;

        // 1. Eksekusi Backup Data
        if (!$noBackup) {
            $this->info('1. Membuat snapshot backup data transaksi testing...');

            $backupData = [
                'metadata' => [
                    'created_at' => now()->toIso8601String(),
                    'environment' => app()->environment(),
                    'app_url' => config('app.url'),
                ],
                'tables' => [
                    'purchase_order_items' => DB::table('purchase_order_items')->get()->toArray(),
                    'purchase_orders'      => DB::table('purchase_orders')->get()->toArray(),
                    'quotation_items'       => DB::table('quotation_items')->get()->toArray(),
                    'quotations'            => DB::table('quotations')->get()->toArray(),
                    'rfq_price_histories'   => DB::table('rfq_price_histories')->get()->toArray(),
                    'rfq_items'             => DB::table('rfq_items')->get()->toArray(),
                    'rfqs'                  => DB::table('rfqs')->get()->toArray(),
                    'approval_histories'    => DB::table('approval_histories')
                        ->where(function ($q) {
                            $q->where('approvable_type', 'like', '%Rfq%')
                              ->orWhere('approvable_type', 'like', '%Quotation%')
                              ->orWhere('approvable_type', 'like', '%PurchaseOrder%');
                        })->get()->toArray(),
                    'activity_logs'         => DB::table('activity_logs')
                        ->where(function ($q) {
                            $q->whereIn('module', ['rfqs', 'rfq', 'quotations', 'quotation', 'purchase_orders', 'purchase_order'])
                              ->orWhere('subject_type', 'like', '%Rfq%')
                              ->orWhere('subject_type', 'like', '%Quotation%')
                              ->orWhere('subject_type', 'like', '%PurchaseOrder%');
                        })->get()->toArray(),
                    'notifications'         => DB::table('notifications')
                        ->where(function ($q) {
                            $q->where('link', 'like', '%rfq%')
                              ->orWhere('link', 'like', '%quotation%')
                              ->orWhere('link', 'like', '%po%')
                              ->orWhere('type', 'like', '%rfq%')
                              ->orWhere('type', 'like', '%po%');
                        })->get()->toArray(),
                ],
            ];

            $totalRows = 0;
            foreach ($backupData['tables'] as $tName => $rows) {
                $totalRows += count($rows);
            }

            File::put($backupFile, json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('   + File backup tersimpan: ' . $backupFile);
            $this->info('   + Total baris transaksi dibackup: ' . $totalRows);

            // Backup berkas upload storage
            $storageSources = [
                storage_path('app/public/po-documents'),
                storage_path('app/public/purchase_orders'),
            ];

            $filesCopied = 0;
            foreach ($storageSources as $srcDir) {
                if (File::isDirectory($srcDir)) {
                    $files = File::allFiles($srcDir);
                    if (count($files) > 0) {
                        File::ensureDirectoryExists($fileBackupDir);
                        foreach ($files as $f) {
                            $subPath = $f->getRelativePathname();
                            $targetPath = $fileBackupDir . DIRECTORY_SEPARATOR . $subPath;
                            File::ensureDirectoryExists(dirname($targetPath));
                            File::copy($f->getRealPath(), $targetPath);
                            $filesCopied++;
                        }
                    }
                }
            }
            if ($filesCopied > 0) {
                $this->info('   + Dokumen fisik testing dibackup: ' . $filesCopied . ' file ke ' . $fileBackupDir);
            }
        }

        // 2. Eksekusi Pembersihan Transaksi
        $this->info("\n2. Membersihkan tabel transaksi testing...");

        Schema::disableForeignKeyConstraints();

        DB::table('purchase_order_items')->truncate();
        DB::table('purchase_orders')->truncate();
        DB::table('quotation_items')->truncate();
        DB::table('quotations')->truncate();
        DB::table('rfq_price_histories')->truncate();
        DB::table('rfq_items')->truncate();
        DB::table('rfqs')->truncate();

        // Hapus log dan approval terkait rfq/po
        DB::table('approval_histories')
            ->where(function ($q) {
                $q->where('approvable_type', 'like', '%Rfq%')
                  ->orWhere('approvable_type', 'like', '%Quotation%')
                  ->orWhere('approvable_type', 'like', '%PurchaseOrder%');
            })->delete();

        DB::table('activity_logs')
            ->where(function ($q) {
                $q->whereIn('module', ['rfqs', 'rfq', 'quotations', 'quotation', 'purchase_orders', 'purchase_order'])
                  ->orWhere('subject_type', 'like', '%Rfq%')
                  ->orWhere('subject_type', 'like', '%Quotation%')
                  ->orWhere('subject_type', 'like', '%PurchaseOrder%');
            })->delete();

        DB::table('notifications')
            ->where(function ($q) {
                $q->where('link', 'like', '%rfq%')
                  ->orWhere('link', 'like', '%quotation%')
                  ->orWhere('link', 'like', '%po%')
                  ->orWhere('type', 'like', '%rfq%')
                  ->orWhere('type', 'like', '%po%');
            })->delete();

        Schema::enableForeignKeyConstraints();

        $customerCount = DB::table('customers')->count();
        $userCount = DB::table('users')->count();

        $this->info('');
        $this->info('========================================================');
        $this->info('BERHASIL! TRANSAKSI TESTING TELAH DIBERSIHKAN DENGAN AMAN');
        $this->info('========================================================');
        $this->table(
            ['Komponen', 'Status Terkini'],
            [
                ['RFQ, Quotation, PO', '0 data (Bersih total)'],
                ['Log Transaksi Testing', '0 data (Bersih total)'],
                ['Data Master Customer', $customerCount . ' customer (UTUH & AMAN)'],
                ['Akun User & Role', $userCount . ' user (UTUH & AMAN)'],
                ['Snapshot Backup', !$noBackup ? basename($backupFile) : 'Dilewati'],
            ]
        );
        $this->info('========================================================');

        return 0;
    }
}
