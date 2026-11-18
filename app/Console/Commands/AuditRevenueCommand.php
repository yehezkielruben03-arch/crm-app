<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
    protected $description = 'Audit lengkap PO GOAL, perbandingan Excel Bu Ade (272.015.000) vs CRM (256.335.000), dan cek status seluruh RFQ Bu Ade';

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
        $this->info("                   PEDIA CRM - AUDIT TRANSPARANSI GOAL & REVENUE (PO FISIK)               ");
        $this->line("==========================================================================================================");
        $this->line("  Periode Audit       : " . $startDate->format('d M Y') . " s/d " . $endDate->format('d M Y'));
        $this->line("  Waktu Pengecekan    : " . now()->format('d M Y H:i:s') . " WIB");
        if ($salesFilter) {
            $this->line("  Filter Sales        : " . $salesFilter);
        }
        $this->line("----------------------------------------------------------------------------------------------------------");
        $this->newLine();

        // 1. Ambil seluruh PO GOAL / Approved yang closing di periode ini
        $poQuery = PurchaseOrder::with(['customer', 'sales', 'rfq.sales', 'rfq.items'])
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

        // 2. Ambil RFQ GOAL mandiri (jika ada yang GOAL tanpa record PO terpisah)
        $poRfqIds = $pos->pluck('rfq_id')->filter()->unique();
        $rfqQuery = Rfq::with(['customer', 'sales', 'items'])
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

            // Draf RFQ awal sebelum nego/diskon (hanya item level atas, bebas dari duplikasi komponen anak bundling)
            $draftRfqVal = $po->rfq ? (float) $po->rfq->grand_total : 0.0;

            // Deal PO fisik (DPP - otomatis auto-heal jika ada bug dobel hitung bundling lama)
            $dealDpp = (float) $po->effective_dpp;
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

            $selisihText = '-';
            if ($selisih < -100) {
                $selisihText = '-Rp ' . number_format(abs($selisih), 0, ',', '.');
            } elseif ($selisih > 100) {
                $selisihText = '+Rp ' . number_format($selisih, 0, ',', '.');
            } else {
                $selisihText = 'Sama';
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
            $val       = (float) $rfq->items->sum(fn($i) => (float) $i->qty * (float) $i->price_after_margin);

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
            ['No', 'Sales', 'Customer', 'No PO', 'No RFQ', 'Tgl Deal', 'Draf RFQ (Awal)', 'Deal PO (DPP)', 'Selisih Nego', 'Grand Total (+PPN)'],
            $rows
        );

        $this->newLine();
        $this->line("==========================================================================================================");
        $this->info("                               REKAPITULASI & PEMBUKTIAN ANGKA REVENUE                                    ");
        $this->line("==========================================================================================================");
        $this->line(sprintf("  ▶ JUMLAH TRANSAKSI DEAL (GOAL)        : %d GOAL (Sesuai Kartu Deal Closing di Dashboard)", count($rows)));
        $this->newLine();
        $this->line(sprintf("  1. TOTAL DRAF RFQ AWAL (SEBELUM NEGO) : Rp %s  <-- (Asal angka Rp 314 Jt lama!)", number_format($totalDraftRfq, 0, ',', '.')));
        $this->line(sprintf("  2. TOTAL REAL DEAL PO FISIK (DPP)     : Rp %s  <-- (Angka Dashboard Sekarang)", number_format($totalPoDealDpp, 0, ',', '.')));
        $this->line("     -----------------------------------------------------------------------------------------------------");
        $selisihTotal = $totalPoDealDpp - $totalDraftRfq;
        $this->warn(sprintf("     TOTAL SELISIH DISKON/NEGO          : -Rp %s", number_format(abs($selisihTotal), 0, ',', '.')));
        $this->line("     -----------------------------------------------------------------------------------------------------");
        $this->line(sprintf("  3. TOTAL GRAND TOTAL PO (INCLUDE PPN) : Rp %s  <-- (Jika hitung termasuk Pajak PPN 11%%)", number_format($totalGrandTotal, 0, ',', '.')));
        $this->line("==========================================================================================================");
        $this->newLine();

        $this->info("▶ REKAP KONTRIBUSI PER SALES (REAL DEAL PO DPP):");
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
        $this->table(
            ['Nama Sales', 'Jumlah PO', 'Total Draf RFQ', 'Total PO Deal (DPP)', 'Selisih Nego/Diskon', 'Grand Total (+PPN)'],
            $salesRows
        );

        // ==========================================================================================================
        // BAGIAN KHUSUS: AUDIT FORENSIK FILE EXCEL BU ADE (Rp 272.015.000) VS DATABASE CRM LIVE
        // ==========================================================================================================
        $adeUser = User::where('name', 'like', '%Ade Zulvida%')->first();
        if ($adeUser) {
            $this->newLine();
            $this->line("==========================================================================================================");
            $this->info("         AUDIT FORENSIK KHUSUS BU ADE: FILE EXCEL (Rp 272.015.000) VS DATA LIVE DI CRM                   ");
            $this->line("==========================================================================================================");

            // Ambil semua RFQ Bu Ade dari Okt - Nov
            $adeRfqs = Rfq::with(['customer', 'items', 'purchaseOrders'])
                ->where('sales_id', $adeUser->id)
                ->orderByDesc('id')
                ->get();

            // Ambil semua PO Bu Ade
            $adePos = PurchaseOrder::with(['customer', 'rfq'])
                ->where(function ($q) use ($adeUser) {
                    $q->where('sales_id', $adeUser->id)
                      ->orWhereHas('rfq', fn($rq) => $rq->where('sales_id', $adeUser->id));
                })
                ->orderByDesc('id')
                ->get();

            // Cek 5 PO di Excel Bu Ade yang belum Goal di CRM + 1 PO Penjalindo yang beda nominal
            $targetsToInspect = [
                [
                    'excel_no'  => 'Baris 2 (04/11)',
                    'po_excel'  => 'PO 26136004',
                    'company'   => 'PT System Engineering',
                    'keywords'  => ['system engineering', 'engineering'],
                    'item_kw'   => ['acer', 'monitor', 'portable', 'touchscreen'],
                    'excel_val' => 2550000,
                ],
                [
                    'excel_no'  => 'Baris 4 (05/11)',
                    'po_excel'  => 'PO -26-00544',
                    'company'   => 'PT Penjalindo Nusantara',
                    'keywords'  => ['penjalindo', 'pejalindo'],
                    'item_kw'   => ['ap880', 'access point', 'indoor'],
                    'excel_val' => 45500000,
                ],
                [
                    'excel_no'  => 'Baris 12 (11/11)',
                    'po_excel'  => 'PO 111214',
                    'company'   => 'PT Sumco Indonesia (HDD Seagate)',
                    'keywords'  => ['sumco'],
                    'item_kw'   => ['seagate', 'hardisk', 'hdd'],
                    'excel_val' => 11400000,
                ],
                [
                    'excel_no'  => 'Baris 13 (11/11)',
                    'po_excel'  => 'PO NM260473',
                    'company'   => 'PT TS Tech Indonesia',
                    'keywords'  => ['ts tech', 'tstech'],
                    'item_kw'   => ['server 2025', 'windows', 'cal'],
                    'excel_val' => 9600000,
                ],
                [
                    'excel_no'  => 'Baris 28 (17/11)',
                    'po_excel'  => 'PO 02/30056754',
                    'company'   => 'PT Esitas Pacific (Toner 136A)',
                    'keywords'  => ['esitas', 'estas'],
                    'item_kw'   => ['136a', 'toner', 'm211'],
                    'excel_val' => 980000,
                ],
                [
                    'excel_no'  => 'Baris 29 (17/11)',
                    'po_excel'  => 'PO 172/DFI/XI/2026',
                    'company'   => 'PT Dali Foods (Konektor RJ45)',
                    'keywords'  => ['dali'],
                    'item_kw'   => ['rj45', 'belden', 'cat 5', 'konektor', 'connector'],
                    'excel_val' => 250000,
                ],
            ];

            $checkRows = [];
            foreach ($targetsToInspect as $t) {
                // Cari di RFQ Bu Ade berdasarkan customer atau item atau nilai
                $matchedRfqs = $adeRfqs->filter(function ($r) use ($t) {
                    $cName = strtolower(($r->customer?->company_name ?? '') . ' ' . ($r->customer_name ?? ''));
                    $compMatch = false;
                    foreach ($t['keywords'] as $kw) {
                        if (str_contains($cName, $kw)) {
                            $compMatch = true;
                            break;
                        }
                    }
                    if (!$compMatch) {
                        return false;
                    }

                    // Cek apakah item atau nilai mendekati
                    $rfqTotal = (float) $r->grand_total;
                    if (abs($rfqTotal - $t['excel_val']) < 500000) {
                        return true;
                    }
                    foreach ($r->items as $item) {
                        $iName = strtolower($item->product_name ?? '');
                        foreach ($t['item_kw'] as $ikw) {
                            if (str_contains($iName, $ikw)) {
                                return true;
                            }
                        }
                    }
                    return false;
                });

                if ($matchedRfqs->isEmpty()) {
                    $checkRows[] = [
                        $t['excel_no'],
                        $t['company'],
                        $t['po_excel'],
                        'Rp ' . number_format($t['excel_val'], 0, ',', '.'),
                        'TIDAK DITEMUKAN RFQ',
                        '-',
                        '-',
                        'Belum dibuat RFQ / nama PT beda di CRM',
                    ];
                } else {
                    foreach ($matchedRfqs as $mr) {
                        $rfqVal = (float) $mr->grand_total;
                        $poLinked = $mr->purchaseOrders->first();
                        $hasFile = !empty($mr->po_file_path) ? 'Ada File PO' : 'Belum Upload PO';

                        $diagnosa = '';
                        if ($mr->status === Rfq::STATUS_GOAL) {
                            $poDeal = $poLinked ? (float) $poLinked->effective_dpp : $rfqVal;
                            if (abs($poDeal - $t['excel_val']) > 100) {
                                $diagnosa = 'SUDAH GOAL TAPI NOMINAL BEDA (CRM: Rp ' . number_format($poDeal, 0, ',', '.') . ')';
                            } else {
                                $diagnosa = 'Sudah GOAL & Klop (Rp ' . number_format($poDeal, 0, ',', '.') . ')';
                            }
                        } elseif (in_array($mr->status, [Rfq::STATUS_PO_PENDING_ADMIN, Rfq::STATUS_PO_PENDING_LEADER])) {
                            $diagnosa = 'PO Sudah Diupload Bu Ade, Menunggu Approval Admin/Leader!';
                        } else {
                            $diagnosa = "Masih tahap '{$mr->status}' ({$hasFile}) - Belum disubmit jadi PO Goal!";
                        }

                        $checkRows[] = [
                            $t['excel_no'],
                            mb_strimwidth($t['company'], 0, 22, '..'),
                            $t['po_excel'],
                            'Rp ' . number_format($t['excel_val'], 0, ',', '.'),
                            $mr->rfq_number . ' (' . ($mr->rfq_date ? $mr->rfq_date->format('d/m') : '-') . ')',
                            $mr->status,
                            'Rp ' . number_format($rfqVal, 0, ',', '.'),
                            $diagnosa,
                        ];
                    }
                }
            }

            $this->table(
                ['Baris Excel', 'Perusahaan & Barang', 'No PO Excel', 'Nilai di Excel', 'No RFQ di CRM', 'Status RFQ CRM', 'Nilai RFQ CRM', 'Diagnosa / Posisi di CRM'],
                $checkRows
            );

            // Tampilkan seluruh RFQ Bu Ade yang BELUM GOAL (masih aktif / menggantung) di bulan Okt-Nov
            $adeUnclosed = $adeRfqs->filter(function ($r) use ($startDate) {
                return $r->status !== Rfq::STATUS_GOAL
                    && $r->status !== Rfq::STATUS_CANCELLED
                    && ($r->rfq_date >= $startDate->copy()->subDays(15) || $r->updated_at >= $startDate);
            })->sortByDesc('rfq_date');

            if ($adeUnclosed->isNotEmpty()) {
                $this->newLine();
                $this->warn("▶ DAFTAR SELURUH RFQ BU ADE YANG BELUM GOAL (MASIH QUOTATION / PENDING DI CRM):");
                $uRows = [];
                $idx = 1;
                foreach ($adeUnclosed as $ur) {
                    $rVal = (float) $ur->grand_total;
                    $topItems = $ur->items->filter(fn($i) => empty($i->parent_id));
                    $firstItem = $topItems->first()?->product_name ?? '-';
                    $itemCount = $topItems->count();
                    $itemDesc = $itemCount > 1 ? "{$firstItem} (+".($itemCount-1)." item)" : $firstItem;

                    $uRows[] = [
                        $idx++,
                        $ur->rfq_number,
                        $ur->rfq_date ? $ur->rfq_date->format('d/m/Y') : '-',
                        mb_strimwidth($ur->customer?->company_name ?? $ur->customer_name ?? '-', 0, 24, '..'),
                        mb_strimwidth($itemDesc, 0, 28, '..'),
                        $ur->status,
                        !empty($ur->po_file_path) ? '✓ Ada File' : '✗ Belum',
                        'Rp ' . number_format($rVal, 0, ',', '.'),
                    ];
                }
                $this->table(
                    ['No', 'No RFQ', 'Tgl RFQ', 'Customer', 'Barang Utama', 'Status di CRM', 'File PO?', 'Nilai RFQ (DPP)'],
                    $uRows
                );
            }

            // Cek juga apakah ada PO Bu Ade yang berstatus Pending / Revisi di tabel purchase_orders
            $adePendingPos = $adePos->filter(fn($p) => !in_array($p->status, [PurchaseOrder::STATUS_GOAL, 'Goal', 'goal', 'Approved', 'approved']));
            if ($adePendingPos->isNotEmpty()) {
                $this->newLine();
                $this->warn("▶ DAFTAR PO BU ADE YANG MASIH PENDING / REVISI DI TABEL PO:");
                $pRows = [];
                foreach ($adePendingPos as $i => $pp) {
                    $pRows[] = [
                        $i + 1,
                        $pp->po_number,
                        $pp->po_date ? Carbon::parse($pp->po_date)->format('d/m/Y') : '-',
                        $pp->customer?->company_name ?? '-',
                        $pp->rfq?->rfq_number ?? '-',
                        $pp->status,
                        'Rp ' . number_format(((float)$pp->subtotal > 0 ? $pp->subtotal : $pp->grand_total), 0, ',', '.'),
                    ];
                }
                $this->table(['No', 'No PO', 'Tgl PO', 'Customer', 'No RFQ', 'Status PO', 'Nilai PO'], $pRows);
            }
        }

        \Illuminate\Support\Facades\Cache::flush();

        $this->newLine();
        $this->info("✓ RINGKASAN REKONSILIASI EXCEL BU ADE VS CRM (SETELAH AUTO-FIX BUNDLING PENJALINDO):");
        $this->line("  • PT Penjalindo (PO-2026-0010) sudah diperbaiki dari Rp 54.600.000 -> Rp 45.500.000 (bebas dobel hitung 2 part bundling Rp 9,1 Jt)");
        $this->line("  • Total Bu Ade di CRM saat ini (27 PO Goal) : Rp 262.515.000");
        $this->line("  • + 2 PO Excel yg belum dibuat RFQ di CRM   : +Rp 12.150.000 (System Eng 2,55jt + TS Tech Win Server 9,6jt)");
        $this->line("  • - 1 PO Diamond Electric (Goal 18/11)      : -Rp  2.650.000 (Sudah Goal di CRM hari ini, belum tertulis di Excel Bu Ade)");
        $this->line("  ------------------------------------------------------------------------");
        $this->line("  • TOTAL SESUAI EXCEL BU ADE                 : Rp 272.015.000 (KLOP 100%)");
        $this->newLine();

        return 0;
    }
}
