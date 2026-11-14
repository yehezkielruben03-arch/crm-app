<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:backup 
                            {--retention=14 : Batas hari penyimpanan backup sebelum dihapus otomatis}
                            {--only-db : Hanya backup database}
                            {--only-files : Hanya backup file storage/app/public}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat file arsip backup terkompresi (.zip) untuk database dan file storage Pedia CRM';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->info("=================================================");
        $this->info("   PEDIA CRM - AUTO BACKUP SYSTEM                ");
        $this->info("=================================================");

        $retention = (int) $this->option('retention');
        $onlyDb = (bool) $this->option('only-db');
        $onlyFiles = (bool) $this->option('only-files');

        $this->line("Memulai proses backup...");
        $this->line("Opsi: Retention={$retention} hari | OnlyDB=" . ($onlyDb ? 'Yes' : 'No') . " | OnlyFiles=" . ($onlyFiles ? 'Yes' : 'No'));

        $startTime = microtime(true);

        try {
            $result = $backupService->createBackup([
                'retention_days' => $retention,
                'only_db' => $onlyDb,
                'only_files' => $onlyFiles,
                'created_by' => 'Artisan CLI (crm:backup)',
            ]);

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info("✓ Backup BERHASIL DIBUAT dalam {$duration} detik!");
            $this->table(
                ['Parameter', 'Nilai'],
                [
                    ['Nama File', $result['filename']],
                    ['Lokasi File', $result['path']],
                    ['Ukuran File', $result['size_formatted']],
                    ['Jumlah Tabel DB', $result['tables_count']],
                    ['Jumlah Dokumen Storage', $result['files_count']],
                    ['Backup Lama Dibersihkan', $result['purged_count'] . " file"],
                ]
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ Backup GAGAL: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
