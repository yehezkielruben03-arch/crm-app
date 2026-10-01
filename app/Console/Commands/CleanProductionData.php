<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanProductionData extends Command
{
    protected $signature = 'crm:clean-data {--keep-customers : Jangan hapus data customer} {--force : Jalankan tanpa konfirmasi}';
    protected $description = 'Bersihkan seluruh data transaksi dan dummy testing di CRM agar siap pakai';

    public function handle()
    {
        $keepCustomers = $this->option('keep-customers');
        $force = $this->option('force');

        if (!$force && !$this->confirm('Apakah Anda yakin ingin MENGHAPUS SELURUH DATA TRANSAKSI TESTING di CRM?')) {
            $this->info('Dibatalkan.');
            return 0;
        }

        $this->info('Memulai pembersihan data transaksi...');

        Schema::disableForeignKeyConstraints();

        // 1. Bersihkan transaksi RFQ
        DB::table('rfq_items')->truncate();
        DB::table('rfq_price_histories')->truncate();
        DB::table('rfqs')->truncate();
        $this->info('✓ Data RFQ dan harga berhasil dibersihkan.');

        // 2. Bersihkan Quotation
        DB::table('quotation_items')->truncate();
        DB::table('quotations')->truncate();
        $this->info('✓ Data Penawaran (Quotation) berhasil dibersihkan.');

        // 3. Bersihkan Purchase Orders
        DB::table('purchase_order_items')->truncate();
        DB::table('purchase_orders')->truncate();
        $this->info('✓ Data Purchase Order (PO) berhasil dibersihkan.');

        // 4. Bersihkan Approval & Log & Notifikasi
        DB::table('approval_histories')->truncate();
        DB::table('activity_logs')->truncate();
        DB::table('notifications')->truncate();
        $this->info('✓ Histori approval, log aktivitas, dan notifikasi berhasil dibersihkan.');

        // 5. Bersihkan Customer jika tidak di-keep
        if (!$keepCustomers) {
            DB::table('customer_contacts')->truncate();
            DB::table('customer_billing_addresses')->truncate();
            DB::table('customer_shipping_addresses')->truncate();
            DB::table('customers')->truncate();
            $this->info('✓ Data customer testing dibersihkan (siap untuk import data asli).');
        } else {
            $this->info('ℹ Data customer dipertahankan.');
        }

        Schema::enableForeignKeyConstraints();

        $this->info('');
        $this->info('========================================================');
        $this->info('✅ DATABASE CRM TELAH BERSIH & SIAP PAKAI 100%!');
        $this->info('   - Akun User & Role tetap UTUH');
        $this->info('   - Seluruh transaksi testing telah di-reset ke 0');
        $this->info('========================================================');

        return 0;
    }
}
