<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CheckSalesPoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crm:check-po 
                            {--month= : Bulan (1-12, default bulan ini)} 
                            {--year= : Tahun (default tahun ini)} 
                            {--sales= : Filter nama sales (opsional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit detail PO aktual dan omset setiap sales di database vs angka dashboard CRM';

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
        $this->line("==================================================================================");
        $this->info("             PEDIA CRM - AUDIT REALTIME PO & OMSET SETIAP SALES                   ");
        $this->line("==================================================================================");
        $this->line("  Periode Audit : " . $startDate->format('d M Y') . " s/d " . $endDate->format('d M Y'));
        $this->line("  Waktu Cek     : " . now()->format('d M Y H:i:s') . " WIB");
        if ($salesFilter) {
            $this->line("  Filter Sales  : " . $salesFilter);
        }
        $this->line("----------------------------------------------------------------------------------");
        $this->newLine();

        $salesQuery = User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where('status', 'Active')
            ->orderBy('name');

        if ($salesFilter) {
            $salesQuery->where('name', 'like', "%{$salesFilter}%");
        }

        $salesList = $salesQuery->get();

        if ($salesList->isEmpty()) {
            $this->warn("Tidak ditemukan sales aktif dengan kriteria yang diminta.");
            return 0;
        }

        $grandSummary = [];

        foreach ($salesList as $sales) {
            $this->info("▶ SALES: {$sales->name} (ID: {$sales->id})");

            $targetAmount = $sales->salesTargets()
                ->where('target_month', $month)
                ->where('target_year', $year)
                ->first()?->target_amount ?? $sales->monthly_target ?? 0;

            // 1. Ambil seluruh PO Goal aktual di bulan ini (baik via po_date, created_at, atau approved_at)
            $pos = PurchaseOrder::with(['customer', 'rfq.items'])
                ->where(function ($sq) use ($sales) {
                    $sq->where('sales_id', $sales->id)
                       ->orWhereHas('rfq', fn($rq) => $rq->where('sales_id', $sales->id));
                })
                ->whereIn('status', [PurchaseOrder::STATUS_GOAL, 'Goal', 'goal', 'Approved', 'approved'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('po_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->orWhereBetween('created_at', [$startDate, $endDate])
                      ->orWhereBetween('approved_at', [$startDate, $endDate]);
                })
                ->get();

            // 1b. Cek apakah ada PO yang BELUM Goal (Pending / Revisi) yang mungkin dikira sudah approve
            $pendingPos = PurchaseOrder::with(['customer', 'rfq'])
                ->where(function ($sq) use ($sales) {
                    $sq->where('sales_id', $sales->id)
                       ->orWhereHas('rfq', fn($rq) => $rq->where('sales_id', $sales->id));
                })
                ->whereNotIn('status', [PurchaseOrder::STATUS_GOAL, 'Goal', 'goal', 'Approved', 'approved'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('po_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                      ->orWhereBetween('created_at', [$startDate, $endDate]);
                })
                ->get();

            // 2. Ambil seluruh RFQ Goal di bulan ini (berdasarkan updated_at atau rfq_date)
            $rfqsGoal = Rfq::with(['customer', 'items', 'purchaseOrders'])
                ->where('sales_id', $sales->id)
                ->whereIn('status', [Rfq::STATUS_GOAL, 'Goal', 'goal', 'GOAL'])
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('updated_at', [$startDate, $endDate])
                      ->orWhereBetween('rfq_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                })
                ->get();

            // 3. Hitungan yang SAAT INI dipakai oleh widget dashboard lama (WHERE status='GOAL' AND MONTH(rfq_date)=$month)
            $legacyRfqs = Rfq::with('items')
                ->where('sales_id', $sales->id)
                ->where('status', Rfq::STATUS_GOAL)
                ->whereMonth('rfq_date', $month)
                ->whereYear('rfq_date', $year)
                ->get();

            $legacyDashboardAchieved = $legacyRfqs->sum(function ($rfq) {
                return $rfq->items->sum(function ($item) {
                    return (float) $item->qty * (float) $item->price_after_margin;
                });
            });

            // Satukan transaksi agar tidak ada duplikasi data
            $transactions = collect();

            // Masukkan data dari tabel PurchaseOrder
            foreach ($pos as $po) {
                $rfq = $po->rfq;
                $rfqDate = $rfq ? Carbon::parse($rfq->rfq_date) : null;
                $poDate = $po->po_date ? Carbon::parse($po->po_date) : Carbon::parse($po->created_at);

                // Apakah transaksi ini masuk ke hitungan dashboard lama?
                $inLegacyDashboard = false;
                $legacyReason = '';
                if ($rfq && $rfq->status === Rfq::STATUS_GOAL && $rfqDate && $rfqDate->month === $month && $rfqDate->year === $year) {
                    $inLegacyDashboard = true;
                    $legacyReason = 'Masuk (RFQ & PO di bulan ini)';
                } else {
                    $inLegacyDashboard = false;
                    if (!$rfq) {
                        $legacyReason = 'Hilang: PO tanpa RFQ (Dashboard lama hanya baca tabel RFQ)';
                    } elseif ($rfq->status !== Rfq::STATUS_GOAL) {
                        $legacyReason = 'Hilang: Status RFQ bukan GOAL';
                    } elseif ($rfqDate && ($rfqDate->month !== $month || $rfqDate->year !== $year)) {
                        $legacyReason = "Hilang: RFQ dibuat bln {$rfqDate->format('m/Y')} (Dashboard lama filter rfq_date)";
                    }
                }

                $rfqItemSum = $rfq ? $rfq->items->sum(fn($i) => (float) $i->qty * (float) $i->price_after_margin) : 0;

                $transactions->put("po_{$po->id}", [
                    'source'           => 'PO',
                    'id'               => $po->id,
                    'po_number'        => $po->po_number ?: '-',
                    'rfq_number'       => $rfq?->rfq_number ?: ($po->rfq_reference ?: '-'),
                    'customer'         => $po->customer?->company_name ?? ($rfq?->customer_name ?? '-'),
                    'rfq_date'         => $rfqDate ? $rfqDate->format('d/m/Y') : '-',
                    'po_date'          => $poDate->format('d/m/Y'),
                    'subtotal'         => (float) $po->subtotal,
                    'tax_amount'       => (float) $po->tax_amount,
                    'grand_total'      => (float) $po->grand_total,
                    'rfq_item_sum'     => (float) $rfqItemSum,
                    'in_dashboard'     => $inLegacyDashboard,
                    'legacy_reason'    => $legacyReason,
                ]);
            }

            // Cek apakah ada RFQ Goal di bulan ini yang belum masuk dari tabel PO
            foreach ($rfqsGoal as $rfq) {
                $alreadyCounted = $transactions->contains(function ($t) use ($rfq) {
                    return $t['rfq_number'] === $rfq->rfq_number;
                });

                if (!$alreadyCounted) {
                    $rfqDate = Carbon::parse($rfq->rfq_date);
                    $goalDate = Carbon::parse($rfq->updated_at);
                    $rfqItemSum = $rfq->items->sum(fn($i) => (float) $i->qty * (float) $i->price_after_margin);

                    $inLegacyDashboard = ($rfqDate->month === $month && $rfqDate->year === $year);
                    $legacyReason = $inLegacyDashboard 
                        ? 'Masuk (RFQ dibuat di bulan ini)' 
                        : "Hilang: RFQ dibuat bln {$rfqDate->format('m/Y')} (Dashboard lama filter rfq_date)";

                    $tax = round($rfqItemSum * 0.11);
                    $transactions->put("rfq_{$rfq->id}", [
                        'source'           => 'RFQ',
                        'id'               => $rfq->id,
                        'po_number'        => '(Dari RFQ GOAL)',
                        'rfq_number'       => $rfq->rfq_number,
                        'customer'         => $rfq->customer?->company_name ?? $rfq->customer_name,
                        'rfq_date'         => $rfqDate->format('d/m/Y'),
                        'po_date'          => $goalDate->format('d/m/Y'),
                        'subtotal'         => (float) $rfqItemSum,
                        'tax_amount'       => (float) $tax,
                        'grand_total'      => (float) ($rfqItemSum + $tax),
                        'rfq_item_sum'     => (float) $rfqItemSum,
                        'in_dashboard'     => $inLegacyDashboard,
                        'legacy_reason'    => $legacyReason,
                    ]);
                }
            }

            // Tampilkan tabel rincian transaksi jika ada
            if ($transactions->isEmpty()) {
                $this->line("  Belum ada transaksi PO Goal di periode ini.");
                if ($pendingPos->isNotEmpty()) {
                    $this->warn("  [!] PERHATIAN: Ditemukan {$pendingPos->count()} PO yang BELUM berstatus GOAL / Approved (masih Pending/Draft):");
                    $pendingRows = [];
                    foreach ($pendingPos as $p) {
                        $pendingRows[] = [
                            \Illuminate\Support\Str::limit($p->customer?->company_name ?? ($p->rfq?->customer_name ?? '-'), 22),
                            $p->po_number ?: '-',
                            $p->status,
                            number_format((float) $p->grand_total, 0, ',', '.'),
                            $p->po_date ? Carbon::parse($p->po_date)->format('d/m/Y') : $p->created_at->format('d/m/Y'),
                        ];
                    }
                    $this->table(['Pelanggan', 'No PO', 'Status Saat Ini', 'Grand Total', 'Tgl PO'], $pendingRows);
                }
                $this->newLine();
            } else {
                $tableRows = [];
                $no = 1;
                $totPoGrand = 0;
                $totPoSub = 0;

                foreach ($transactions as $t) {
                    $totPoGrand += $t['grand_total'];
                    $totPoSub   += $t['subtotal'];

                    $statusCol = $t['in_dashboard'] 
                        ? '<info>✓ MASUK</info>' 
                        : '<fg=red>✗ HILANG</fg=red>';

                    $tableRows[] = [
                        $no++,
                        \Illuminate\Support\Str::limit($t['customer'], 22),
                        $t['po_number'],
                        $t['rfq_number'],
                        $t['rfq_date'],
                        $t['po_date'],
                        number_format($t['subtotal'], 0, ',', '.'),
                        number_format($t['grand_total'], 0, ',', '.'),
                        $statusCol,
                        $t['legacy_reason'],
                    ];
                }

                $this->table([
                    'No',
                    'Pelanggan',
                    'No PO',
                    'No RFQ',
                    'Tgl RFQ',
                    'Tgl Goal/PO',
                    'Subtotal (DPP)',
                    'Grand Total PO',
                    'Di Dashboard?',
                    'Catatan / Alasan',
                ], $tableRows);

                // Rekap Sales Ini
                $diff = $totPoGrand - $legacyDashboardAchieved;
                $pctAktual = $targetAmount > 0 ? min(round(($totPoGrand / $targetAmount) * 100, 1), 100) : 0;
                $pctDashboard = $targetAmount > 0 ? min(round(($legacyDashboardAchieved / $targetAmount) * 100, 1), 100) : 0;

                $this->line("  ┌─ Ringkasan Performa {$sales->name}:");
                $this->line("  │  • Total Aktual PO (Grand Total) : Rp " . number_format($totPoGrand, 0, ',', '.') . " ({$pctAktual}%)");
                $this->line("  │  • Total Aktual PO (DPP/Subtotal): Rp " . number_format($totPoSub, 0, ',', '.'));
                $this->line("  │  • Angka di Dashboard Saat Ini   : Rp " . number_format($legacyDashboardAchieved, 0, ',', '.') . " ({$pctDashboard}%)");
                if ($diff != 0) {
                    $this->error("  │  • SELISIH OMSET HILANG / BEDA   : Rp " . number_format($diff, 0, ',', '.') . " !");
                } else {
                    $this->info("  │  • SELISIH OMSET                 : Rp 0 (SINKRON)");
                }
                $this->line("  └─────────────────────────────────────────────────────────────────");

                if ($pendingPos->isNotEmpty()) {
                    $this->warn("  [!] PERHATIAN: Ditemukan {$pendingPos->count()} PO yang BELUM berstatus GOAL / Approved (masih Pending/Draft):");
                    $pendingRows = [];
                    foreach ($pendingPos as $p) {
                        $pendingRows[] = [
                            \Illuminate\Support\Str::limit($p->customer?->company_name ?? ($p->rfq?->customer_name ?? '-'), 22),
                            $p->po_number ?: '-',
                            $p->status,
                            number_format((float) $p->grand_total, 0, ',', '.'),
                            $p->po_date ? Carbon::parse($p->po_date)->format('d/m/Y') : $p->created_at->format('d/m/Y'),
                        ];
                    }
                    $this->table(['Pelanggan', 'No PO', 'Status Saat Ini', 'Grand Total', 'Tgl PO'], $pendingRows);
                }

                $this->newLine();

                $grandSummary[] = [
                    'name'            => $sales->name,
                    'actual_po_count' => $transactions->count(),
                    'actual_subtotal' => $totPoSub,
                    'actual_grand'    => $totPoGrand,
                    'dashboard_old'   => $legacyDashboardAchieved,
                    'difference'      => $diff,
                    'target'          => $targetAmount,
                    'pct_actual'      => $pctAktual . '%',
                    'pct_dashboard'   => $pctDashboard . '%',
                ];
            }
        }

        // Tampilkan Tabel Rekap Seluruh Sales
        if (!empty($grandSummary)) {
            $this->info("==================================================================================");
            $this->info("                 REKAP PERBANDINGAN SELURUH SALES MARKETING                       ");
            $this->info("==================================================================================");

            $summaryRows = [];
            foreach ($grandSummary as $s) {
                $diffCol = $s['difference'] > 0 
                    ? "<fg=red>+Rp " . number_format($s['difference'], 0, ',', '.') . "</fg=red>"
                    : "Rp 0";

                $summaryRows[] = [
                    $s['name'],
                    $s['actual_po_count'],
                    'Rp ' . number_format($s['actual_grand'], 0, ',', '.'),
                    $s['pct_actual'],
                    'Rp ' . number_format($s['dashboard_old'], 0, ',', '.'),
                    $s['pct_dashboard'],
                    $diffCol,
                ];
            }

            $this->table([
                'Nama Sales',
                'Jumlah PO',
                'Aktual PO (Grand Total)',
                '% Aktual',
                'Dashboard Saat Ini',
                '% Dashboard',
                'Selisih Belum Masuk',
            ], $summaryRows);

            $this->newLine();
            $this->comment("Petunjuk: Kolom 'Selisih Belum Masuk' adalah transaksi PO sah bulan ini yang tereliminasi");
            $this->comment("karena dashboard lama memfilter 'rfq_date' (tanggal draf penawaran), bukan tanggal PO Goal.");
            $this->newLine();
        }

        return 0;
    }
}
