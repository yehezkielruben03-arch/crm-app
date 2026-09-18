<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PruneOldLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:prune-logs {--months=6 : Jumlah bulan data akan dipertahankan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghapus data notifikasi dan activity log yang sudah kedaluwarsa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $months = (int) $this->option('months');
        $dateLimit = now()->subMonths($months);

        $this->info("Memulai proses bersih-bersih data sebelum tanggal: {$dateLimit->format('Y-m-d')}");

        // 1. Hapus Notifikasi Lama
        $deletedNotifs = \Illuminate\Support\Facades\DB::table('notifications')
            ->where('created_at', '<', $dateLimit)
            ->delete();
        $this->info("✅ Berhasil menghapus {$deletedNotifs} baris dari tabel notifications.");

        // 2. Hapus Activity Log Lama
        $deletedLogs = \Illuminate\Support\Facades\DB::table('activity_logs')
            ->where('created_at', '<', $dateLimit)
            ->delete();
        $this->info("✅ Berhasil menghapus {$deletedLogs} baris dari tabel activity_logs.");

        $this->info("Proses selesai!");
    }
}
