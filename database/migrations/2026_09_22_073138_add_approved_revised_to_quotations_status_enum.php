<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambahkan nilai 'Approved' dan 'Revised' ke kolom ENUM status di tabel quotations.
     * Sebelumnya hanya ['Draft', 'Sent'] — Quotation::STATUS_APPROVED tidak bisa disimpan ke DB.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // MySQL tidak support ALTER ENUM langsung via Eloquent Blueprint ->change() dengan aman.
        // Gunakan raw SQL untuk modifikasi ENUM agar tidak ada data yang hilang.
        DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('Draft', 'Sent', 'Approved', 'Revised') NOT NULL DEFAULT 'Draft'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Rollback: hapus 'Approved' dan 'Revised' — HATI-HATI jika sudah ada data dengan status tsb
        DB::statement("ALTER TABLE quotations MODIFY COLUMN status ENUM('Draft', 'Sent') NOT NULL DEFAULT 'Draft'");
    }
};
