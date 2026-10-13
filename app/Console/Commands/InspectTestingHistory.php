<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Rfq;
use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Models\ActivityLog;
use App\Models\Notification;

class InspectTestingHistory extends Command
{
    protected $signature = 'crm:inspect-testing';
    protected $description = 'Periksa detail data dan aktivitas testing terkini di CRM (RFQ, PO, Log, Notifikasi, Customer Baru)';

    public function handle()
    {
        $this->info('========================================================');
        $this->info('🔍 AUDIT PERUBAHAN & AKTIVITAS TESTING TERKINI DI CRM');
        $this->info('========================================================');

        // 1. Ringkasan Total Data Transaksi
        $rfqCount = DB::table('rfqs')->count();
        $rfqItemCount = DB::table('rfq_items')->count();
        $priceHistCount = DB::table('rfq_price_histories')->count();
        $poCount = DB::table('purchase_orders')->count();
        $poItemCount = DB::table('purchase_order_items')->count();
        $quoCount = DB::table('quotations')->count();
        $quoItemCount = DB::table('quotation_items')->count();
        $logCount = DB::table('activity_logs')->count();
        $notifCount = DB::table('notifications')->count();

        $storageFiles = 0;
        $poDir = storage_path('app/public/purchase_orders');
        if (File::isDirectory($poDir)) {
            $storageFiles = count(File::allFiles($poDir));
        }

        $this->info("\n1. REKAPITULASI JUMLAH DATA TRANSAKSI TESTING:");
        $this->table(
            ['Tabel / Komponen', 'Jumlah Data', 'Keterangan'],
            [
                ['RFQs (Tiket Permintaan Harga)', $rfqCount, 'Data testing transaksi'],
                ['RFQ Items (Rincian Barang/Jasa)', $rfqItemCount, 'Item pada tiket RFQ'],
                ['RFQ Price Histories (Riwayat HPP)', $priceHistCount, 'Versi kalkulasi harga'],
                ['Purchase Orders (PO Resmi)', $poCount, 'PO hasil GOAL / create'],
                ['Purchase Order Items', $poItemCount, 'Rincian item PO'],
                ['Quotations (Penawaran Standalone)', $quoCount, 'Dokumen penawaran lama'],
                ['Quotation Items', $quoItemCount, 'Rincian item penawaran'],
                ['File Bukti PO di Storage', $storageFiles . ' file', 'File PDF/gambar di storage/app/public/purchase_orders'],
                ['Activity Logs (Catatan Aktivitas)', $logCount, 'Log aksi tester & sistem'],
                ['Notifikasi Sistem', $notifCount, 'Notifikasi ke lonceng user'],
            ]
        );

        // 2. Rincian Tiket RFQ yang Pernah Dibuat
        $this->info("\n2. DAFTAR TIKET RFQ TESTING:");
        $rfqs = DB::table('rfqs')
            ->select('id', 'rfq_number', 'customer_name', 'sales_name', 'status', 'type', 'created_at')
            ->orderByDesc('id')
            ->get();

        if ($rfqs->count() > 0) {
            $rfqRows = $rfqs->map(function ($r) {
                return [
                    'ID'       => $r->id,
                    'No. RFQ'  => $r->rfq_number,
                    'Customer' => $r->customer_name,
                    'Sales'    => $r->sales_name ?: '-',
                    'Tipe'     => $r->type ?: 'Non Projek',
                    'Status'   => $r->status,
                    'Waktu'    => $r->created_at ? substr($r->created_at, 0, 16) : '-',
                ];
            });
            $this->table(['ID', 'No. RFQ', 'Customer', 'Sales', 'Tipe', 'Status', 'Waktu Dibuat'], $rfqRows);
        } else {
            $this->line('   (Tidak ada tiket RFQ di database - Bersih)');
        }

        // 3. Rincian Purchase Order (PO)
        $this->info("\n3. DAFTAR PURCHASE ORDER (PO) TESTING:");
        $pos = DB::table('purchase_orders')
            ->select('id', 'po_number', 'customer_id', 'status', 'grand_total', 'created_at')
            ->orderByDesc('id')
            ->get();

        if ($pos->count() > 0) {
            $poRows = $pos->map(function ($p) {
                return [
                    'ID'       => $p->id,
                    'No. PO'   => $p->po_number,
                    'Cust ID'  => $p->customer_id,
                    'Total'    => 'Rp ' . number_format($p->grand_total, 0, ',', '.'),
                    'Status'   => $p->status,
                    'Waktu'    => $p->created_at ? substr($p->created_at, 0, 16) : '-',
                ];
            });
            $this->table(['ID', 'No. PO', 'Customer ID', 'Grand Total', 'Status', 'Waktu Dibuat'], $poRows);
        } else {
            $this->line('   (Tidak ada Purchase Order di database - Bersih)');
        }

        // 4. Log Aktivitas Terbaru (5 Terakhir)
        $this->info("\n4. AKTIVITAS TERBARU (5 TERAKHIR):");
        $logs = DB::table('activity_logs')
            ->select('id', 'module', 'action', 'description', 'user_id', 'created_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($logs->count() > 0) {
            $logRows = $logs->map(function ($l) {
                return [
                    'ID'      => $l->id,
                    'Modul'   => $l->module,
                    'Aksi'    => $l->action,
                    'Deskripsi' => substr($l->description ?? '-', 0, 40),
                    'User ID' => $l->user_id,
                    'Waktu'   => $l->created_at ? substr($l->created_at, 0, 16) : '-',
                ];
            });
            $this->table(['ID', 'Modul', 'Aksi', 'Deskripsi', 'User ID', 'Waktu'], $logRows);
        } else {
            $this->line('   (Tidak ada activity log di database)');
        }

        // 5. Customer yang Baru Dibuat dalam 7 Hari Terakhir
        $this->info("\n5. CUSTOMER YANG BARU DITAMBAHKAN DALAM 7 HARI TERAKHIR:");
        $recentCusts = DB::table('customers')
            ->where('created_at', '>=', now()->subDays(7))
            ->select('id', 'company_name', 'status', 'sales_id', 'created_at')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        if ($recentCusts->count() > 0) {
            $custRows = $recentCusts->map(function ($c) {
                return [
                    'ID'        => $c->id,
                    'Nama PT'   => $c->company_name,
                    'Status'    => $c->status,
                    'Sales ID'  => $c->sales_id ?: '-',
                    'Waktu Buat'=> $c->created_at ? substr($c->created_at, 0, 16) : '-',
                ];
            });
            $this->table(['ID', 'Nama Perusahaan', 'Status', 'Sales ID', 'Waktu Dibuat'], $custRows);
        } else {
            $this->line('   (Tidak ada customer baru yang ditambahkan dalam 7 hari terakhir)');
        }

        $this->info("\n========================================================");
        $this->info('PETUNJUK PENGHAPUSAN:');
        $this->info('Untuk membersihkan seluruh transaksi testing di atas secara aman:');
        $this->info('  php artisan crm:reset-rfq');
        $this->info('Perintah tersebut otomatis membuat backup snapshot JSON terlebih dahulu');
        $this->info('dan TIDAK AKAN menghapus data Customer maupun akun User.');
        $this->info('========================================================');

        return 0;
    }
}
