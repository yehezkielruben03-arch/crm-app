<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use Illuminate\Console\Command;

/**
 * Gap #10 — Otomatisasi Status Customer Inactive
 *
 * Logika (Bahasa Bayi):
 * Seperti kartu anggota gym — kalau tidak datang 12 bulan, otomatis
 * jadi Inactive. Command ini jalan setiap hari dan mengecek semua
 * Customer Active yang tidak punya transaksi Goal dalam 12 bulan terakhir.
 */
class UpdateInactiveCustomers extends Command
{
    protected $signature   = 'customer:update-status';
    protected $description = 'Set status customer ke Inactive jika tidak ada transaksi selama > 12 bulan';

    public function handle(): int
    {
        $cutoff = now()->subMonths(12);

        // Cari Customer Active yang tidak punya RFQ Goal ATAU PO Goal dalam 12 bulan
        $query = Customer::where('status', Customer::STATUS_ACTIVE)
            ->whereDoesntHave('rfqs', function ($q) use ($cutoff) {
                $q->where('status', Rfq::STATUS_GOAL)
                  ->where('updated_at', '>=', $cutoff);
            })
            ->whereDoesntHave('purchaseOrders', function ($q) use ($cutoff) {
                $q->where('status', PurchaseOrder::STATUS_GOAL)
                  ->where('updated_at', '>=', $cutoff);
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('Tidak ada customer yang perlu diubah statusnya.');
            return self::SUCCESS;
        }

        // Bulk update agar efisien — tidak perlu loop satu-satu
        $query->update(['status' => Customer::STATUS_INACTIVE]);

        $this->info("{$count} customer telah diubah statusnya menjadi Inactive.");
        return self::SUCCESS;
    }
}
