<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqPriceHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckRfqRevisionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:check-rfq-revision 
                            {--rfq= : Cek spesifik nomor RFQ (misal: 261028-0020)}
                            {--month= : Bulan transaksi PO (default bulan ini)}
                            {--year= : Tahun (default tahun ini)}
                            {--sales= : Filter nama sales (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit detail riwayat revisi harga, masa berlaku 3 hari, dan timeline update RFQ vs PO';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificRfq = $this->option('rfq');
        $month = (int) ($this->option('month') ?: now()->month);
        $year  = (int) ($this->option('year') ?: now()->year);
        $salesFilter = $this->option('sales');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $this->newLine();
        $this->line("==================================================================================");
        $this->info("             PEDIA CRM - AUDIT FORENSIK UPDATE & REVISI SETIAP RFQ                ");
        $this->line("==================================================================================");
        $this->line("  Periode Audit       : " . $startDate->format('d M Y') . " s/d " . $endDate->format('d M Y'));
        $this->line("  Waktu Audit         : " . now()->format('d M Y H:i:s') . " WIB");
        if ($specificRfq) {
            $this->line("  Filter Nomor RFQ    : " . $specificRfq);
        }
        if ($salesFilter) {
            $this->line("  Filter Sales        : " . $salesFilter);
        }
        $this->line("----------------------------------------------------------------------------------");
        $this->newLine();

        $rfqQuery = Rfq::with([
            'customer',
            'sales',
            'items',
            'purchaseOrders',
            'revisionAuthor',
            'priceHistories.creator',
        ]);

        if ($specificRfq) {
            $rfqQuery->where('rfq_number', 'like', "%{$specificRfq}%");
        } else {
            // Ambil semua RFQ yang punya PO Goal di bulan ini, atau RFQ Goal di bulan ini
            $rfqQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereHas('purchaseOrders', function ($poQ) use ($startDate, $endDate) {
                    $poQ->whereBetween('po_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                        ->orWhereBetween('created_at', [$startDate, $endDate])
                        ->orWhereBetween('approved_at', [$startDate, $endDate]);
                })->orWhere(function ($goalQ) use ($startDate, $endDate) {
                    $goalQ->whereIn('status', [Rfq::STATUS_GOAL, 'Goal'])
                          ->whereBetween('updated_at', [$startDate, $endDate]);
                });
            });

            if ($salesFilter) {
                $rfqQuery->whereHas('sales', function ($sq) use ($salesFilter) {
                    $sq->where('name', 'like', "%{$salesFilter}%");
                });
            }
        }

        $rfqs = $rfqQuery->orderBy('rfq_date')->get();

        if ($rfqs->isEmpty()) {
            $this->warn("Tidak ditemukan RFQ yang cocok dengan kriteria audit.");
            return 0;
        }

        $this->info("Ditemukan " . $rfqs->count() . " RFQ untuk diaudit riwayat update & revisinya.\n");

        foreach ($rfqs as $idx => $rfq) {
            $no = $idx + 1;
            $customerName = $rfq->customer?->company_name ?? $rfq->customer_name;
            $salesName = $rfq->sales?->name ?? $rfq->sales_name;
            
            // Tanggal dasar
            $createdDate = $rfq->created_at ? $rfq->created_at->format('d/m/Y H:i') : '-';
            $rfqDate = $rfq->rfq_date ? Carbon::parse($rfq->rfq_date)->format('d/m/Y') : '-';
            $updatedDate = $rfq->updated_at ? $rfq->updated_at->format('d/m/Y H:i') : '-';

            // Hitung masa berlaku 3 hari kerja
            $validUntil = $rfq->calculateValidUntilDate();
            $validUntilStr = $validUntil->format('d/m/Y');
            $maxValidityDays = (int) ($rfq->items->max('validity_days') ?: 3);
            if ($maxValidityDays <= 0 || $maxValidityDays === 7) {
                $maxValidityDays = 3;
            }

            // PO terkait
            $po = $rfq->purchaseOrders->first();
            $poDate = $po && $po->po_date ? Carbon::parse($po->po_date)->format('d/m/Y') : ($po ? $po->created_at->format('d/m/Y') : '-');
            $poApprovedAt = $po && $po->approved_at ? Carbon::parse($po->approved_at)->format('d/m/Y H:i') : '-';
            $poNumber = $po?->po_number ?: '-';
            $poSubtotal = $po ? (float) $po->effective_dpp : 0;
            $poGrandTotal = $po ? (float) $po->grand_total : 0;

            // Cek apakah PO terbit setelah masa berlaku 3 hari habis
            $poCarbonDate = $po && $po->po_date ? Carbon::parse($po->po_date) : ($po ? $po->created_at : null);
            $isPoAfterExpiry = $poCarbonDate ? $poCarbonDate->startOfDay()->gt($validUntil->startOfDay()) : false;
            $gapDays = $poCarbonDate ? Carbon::parse($rfq->rfq_date ?: $rfq->created_at)->diffInDays($poCarbonDate) : 0;

            // Riwayat Versi Harga (RfqPriceHistory)
            $histories = $rfq->priceHistories->sortBy('version');
            $versionCount = $histories->count();

            // Total RFQ Items saat ini (draf di database)
            $currentRfqDpp = (float) $rfq->grand_total;

            $this->line("==================================================================================");
            $this->info("[$no] RFQ: {$rfq->rfq_number} | Pelanggan: {$customerName} | Sales: {$salesName}");
            $this->line("==================================================================================");
            $this->line("  • Status RFQ Saat Ini     : {$rfq->status}");
            $this->line("  • Tanggal RFQ Dibuat      : {$rfqDate} (Created: {$createdDate})");
            $this->line("  • Masa Berlaku ({$maxValidityDays} hari)   : s/d {$validUntilStr}");
            $this->line("  • Tanggal Update Terakhir : {$updatedDate}");

            // Tampilkan info PO
            if ($po) {
                $statusExpiry = $isPoAfterExpiry 
                    ? "<fg=yellow>LEWAT MASA BERLAKU ({$gapDays} hari selisih dari tgl penawaran)</fg=yellow>"
                    : "<info>MASIH BERLAKU</info>";

                $this->line("  • PO Terkait              : {$poNumber} (Status: {$po->status})");
                $this->line("  • Tanggal PO Terbit       : {$poDate} [{$statusExpiry}]");
                $this->line("  • Tanggal PO Di-Approve   : {$poApprovedAt}");
                $this->line("  • Nilai PO (DPP / Subtot) : Rp " . number_format($poSubtotal, 0, ',', '.'));
                $this->line("  • Nilai PO (Grand Total)  : Rp " . number_format($poGrandTotal, 0, ',', '.'));
            } else {
                $this->line("  • PO Terkait              : Belum ada PO terhubung");
            }

            // Tampilkan Detail Riwayat Versi Harga
            $this->newLine();
            $this->line("  ─── Riwayat Versi Harga (Snapshot Versioning) ───");
            if ($versionCount > 0) {
                $historyRows = [];
                foreach ($histories as $h) {
                    $creatorName = $h->creator?->name ?? 'System/Admin';
                    $createdAt = $h->created_at ? $h->created_at->format('d/m/Y H:i') : '-';
                    $approvedAt = $h->approved_at ? $h->approved_at->format('d/m/Y H:i') : 'Belum/Auto';
                    
                    $vTotal = 0;
                    $itemCount = 0;
                    if (is_array($h->history_data)) {
                        $topHistoryItems = collect($h->history_data)->filter(fn($hi) => empty($hi['parent_id']));
                        $itemCount = $topHistoryItems->count();
                        foreach ($topHistoryItems as $hItem) {
                            $qty = (float) ($hItem['qty'] ?? 0);
                            $price = (float) ($hItem['price_after_margin'] ?? ($hItem['unit_price'] ?? 0));
                            $vTotal += ($qty * $price);
                        }
                    }

                    $historyRows[] = [
                        "v{$h->version}",
                        $createdAt,
                        $creatorName,
                        $approvedAt,
                        $itemCount . ' item',
                        'Rp ' . number_format($vTotal, 0, ',', '.'),
                    ];
                }

                $this->table([
                    'Versi',
                    'Tgl Disubmit',
                    'Disubmit Oleh',
                    'Tgl Approval',
                    'Jumlah Item',
                    'Total Estimasi (DPP)',
                ], $historyRows);
            } else {
                $this->line("    (Tidak ada log versi bertahap di rfq_price_histories. Hanya draf 1 nilai: Rp " . number_format($currentRfqDpp, 0, ',', '.') . ")");
            }

            // Tampilkan Catatan Revisi jika ada
            if ($rfq->revision_notes || $rfq->revision_at || $rfq->revision_by) {
                $revDate = $rfq->revision_at ? Carbon::parse($rfq->revision_at)->format('d/m/Y H:i') : '-';
                $revBy = $rfq->revisionAuthor?->name ?? 'Admin/Leader';
                $this->warn("  [!] CATATAN REVISI RFQ : \"{$rfq->revision_notes}\"");
                $this->line("      Direvisi Oleh      : {$revBy} pada {$revDate}");
            }

            if ($rfq->po_rejection_note || $rfq->po_rejected_at) {
                $poRejDate = $rfq->po_rejected_at ? Carbon::parse($rfq->po_rejected_at)->format('d/m/Y H:i') : '-';
                $this->error("  [!] REVISI DARI REJECT GOAL: \"{$rfq->po_rejection_note}\" ({$poRejDate})");
            }

            // DIAGNOSA FORENSIK PER RFQ
            $this->newLine();
            $this->line("  ─── Diagnosa Kenapa Tanggal RFQ & PO Berjarak ───");
            if ($versionCount > 1) {
                $latestVer = $histories->last();
                $latestDate = $latestVer->created_at ? $latestVer->created_at->format('d/m/Y') : '-';
                $this->info("  ✓ Ada Update/Revisi Harga Resmi: RFQ ini mengalami " . ($versionCount - 1) . " kali revisi. Versi terakhir (v{$latestVer->version}) disubmit tgl {$latestDate}.");
            } else {
                if ($isPoAfterExpiry) {
                    $this->comment("  ℹ Penjelasan Lapangan: RFQ ini dibuat tgl {$rfqDate} (berlaku s/d {$validUntilStr}).");
                    $this->comment("    Customer baru menerbitkan PO tgl {$poDate} ({$gapDays} hari kemudian).");
                    $this->comment("    Karena harga barang dan kesepakatan tidak berubah, Sales & Admin langsung meng-upload/approve PO");
                    $this->comment("    ke nomor RFQ ini tanpa membuat draf baru ataupun revisi ulang harga di sistem.");
                } else {
                    $this->info("  ✓ Timeline Normal: PO terbit dalam rentang waktu yang wajar.");
                }
            }

            if ($po && $poSubtotal > 0 && abs($poSubtotal - $currentRfqDpp) > 100) {
                $selisih = $poSubtotal - $currentRfqDpp;
                $this->warn("  * Perbedaan Nilai: Nilai di PO (Rp " . number_format($poSubtotal, 0, ',', '.') . ") beda Rp " . number_format(abs($selisih), 0, ',', '.') . " dengan draf awal RFQ (Rp " . number_format($currentRfqDpp, 0, ',', '.') . ").");
            }

            $this->newLine();
        }

        return 0;
    }
}
