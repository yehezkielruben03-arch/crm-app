<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class CustomerExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private int $rowNumber = 0;

    public function collection()
    {
        $user = auth()->user();
        $query = Customer::with(['sales', 'contacts']);

        if ($user && !$user->isAdminOrAbove()) {
            $query->where('sales_id', $user->id);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function headings(): array
    {
        return [
            'No',
            'Kode Perusahaan',
            'Nama Perusahaan',
            'Tipe',
            'Bidang Usaha',
            'Industri',
            'Kawasan / Area',
            'Kota / Kabupaten',
            'Provinsi',
            'Alamat Lengkap',
            'Telepon Kantor',
            'Email Perusahaan',
            'Website',
            'NPWP',
            'NIB',
            'Status',
            'Sales Marketing',
            'Nama PIC Utama',
            'Jabatan PIC',
            'Email PIC',
            'No. HP / WA PIC',
            'Catatan Perusahaan',
        ];
    }

    public function map($customer): array
    {
        $this->rowNumber++;
        $primary = $customer->primaryContact();

        return [
            $this->rowNumber,
            $customer->company_code ?? '-',
            $customer->company_name,
            $customer->customer_type ?? '-',
            $customer->brand_name ?? '-',
            $customer->industry ?? '-',
            $customer->region ?? '-',
            $customer->city ?? '-',
            $customer->province ?? '-',
            $customer->address ?? '-',
            $customer->phone ?? '-',
            $customer->email ?? '-',
            $customer->website ?? '-',
            $customer->npwp ?? '-',
            $customer->nib ?? '-',
            $customer->status,
            $customer->sales?->name ?? 'Admin',
            $primary?->name ?? $customer->cp_name ?? '-',
            $primary?->position ?? $customer->cp_position ?? '-',
            $primary?->email ?? $customer->cp_email ?? '-',
            $primary?->phone ?? $customer->cp_phone ?? '-',
            $customer->notes ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = 'V';
        $fullRange   = 'A1:' . $highestCol . $highestRow;
        $headerRange = 'A1:' . $highestCol . '1';

        // 1. Column Widths configuration
        $widths = [
            'A' => 6,   // No
            'B' => 24,  // Kode Perusahaan
            'C' => 32,  // Nama Perusahaan
            'D' => 10,  // Tipe
            'E' => 20,  // Bidang Usaha
            'F' => 18,  // Industri
            'G' => 24,  // Kawasan / Area
            'H' => 20,  // Kota
            'I' => 18,  // Provinsi
            'J' => 38,  // Alamat Lengkap
            'K' => 18,  // Telepon
            'L' => 26,  // Email
            'M' => 26,  // Website
            'N' => 22,  // NPWP
            'O' => 18,  // NIB
            'P' => 14,  // Status
            'Q' => 22,  // Sales Marketing
            'R' => 22,  // Nama PIC
            'S' => 20,  // Jabatan PIC
            'T' => 26,  // Email PIC
            'U' => 18,  // HP PIC
            'V' => 32,  // Catatan
        ];

        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        // 2. Header Row Styling (Dark Slate Navy, Bold White Text, 32pt height)
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 10.5,
                'name' => 'Segoe UI',
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E293B'], // Slate 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => false,
            ],
        ]);

        // 3. Grid Borders & Global Vertical Alignment
        $sheet->getStyle($fullRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFCBD5E1'], // Light Slate Gray border
                ],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // 4. Data Rows Styling (Rows 2 to $highestRow)
        if ($highestRow >= 2) {
            // Text formatting to prevent Excel from trimming leading zeroes
            $textCols = ['B', 'K', 'N', 'O', 'U'];
            foreach ($textCols as $col) {
                $sheet->getStyle($col . '2:' . $col . $highestRow)
                    ->getNumberFormat()
                    ->setFormatCode(NumberFormat::FORMAT_TEXT);
            }

            // Alignments
            $centerCols = ['A', 'B', 'D', 'K', 'N', 'O', 'P', 'U'];
            foreach ($centerCols as $col) {
                $sheet->getStyle($col . '2:' . $col . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            // Left-aligned columns with wrap text
            $wrapCols = ['J', 'V'];
            foreach ($wrapCols as $col) {
                $sheet->getStyle($col . '2:' . $col . $highestRow)
                    ->getAlignment()
                    ->setWrapText(true);
            }

            // Zebra striping and Status badges
            for ($row = 2; $row <= $highestRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(24);

                // Alternating row background
                if ($row % 2 === 0) {
                    $sheet->getStyle('A' . $row . ':' . $highestCol . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFF8FAFC'], // Light subtle gray
                        ],
                    ]);
                }

                // Status Badge styling
                $statusVal = trim((string) $sheet->getCell('P' . $row)->getValue());
                if ($statusVal === 'Active') {
                    $sheet->getStyle('P' . $row)->applyFromArray([
                        'font' => ['color' => ['argb' => 'FF15803D'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCFCE7']],
                    ]);
                } elseif ($statusVal === 'Prospect') {
                    $sheet->getStyle('P' . $row)->applyFromArray([
                        'font' => ['color' => ['argb' => 'FFB45309'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEF3C7']],
                    ]);
                } elseif (in_array($statusVal, ['Inactive', 'Blacklist'])) {
                    $sheet->getStyle('P' . $row)->applyFromArray([
                        'font' => ['color' => ['argb' => 'FFB91C1C'], 'bold' => true],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFFEE2E2']],
                    ]);
                }
            }
        }

        // 5. Freeze Header and Columns A-C (No, Kode Perusahaan, Nama Perusahaan stay pinned)
        $sheet->freezePane('D2');

        // 6. Enable AutoFilter
        $sheet->setAutoFilter($headerRange);

        return [];
    }
}

