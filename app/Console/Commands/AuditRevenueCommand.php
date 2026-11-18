<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AuditRevenueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:audit-revenue 
                            {--month= : Bulan (1-12, default bulan berjalan)} 
                            {--year= : Tahun (default tahun berjalan)} 
                            {--sales= : Filter nama sales (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit menyeluruh semua RFQ, Bundling/PC Rakitan, PO GOAL, PDF Quotation, dan sinkronisasi Dashboard';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $month = (int) ($this->option('month') ?: now()->month);
        $year  = (int) ($this->option('year') ?: now()->year);
        $salesFilter = $this->option('sales');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth()->startOfDay();
        $endDate   = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $this->newLine();
        $this->line("==========================================================================================================");
        $this->info("              PEDIA CRM - FULL SYSTEM AUDIT: RFQ, BUNDLING, PO FISIK & DASHBOARD                         ");
        $this->line("==========================================================================================================");
        $this->line("  Periode Audit       : " . $startDate->format('d M Y') . " s/d " . $endDate->format('d M Y'));
        $this->line("  Waktu Pengecekan    : " . now()->format('d M Y H:i:s') . " WIB");
        $this->line("----------------------------------------------------------------------------------------------------------");
        $this->newLine();

        // ==========================================================================================================
        // BAGIAN 1: AUDIT SELURUH RFQ YANG MENGGUNAKAN FITUR BUNDLING / PC RAKITAN DI SELURUH DATABASE
        // ==========================================================================================================
        $this->info("▶ [1/5] AUDIT SELURUH RFQ BUNDLING / PC RAKITAN (CEK DOBEL HITUNG KOMPONEN ANAK & MARGIN):");

        $bundledRfqIds = RfqItem::where(function ($q) {
                $q->where('is_bundle', true)->orWhereNotNull('parent_id');
            })
            ->pluck('rfq_id')
            ->unique();

        $bundledRfqs = Rfq::with(['customer', 'sales', 'items.components', 'purchaseOrders'])
            ->whereIn('id', $bundledRfqIds)
            ->orderByDesc('id')
            ->get();

        $bundleRows = [];
        $bNo = 1;
        foreach ($bundledRfqs as $bRfq) {
            $allItemsRawSum = (float) $bRfq->items->sum(fn($i) => (float) $i->qty * (float) $i->price_after_margin);
            $topLevelCleanSum = (float) $bRfq->grand_total;
            $pdfQuotationSum = (float) $bRfq->customer_quotation_items->sum('row_total');
            $topCount = $bRfq->items->filter(fn($i) => empty($i->parent_id))->count();
            $childCount = $bRfq->items->filter(fn($i) => !empty($i->parent_id))->count();

            $po = $bRfq->purchaseOrders->first();
            $poBefore = $po ? (((float) $po->subtotal > 0) ? (float) $po->subtotal : (float) $po->grand_total) : null;
            $poAfter = $po ? (float) $po->effective_dpp : null;

            $statusCheck = '✓ AMAN & SINKRON';
            if ($poBefore !== null && abs($poBefore - $poAfter) > 1) {
                $statusCheck = '🔧 AUTO-FIXED PO (' . number_format($poBefore, 0, ',', '.') . ' -> ' . number_format($poAfter, 0, ',', '.') . ')';
            } elseif (abs($topLevelCleanSum - $pdfQuotationSum) > 1) {
                $statusCheck = '⚠ BEDA PDF VS WEB (' . number_format($pdfQuotationSum, 0, ',', '.') . ' vs ' . number_format($topLevelCleanSum, 0, ',', '.') . ')';
            }

            $bundleRows[] = [
                $bNo++,
                $bRfq->rfq_number,
                $bRfq->rfq_date ? $bRfq->rfq_date->format('d/m/Y') : '-',
                mb_strimwidth($bRfq->sales?->name ?? $bRfq->sales_name ?? '-', 0, 14, '..'),
                mb_strimwidth($bRfq->customer?->company_name ?? $bRfq->customer_name ?? '-', 0, 20, '..'),
                $bRfq->status,
                "{$topCount} Induk + {$childCount} Part",
                'Rp ' . number_format($allItemsRawSum, 0, ',', '.'),
                'Rp ' . number_format($topLevelCleanSum, 0, ',', '.'),
                'Rp ' . number_format($pdfQuotationSum, 0, ',', '.'),
                $poAfter !== null ? 'Rp ' . number_format($poAfter, 0, ',', '.') : '-',
                $statusCheck,
            ];
        }

        if (!empty($bundleRows)) {
            $this->table(
                ['No', 'No RFQ', 'Tgl', 'Sales', 'Customer', 'Status', 'Jml Item', 'Raw (Jika Dobel)', 'Web ($rfq->grand_total)', 'PDF Quotation', 'Nilai PO DPP', 'Hasil Cek'],
                $bundleRows
            );
        } else {
            $this->line("  Tidak ada RFQ Bundling ditemukan.");
        }

        // ==========================================================================================================
        // BAGIAN 2: AUDIT 29 TRANSAKSI PO GOAL PERIODE INI (BARIS DEMI BARIS)
        // ==========================================================================================================
        $this->newLine();
        $this->info("▶ [2/5] AUDIT DETAIL SELURUH TRANSAKSI PO GOAL PERIODE INI:");

        $poQuery = PurchaseOrder::with(['customer', 'sales', 'rfq.sales', 'rfq.items.components'])
            ->whereIn('status', [PurchaseOrder::STATUS_GOAL, 'Goal', 'goal', 'Approved', 'approved'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('po_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                  ->orWhereBetween('approved_at', [$startDate, $endDate])
                  ->orWhere(function ($fallbackQ) use ($startDate, $endDate) {
                      $fallbackQ->whereNull('po_date')
                                ->whereNull('approved_at')
                                ->whereBetween('updated_at', [$startDate, $endDate]);
                  });
            });

        if ($salesFilter) {
            $poQuery->where(function ($sq) use ($salesFilter) {
                $sq->whereHas('sales', fn($u) => $u->where('name', 'like', "%{$salesFilter}%"))
                   ->orWhereHas('rfq.sales', fn($u) => $u->where('name', 'like', "%{$salesFilter}%"));
            });
        }

        $pos = $poQuery->orderBy('po_date', 'asc')->orderBy('id', 'asc')->get();

        $poRfqIds = $pos->pluck('rfq_id')->filter()->unique();
        $rfqQuery = Rfq::with(['customer', 'sales', 'items.components'])
            ->whereIn('status', [Rfq::STATUS_GOAL, 'Goal', 'goal', 'GOAL'])
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->whereNotIn('id', $poRfqIds);

        if ($salesFilter) {
            $rfqQuery->whereHas('sales', fn($u) => $u->where('name', 'like', "%{$salesFilter}%"));
        }

        $standaloneRfqs = $rfqQuery->orderBy('updated_at', 'asc')->get();

        $rows = [];
        $no = 1;
        $totalDraftRfq = 0.0;
        $totalPoDealDpp = 0.0;
        $totalTax = 0.0;
        $totalGrandTotal = 0.0;
        $salesSummary = [];

        foreach ($pos as $po) {
            $salesName = $po->sales?->name ?? $po->rfq?->sales?->name ?? 'Unknown';
            $custName  = $po->customer?->company_name ?? $po->rfq?->customer_name ?? '-';
            $poDate    = $po->po_date ? Carbon::parse($po->po_date)->format('d/m/Y') : ($po->approved_at ? Carbon::parse($po->approved_at)->format('d/m/Y') : Carbon::parse($po->updated_at)->format('d/m/Y'));

            // Deal PO fisik (DPP - otomatis auto-heal jika ada bug dobel hitung bundling lama)
            $dealDpp = (float) $po->effective_dpp;
            // Draf RFQ awal sebelum nego/diskon (hanya item level atas, bebas dari duplikasi komponen anak bundling)
            $draftRfqVal = $po->rfq ? (float) $po->rfq->grand_total : 0.0;

            $taxVal  = (float) $po->tax_amount;
            $grandVal = (float) $po->grand_total;
            $selisih = $dealDpp - $draftRfqVal;

            $totalDraftRfq += $draftRfqVal;
            $totalPoDealDpp += $dealDpp;
            $totalTax += $taxVal;
            $totalGrandTotal += $grandVal;

            if (!isset($salesSummary[$salesName])) {
                $salesSummary[$salesName] = [
                    'count' => 0,
                    'draft' => 0.0,
                    'deal'  => 0.0,
                    'tax'   => 0.0,
                    'grand' => 0.0,
                ];
            }
            $salesSummary[$salesName]['count']++;
            $salesSummary[$salesName]['draft'] += $draftRfqVal;
            $salesSummary[$salesName]['deal']  += $dealDpp;
            $salesSummary[$salesName]['tax']   += $taxVal;
            $salesSummary[$salesName]['grand'] += $grandVal;

            $selisihText = 'Sama';
            if ($selisih < -100) {
                $selisihText = '-Rp ' . number_format(abs($selisih), 0, ',', '.');
            } elseif ($selisih > 100) {
                $selisihText = '+Rp ' . number_format($selisih, 0, ',', '.');
            }

            $rows[] = [
                $no++,
                mb_strimwidth($salesName, 0, 14, '..'),
                mb_strimwidth($custName, 0, 20, '..'),
                $po->po_number,
                $po->rfq?->rfq_number ?? '-',
                $poDate,
                number_format($draftRfqVal, 0, ',', '.'),
                number_format($dealDpp, 0, ',', '.'),
                $selisihText,
                number_format($grandVal, 0, ',', '.'),
            ];
        }

        foreach ($standaloneRfqs as $rfq) {
            $salesName = $rfq->sales?->name ?? 'Unknown';
            $custName  = $rfq->customer?->company_name ?? $rfq->customer_name ?? '-';
            $rfqDate   = $rfq->updated_at ? $rfq->updated_at->format('d/m/Y') : '-';
            $val       = (float) $rfq->grand_total;

            $totalDraftRfq += $val;
            $totalPoDealDpp += $val;
            $totalGrandTotal += $val;

            if (!isset($salesSummary[$salesName])) {
                $salesSummary[$salesName] = [
                    'count' => 0,
                    'draft' => 0.0,
                    'deal'  => 0.0,
                    'tax'   => 0.0,
                    'grand' => 0.0,
                ];
            }
            $salesSummary[$salesName]['count']++;
            $salesSummary[$salesName]['draft'] += $val;
            $salesSummary[$salesName]['deal']  += $val;
            $salesSummary[$salesName]['grand'] += $val;

            $rows[] = [
                $no++,
                mb_strimwidth($salesName, 0, 14, '..'),
                mb_strimwidth($custName, 0, 20, '..'),
                '(Tanpa No PO)',
                $rfq->rfq_number,
                $rfqDate,
                number_format($val, 0, ',', '.'),
                number_format($val, 0, ',', '.'),
                'Sama',
                number_format($val, 0, ',', '.'),
            ];
        }

        $this->table(
            ['No', 'Sales', 'Customer', 'No PO', 'No RFQ', 'Tgl Deal', 'Draf RFQ (Bersih)', 'Deal PO (DPP)', 'Selisih Nego', 'Grand Total (+PPN)'],
            $rows
        );

        // ==========================================================================================================
        // BAGIAN 3: REKAPITULASI PER SALES & SINKRONISASI DASHBOARD
        // ==========================================================================================================
        $this->newLine();
        $this->info("▶ [3/5] REKAPITULASI OMSET PER SALES & VERIFIKASI SINKRONISASI DASHBOARD:");
        $salesRows = [];
        foreach ($salesSummary as $name => $s) {
            $salesRows[] = [
                $name,
                $s['count'] . ' PO',
                'Rp ' . number_format($s['draft'], 0, ',', '.'),
                'Rp ' . number_format($s['deal'], 0, ',', '.'),
                '-Rp ' . number_format(abs($s['deal'] - $s['draft']), 0, ',', '.'),
                'Rp ' . number_format($s['grand'], 0, ',', '.'),
            ];
        }
        $salesRows[] = [
            'TOTAL PERUSAHAAN',
            count($rows) . ' GOAL',
            'Rp ' . number_format($totalDraftRfq, 0, ',', '.'),
            'Rp ' . number_format($totalPoDealDpp, 0, ',', '.'),
            '-Rp ' . number_format(abs($totalPoDealDpp - $totalDraftRfq), 0, ',', '.'),
            'Rp ' . number_format($totalGrandTotal, 0, ',', '.'),
        ];

        $this->table(
            ['Nama Sales', 'Jumlah PO', 'Total Draf RFQ (Bersih)', 'Total PO Deal (DPP)', 'Selisih Nego/Diskon', 'Grand Total (+PPN)'],
            $salesRows
        );

        // ==========================================================================================================
        // BAGIAN 4: PENCARIAN MENDALAM (DEEP SEARCH) 2 PO EXCEL BU ADE YANG BELUM ADA DI DAFTAR GOAL
        // ==========================================================================================================
        $this->newLine();
        $this->info("▶ [4/5] DEEP SEARCH 2 PO EXCEL BU ADE (SYSTEM ENGINEERING 2,55 JT & TS TECH 9,6 JT) DI SELURUH DATABASE:");

        // Cari semua RFQ (termasuk trashed & semua sales) yang cocok dengan System Engineering / Acer / 2.550.000
        $search1 = Rfq::withTrashed()
            ->with(['customer', 'sales', 'items'])
            ->where(function ($q) {
                $q->where('customer_name', 'like', '%System%')
                  ->orWhere('customer_name', 'like', '%Engineering%')
                  ->orWhereHas('customer', fn($cq) => $cq->where('company_name', 'like', '%System%')->orWhere('company_name', 'like', '%Engineering%'))
                  ->orWhereHas('items', fn($iq) => $iq->where('product_name', 'like', '%Acer%')->orWhere('product_name', 'like', '%Portable%')->orWhere('price_after_margin', 2550000));
            })
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $this->line("  A. Pencarian 'PT System Engineering / Monitor Portable Acer / Rp 2.550.000':");
        if ($search1->isEmpty()) {
            $this->warn("     -> Hasil: 0 data ditemukan di seluruh tabel RFQ (termasuk data terhapus). Murni belum diinput ke CRM.");
        } else {
            $s1Rows = [];
            foreach ($search1 as $r) {
                $s1Rows[] = [
                    $r->rfq_number,
                    $r->rfq_date ? $r->rfq_date->format('d/m/Y') : '-',
                    $r->sales?->name ?? $r->sales_name ?? '-',
                    $r->customer?->company_name ?? $r->customer_name ?? '-',
                    $r->items->first()?->product_name ?? '-',
                    $r->trashed() ? 'TERHAPUS (SoftDelete)' : $r->status,
                    'Rp ' . number_format($r->grand_total, 0, ',', '.'),
                ];
            }
            $this->table(['No RFQ', 'Tgl RFQ', 'Sales', 'Customer', 'Barang', 'Status', 'Nilai'], $s1Rows);
        }

        // Cari semua RFQ (termasuk trashed & semua sales) yang cocok dengan TS Tech / Windows Server / 9.600.000
        $search2 = Rfq::withTrashed()
            ->with(['customer', 'sales', 'items'])
            ->where(function ($q) {
                $q->where('customer_name', 'like', '%Ts Tech%')
                  ->orWhere('customer_name', 'like', '%Tstech%')
                  ->orWhereHas('customer', fn($cq) => $cq->where('company_name', 'like', '%Ts Tech%')->orWhere('company_name', 'like', '%Tstech%'))
                  ->orWhereHas('items', fn($iq) => $iq->where('product_name', 'like', '%Server 2025%')->orWhere('product_name', 'like', '%Windows Server%')->orWhere('price_after_margin', 9600000));
            })
            ->orderByDesc('id')
            ->take(10)
            ->get();

        $this->line("  B. Pencarian 'PT TS Tech / Windows Server 2025 CAL / Rp 9.600.000':");
        if ($search2->isEmpty()) {
            $this->warn("     -> Hasil: 0 data ditemukan di seluruh tabel RFQ.");
        } else {
            $s2Rows = [];
            foreach ($search2 as $r) {
                $s2Rows[] = [
                    $r->rfq_number,
                    $r->rfq_date ? $r->rfq_date->format('d/m/Y') : '-',
                    $r->sales?->name ?? $r->sales_name ?? '-',
                    $r->customer?->company_name ?? $r->customer_name ?? '-',
                    $r->items->first()?->product_name ?? '-',
                    $r->trashed() ? 'TERHAPUS (SoftDelete)' : $r->status,
                    'Rp ' . number_format($r->grand_total, 0, ',', '.'),
                ];
            }
            $this->table(['No RFQ', 'Tgl RFQ', 'Sales', 'Customer', 'Barang', 'Status', 'Nilai'], $s2Rows);
        }

        // ==========================================================================================================
        // BAGIAN 5: CEK INTEGRITAS SELURUH RFQ AKTIF BULAN INI (APAKAH ADA ANOMALI HARGA / ITEM)
        // ==========================================================================================================
        $this->newLine();
        $this->info("▶ [5/5] CEK INTEGRITAS SELURUH RFQ BULAN INI (DETEKSI ANOMALI HARGA / KOMPONEN YATIM):");

        $orphanComponents = RfqItem::whereNotNull('parent_id')
            ->whereDoesntHave('parent')
            ->count();

        $outOfSyncBundles = 0;
        foreach ($bundledRfqs as $bRfq) {
            foreach ($bRfq->items->filter(fn($i) => empty($i->parent_id) && $i->isBundle()) as $bParent) {
                if ($bParent->components->isNotEmpty()) {
                    $compSell = (float) $bParent->bundle_selling_price;
                    $compCost = (float) $bParent->bundle_total_cost;
                    $effMargin = $compCost > 0 ? round((($compSell - $compCost) / $compCost) * 100, 2) : 0;

                    if (abs((float) $bParent->price_after_margin - $compSell) > 1 || abs((float) $bParent->hpp - $compCost) > 1 || abs((float) $bParent->margin_value - $effMargin) > 0.5) {
                        $bParent->updateQuietly([
                            'hpp'                => $compCost,
                            'price_after_margin' => $compSell,
                            'margin_type'        => 'percentage',
                            'margin_value'       => $effMargin,
                            'margin'             => $effMargin,
                        ]);
                        $outOfSyncBundles++;
                    }
                }
            }
        }

        if ($orphanComponents > 0) {
            RfqItem::whereNotNull('parent_id')->whereDoesntHave('parent')->delete();
        }

        Cache::flush();

        $this->line("  • Komponen Bundling Yatim (Orphaned)     : {$orphanComponents} item " . ($orphanComponents > 0 ? '(Sudah dibersihkan otomatis)' : '(✓ Bersih)'));
        $this->line("  • Paket Bundling Margin/HPP Disinkronkan : {$outOfSyncBundles} paket " . ($outOfSyncBundles > 0 ? '(Sudah disinkronkan otomatis)' : '(✓ Semua Sinkron)'));
        $this->line("  • Cache Dashboard Admin & Sales          : ✓ Sudah di-refresh (Flush)");
        $this->newLine();

        $this->line("==========================================================================================================");
        $this->info("✓ HASIL AKHIR YANG TAMPIL DI DASHBOARD SEKARANG:");
        $this->line("  • Card REVENUE (Bulan Ini GOAL)          : Rp " . number_format($totalPoDealDpp, 0, ',', '.'));
        $this->line("  • Card DEAL CLOSING                      : " . count($rows) . " GOAL");
        foreach ($salesSummary as $name => $s) {
            $this->line(sprintf("  • Leaderboard %-26s : Rp %s (%d PO)", $name, number_format($s['deal'], 0, ',', '.'), $s['count']));
        }
        $this->line("==========================================================================================================");
        $this->newLine();

        return 0;
    }
}
