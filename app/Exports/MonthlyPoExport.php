<?php

namespace App\Exports;

use App\Services\MonthlyPoReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonthlyPoExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private readonly int $month,
        private readonly int $year,
        private readonly Collection $rows,
        private readonly bool $multiSheetBySales = false,
        private readonly ?string $singleSalesName = null,
        private readonly ?MonthlyPoReportService $reportService = null,
    ) {
    }

    /**
     * @return array<int, MonthlyPoSalesSheet>
     */
    public function sheets(): array
    {
        $usedTitles = [];

        if (!$this->multiSheetBySales) {
            $salesLabel = $this->singleSalesName ?: 'Sales';
            $sheetTitle = $this->makeUniqueSheetTitle($salesLabel, $usedTitles);

            return [
                new MonthlyPoSalesSheet(
                    sheetTitle: $sheetTitle,
                    subtitleLabel: 'Sales: ' . $salesLabel,
                    month: $this->month,
                    year: $this->year,
                    rows: $this->rows,
                    includeSalesColumn: false,
                ),
            ];
        }

        $sheets = [];

        // Sheet 1: Rekap Semua Sales (gabungan semua PO bulan tersebut + kolom Sales)
        $rekapTitle = $this->makeUniqueSheetTitle('Rekap Semua Sales', $usedTitles);
        $sheets[] = new MonthlyPoSalesSheet(
            sheetTitle: $rekapTitle,
            subtitleLabel: 'Semua Sales (Rekap Gabungan)',
            month: $this->month,
            year: $this->year,
            rows: $this->rows,
            includeSalesColumn: true,
        );

        // Sheet 2..N: Sheet terpisah untuk masing-masing Sales (4 kolom polos: Tanggal | Nama PT | Nama Item | Harga Exclude)
        $service = $this->reportService ?? app(MonthlyPoReportService::class);
        $grouped = $service->groupRowsBySales($this->rows);

        foreach ($grouped as $salesGroup) {
            $salesName = $salesGroup['sales_name'];
            $sheetTitle = $this->makeUniqueSheetTitle($salesName, $usedTitles);

            $sheets[] = new MonthlyPoSalesSheet(
                sheetTitle: $sheetTitle,
                subtitleLabel: 'Sales: ' . $salesName,
                month: $this->month,
                year: $this->year,
                rows: $salesGroup['rows'],
                includeSalesColumn: false,
            );
        }

        return $sheets;
    }

    /**
     * Sanitasi judul worksheet agar selalu patuh pada batas PhpSpreadsheet (max 31 char & tanpa : \ / ? * [ ]).
     *
     * @param  array<int, string>  $usedTitles
     */
    private function makeUniqueSheetTitle(string $rawTitle, array &$usedTitles): string
    {
        $clean = preg_replace('/[:\\\\\/?*\[\]]/', ' ', $rawTitle) ?? $rawTitle;
        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);
        if ($clean === '') {
            $clean = 'Laporan PO';
        }

        $base = mb_substr($clean, 0, 31);
        $candidate = $base;
        $suffix = 2;

        while (in_array(mb_strtolower($candidate), $usedTitles, true)) {
            $tag = " ({$suffix})";
            $candidate = mb_substr($clean, 0, 31 - mb_strlen($tag)) . $tag;
            $suffix++;
        }

        $usedTitles[] = mb_strtolower($candidate);

        return $candidate;
    }
}
