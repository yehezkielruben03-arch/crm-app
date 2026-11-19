<?php

namespace App\Exports;

use App\Services\MonthlyPoReportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class MonthlyPoSalesSheet implements FromArray, WithTitle, WithEvents
{
    private const CURRENCY_FORMAT = '"Rp "#,##0';

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly string $subtitleLabel,
        private readonly int $month,
        private readonly int $year,
        private readonly Collection $rows,
        private readonly bool $includeSalesColumn = false,
    ) {
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        $periodLabel = strtoupper(MonthlyPoReportService::monthName($this->month) . ' ' . $this->year);
        $colCount = $this->includeSalesColumn ? 5 : 4;

        $data = [];

        // Row 1: Judul Utama
        $titleRow = array_fill(0, $colCount, '');
        $titleRow[0] = "LAPORAN PO BULANAN - {$periodLabel}";
        $data[] = $titleRow;

        // Row 2: Subtitle Sales & Jumlah PO
        $subRow = array_fill(0, $colCount, '');
        $subRow[0] = $this->subtitleLabel;
        $subRow[$colCount - 1] = 'Total Transaksi: ' . $this->rows->count() . ' PO';
        $data[] = $subRow;

        // Row 3: Spacer
        $data[] = array_fill(0, $colCount, '');

        // Row 4: Header Tabel
        $data[] = $this->includeSalesColumn
            ? ['Tanggal', 'Sales', 'Nama PT', 'Nama Item', 'Harga Exclude']
            : ['Tanggal', 'Nama PT', 'Nama Item', 'Harga Exclude'];

        // Row 5..N: Data PO
        if ($this->rows->isEmpty()) {
            $emptyRow = array_fill(0, $colCount, '');
            $emptyRow[0] = 'Tidak ada data PO GOAL pada periode ' . MonthlyPoReportService::monthName($this->month) . ' ' . $this->year . '.';
            $data[] = $emptyRow;
        } else {
            foreach ($this->rows as $row) {
                $tanggal = $this->sanitizeText((string) ($row['date_display'] ?? '-'));
                $customer = $this->sanitizeText((string) ($row['customer_name'] ?? '-'));
                $item = $this->sanitizeText((string) ($row['item_summary'] ?? '-'));
                $price = (float) ($row['exclude_price'] ?? 0);

                if ($this->includeSalesColumn) {
                    $sales = $this->sanitizeText((string) ($row['sales_name'] ?? '-'));
                    $data[] = [$tanggal, $sales, $customer, $item, $price];
                } else {
                    $data[] = [$tanggal, $customer, $item, $price];
                }
            }
        }

        // Row Akhir: Baris TOTAL (nilai rumus diisi eksplisit pada AfterSheet)
        $totalRow = array_fill(0, $colCount, '');
        $totalRow[0] = 'TOTAL HARGA EXCLUDE (DPP)';
        $totalRow[$colCount - 1] = 0;
        $data[] = $totalRow;

        return $data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->includeSalesColumn ? 'E' : 'D';
                $labelEndCol = $this->includeSalesColumn ? 'D' : 'C';
                $itemCol = $this->includeSalesColumn ? 'D' : 'C';
                $ptCol = $this->includeSalesColumn ? 'C' : 'B';

                $hasRows = $this->rows->isNotEmpty();
                $firstDataRow = 5;
                $lastDataRow = $hasRows ? (4 + $this->rows->count()) : 5;
                $totalRow = $lastDataRow + 1;

                // 1. Lebar Kolom
                $widths = $this->includeSalesColumn
                    ? ['A' => 15, 'B' => 22, 'C' => 32, 'D' => 56, 'E' => 22]
                    : ['A' => 15, 'B' => 34, 'C' => 58, 'D' => 22];

                foreach ($widths as $col => $width) {
                    $sheet->getColumnDimension($col)->setWidth($width);
                }

                // 2. Judul (Row 1) & Subtitle (Row 2)
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 13,
                        'name'  => 'Segoe UI',
                        'color' => ['argb' => 'FF0F172A'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->mergeCells("A2:{$labelEndCol}2");
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getStyle("A2:{$labelEndCol}2")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 10,
                        'name'  => 'Segoe UI',
                        'color' => ['argb' => 'FF475569'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $sheet->getStyle("{$lastCol}2")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 10,
                        'name'  => 'Segoe UI',
                        'color' => ['argb' => 'FF1D4ED8'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_RIGHT,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(3)->setRowHeight(8);

                // 3. Header Tabel (Row 4)
                $sheet->getRowDimension(4)->setRowHeight(28);
                $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 10.5,
                        'name'  => 'Segoe UI',
                        'color' => ['argb' => 'FFFFFFFF'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF1E293B'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF1E293B'],
                        ],
                    ],
                ]);

                // 4. Baris Data (Row 5..lastDataRow)
                $tableRange = "A4:{$lastCol}{$totalRow}";
                $sheet->getStyle($tableRange)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FFCBD5E1'],
                        ],
                    ],
                    'font' => [
                        'name' => 'Segoe UI',
                        'size' => 10,
                    ],
                ]);

                // Restore header font color/bold after global table font rule
                $sheet->getStyle("A4:{$lastCol}4")->getFont()
                    ->setBold(true)
                    ->setSize(10.5)
                    ->getColor()->setARGB('FFFFFFFF');

                if (!$hasRows) {
                    $sheet->mergeCells("A5:{$lastCol}5");
                    $sheet->getRowDimension(5)->setRowHeight(28);
                    $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
                        'font' => [
                            'italic' => true,
                            'color'  => ['argb' => 'FF64748B'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                        ],
                    ]);
                } else {
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode(NumberFormat::FORMAT_TEXT);
                    $sheet->getStyle("A{$firstDataRow}:A{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    $sheet->getStyle("{$ptCol}{$firstDataRow}:{$itemCol}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);

                    if ($this->includeSalesColumn) {
                        $sheet->getStyle("B{$firstDataRow}:B{$lastDataRow}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                            ->setVertical(Alignment::VERTICAL_CENTER);
                    }

                    $sheet->getStyle("{$lastCol}{$firstDataRow}:{$lastCol}{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode(self::CURRENCY_FORMAT);
                    $sheet->getStyle("{$lastCol}{$firstDataRow}:{$lastCol}{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                        ->setVertical(Alignment::VERTICAL_CENTER);

                    foreach ($this->rows as $idx => $row) {
                        $excelRow = $firstDataRow + $idx;

                        // Paksa kolom teks sebagai explicit string (lapisan tambahan anti formula injection)
                        $sheet->setCellValueExplicit("A{$excelRow}", $this->sanitizeText((string) ($row['date_display'] ?? '-')), DataType::TYPE_STRING);
                        if ($this->includeSalesColumn) {
                            $sheet->setCellValueExplicit("B{$excelRow}", $this->sanitizeText((string) ($row['sales_name'] ?? '-')), DataType::TYPE_STRING);
                        }
                        $sheet->setCellValueExplicit("{$ptCol}{$excelRow}", $this->sanitizeText((string) ($row['customer_name'] ?? '-')), DataType::TYPE_STRING);
                        $sheet->setCellValueExplicit("{$itemCol}{$excelRow}", $this->sanitizeText((string) ($row['item_summary'] ?? '-')), DataType::TYPE_STRING);

                        // Tinggi baris adaptif sesuai panjang nama item
                        $itemLen = mb_strlen((string) ($row['item_summary'] ?? ''));
                        $ptLen = mb_strlen((string) ($row['customer_name'] ?? ''));
                        $estimatedLines = max(1, (int) ceil($itemLen / 52), (int) ceil($ptLen / 28));
                        $sheet->getRowDimension($excelRow)->setRowHeight(max(22, $estimatedLines * 16 + 6));

                        if ($excelRow % 2 === 0) {
                            $sheet->getStyle("A{$excelRow}:{$lastCol}{$excelRow}")->applyFromArray([
                                'fill' => [
                                    'fillType'   => Fill::FILL_SOLID,
                                    'startColor' => ['argb' => 'FFF8FAFC'],
                                ],
                            ]);
                        }
                    }

                    $sheet->setAutoFilter("A4:{$lastCol}{$lastDataRow}");
                }

                // 5. Baris TOTAL dengan Formula Excel Asli (=SUM)
                $sheet->mergeCells("A{$totalRow}:{$labelEndCol}{$totalRow}");
                $sheet->getRowDimension($totalRow)->setRowHeight(28);

                if ($hasRows) {
                    $sheet->setCellValue("{$lastCol}{$totalRow}", "=SUM({$lastCol}{$firstDataRow}:{$lastCol}{$lastDataRow})");
                } else {
                    $sheet->setCellValue("{$lastCol}{$totalRow}", 0);
                }

                $sheet->getStyle("A{$totalRow}:{$lastCol}{$totalRow}")->applyFromArray([
                    'font' => [
                        'bold'  => true,
                        'size'  => 10.5,
                        'name'  => 'Segoe UI',
                        'color' => ['argb' => 'FF065F46'],
                    ],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFECFDF5'],
                    ],
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color'       => ['argb' => 'FF059669'],
                        ],
                        'bottom' => [
                            'borderStyle' => Border::BORDER_MEDIUM,
                            'color'       => ['argb' => 'FF059669'],
                        ],
                    ],
                ]);

                $sheet->getStyle("A{$totalRow}:{$labelEndCol}{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                $sheet->getStyle("{$lastCol}{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode(self::CURRENCY_FORMAT);
                $sheet->getStyle("{$lastCol}{$totalRow}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_RIGHT)
                    ->setVertical(Alignment::VERTICAL_CENTER);

                // 6. Freeze Header & Print Setup Landscape Fit-to-Page
                $sheet->freezePane('A5');
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
            },
        ];
    }

    /**
     * Cegah Excel Formula Injection (CSV/XLSX injection) pada cell teks dari input user.
     */
    public static function sanitizeSpreadsheetText(string $value): string
    {
        if ($value === '' || $value === '-') {
            return $value;
        }

        $trimmed = ltrim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $value;
        }

        return $value;
    }

    private function sanitizeText(string $value): string
    {
        return self::sanitizeSpreadsheetText($value);
    }
}
