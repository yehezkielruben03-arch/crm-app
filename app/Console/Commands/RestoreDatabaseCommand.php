<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class RestoreDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:restore 
                            {file? : Nama file backup zip di folder storage/app/backups}
                            {--force : Lewati konfirmasi peringatan}
                            {--skip-snapshot : Lewati pembuatan snapshot pengaman sebelum restore}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memulihkan (restore) database dan file storage Pedia CRM dari file arsip backup';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $this->warn("=================================================");
        $this->warn("   PEDIA CRM - DATA RESTORE UTILITY              ");
        $this->warn("=================================================");

        $filename = $this->argument('file');

        // Jika user tidak memasukkan nama file, tampilkan pilihan backup yang tersedia
        if (!$filename) {
            $backups = $backupService->listBackups();
            if (empty($backups)) {
                $this->error("Tidak ada file backup yang ditemukan di folder storage/app/backups!");
                return Command::FAILURE;
            }

            $choices = [];
            foreach ($backups as $index => $b) {
                $choices[$b['filename']] = "{$b['filename']} ({$b['size_formatted']} - {$b['created_at']})";
            }

            $filename = $this->choice(
                'Pilih file backup yang ingin di-restore ke sistem:',
                array_keys($choices),
                0
            );
        }

        $this->newLine();
        $this->alert("PERINGATAN: Proses ini akan menimpa data yang sedang aktif dengan data backup!");
        $this->line("Target File: <fg=cyan>{$filename}</>");

        $force = (bool) $this->option('force');
        if (!$force) {
            if (!$this->confirm('Apakah Anda yakin 100% ingin melanjutkan proses restore ini?', false)) {
                $this->info("Proses restore dibatalkan oleh pengguna.");
                return Command::SUCCESS;
            }
        }

        $skipSnapshot = (bool) $this->option('skip-snapshot');
        if (!$skipSnapshot) {
            $this->info("→ Membuat Safety Snapshot pengaman data saat ini...");
        }

        $this->line("→ Memulai ekstraksi dan injeksi data ke database & storage...");
        $startTime = microtime(true);

        try {
            $result = $backupService->restoreBackup($filename, [
                'skip_snapshot' => $skipSnapshot,
            ]);

            $duration = round(microtime(true) - $startTime, 2);

            $this->newLine();
            $this->info("✓ RESTORE BERHASIL SELESAI dalam {$duration} detik!");
            $this->table(
                ['Parameter', 'Keterangan'],
                [
                    ['File Sumber', $result['restored_file']],
                    ['Tabel DB Terpulihkan', $result['tables_restored']],
                    ['Dokumen Storage Terpulihkan', $result['files_restored']],
                    ['Safety Snapshot (Undo Guard)', $result['safety_snapshot'] ?: '(Dilewati)'],
                    ['Cache System', 'optimize:clear dieksekusi otomatis'],
                ]
            );

            if (!empty($result['safety_snapshot'])) {
                $this->line("<fg=yellow>Catatan:</> Jika Anda ingin membatalkan/mengembalikan kondisi sebelum restore, jalankan:");
                $this->line("<fg=yellow>php artisan crm:restore {$result['safety_snapshot']} --force</>");
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error("✗ RESTORE GAGAL: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
