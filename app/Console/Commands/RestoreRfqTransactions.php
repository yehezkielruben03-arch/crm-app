<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RestoreRfqTransactions extends Command
{
    protected $signature = 'crm:restore-rfq {file? : Nama atau path file JSON backup di database/backups}';
    protected $description = 'Pulihkan data transaksi testing RFQ, Quotation, PO dari snapshot backup JSON';

    public function handle()
    {
        $backupDir = database_path('backups');
        $fileArg = $this->argument('file');

        $targetPath = null;
        if ($fileArg) {
            if (File::exists($fileArg)) {
                $targetPath = $fileArg;
            } elseif (File::exists($backupDir . DIRECTORY_SEPARATOR . $fileArg)) {
                $targetPath = $backupDir . DIRECTORY_SEPARATOR . $fileArg;
            } else {
                $this->error('File backup tidak ditemukan: ' . $fileArg);
                return 1;
            }
        } else {
            if (!File::isDirectory($backupDir)) {
                $this->error('Folder backup tidak ditemukan di: ' . $backupDir);
                return 1;
            }
            $allFiles = File::files($backupDir);
            $backupFiles = [];
            foreach ($allFiles as $f) {
                if (str_starts_with($f->getFilename(), 'rfq_backup_') && str_ends_with($f->getFilename(), '.json')) {
                    $backupFiles[] = $f->getRealPath();
                }
            }
            if (empty($backupFiles)) {
                $this->error('Tidak ada file backup rfq_backup di folder: ' . $backupDir);
                return 1;
            }
            rsort($backupFiles);
            $targetPath = $backupFiles[0];
        }

        $this->info('Memulihkan snapshot dari: ' . $targetPath);

        $raw = File::get($targetPath);
        $data = json_decode($raw, true);

        if (!isset($data['tables'])) {
            $this->error('Format data backup tidak valid.');
            return 1;
        }

        Schema::disableForeignKeyConstraints();

        $tables = [
            'purchase_order_items',
            'purchase_orders',
            'quotation_items',
            'quotations',
            'rfq_price_histories',
            'rfq_items',
            'rfqs',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            if (!empty($data['tables'][$table])) {
                $chunks = array_chunk($data['tables'][$table], 100);
                foreach ($chunks as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
            $count = count($data['tables'][$table] ?? []);
            $this->info('   + Tabel ' . $table . ' dipulihkan (' . $count . ' baris)');
        }

        if (!empty($data['tables']['approval_histories'])) {
            DB::table('approval_histories')
                ->where(function ($q) {
                    $q->where('approvable_type', 'like', '%Rfq%')
                      ->orWhere('approvable_type', 'like', '%Quotation%')
                      ->orWhere('approvable_type', 'like', '%PurchaseOrder%');
                })->delete();
            $chunks = array_chunk($data['tables']['approval_histories'], 100);
            foreach ($chunks as $chunk) {
                DB::table('approval_histories')->insert($chunk);
            }
            $this->info('   + Histori approval dipulihkan.');
        }

        if (!empty($data['tables']['activity_logs'])) {
            DB::table('activity_logs')
                ->where(function ($q) {
                    $q->whereIn('module', ['rfqs', 'rfq', 'quotations', 'quotation', 'purchase_orders', 'purchase_order'])
                      ->orWhere('subject_type', 'like', '%Rfq%')
                      ->orWhere('subject_type', 'like', '%Quotation%')
                      ->orWhere('subject_type', 'like', '%PurchaseOrder%');
                })->delete();
            $chunks = array_chunk($data['tables']['activity_logs'], 100);
            foreach ($chunks as $chunk) {
                DB::table('activity_logs')->insert($chunk);
            }
            $this->info('   + Activity logs dipulihkan.');
        }

        if (!empty($data['tables']['notifications'])) {
            DB::table('notifications')
                ->where(function ($q) {
                    $q->where('link', 'like', '%rfq%')
                      ->orWhere('link', 'like', '%quotation%')
                      ->orWhere('link', 'like', '%po%')
                      ->orWhere('type', 'like', '%rfq%')
                      ->orWhere('type', 'like', '%po%');
                })->delete();
            $chunks = array_chunk($data['tables']['notifications'], 100);
            foreach ($chunks as $chunk) {
                DB::table('notifications')->insert($chunk);
            }
            $this->info('   + Notifikasi dipulihkan.');
        }

        Schema::enableForeignKeyConstraints();

        $this->info('');
        $this->info('========================================================');
        $this->info('SELESAI! TRANSAKSI TESTING BERHASIL DIPULIHKAN 100%');
        $this->info('========================================================');

        return 0;
    }
}
