<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportVakumExcel extends Command
{
    protected $signature = 'crm:import-excel {file? : Path file Excel (default: file di root crm)}';
    protected $description = 'Import database customer dari file Excel Base Sales / Pelanggan Vakum ke dalam CRM';

    public function handle()
    {
        $filePath = $this->argument('file') ?: base_path('2026-07_Update Database pelanggan Vakum.xlsx');

        if (!file_exists($filePath)) {
            $this->error("❌ File tidak ditemukan di: {$filePath}");
            return 1;
        }

        $this->info("========================================================");
        $this->info("🚀 MEMULAI IMPORT DATABASE CUSTOMER EXCEL KE CRM");
        $this->info("📂 File: " . basename($filePath));
        $this->info("========================================================");

        // 1. Pastikan User Sales Siap
        $this->info("1. Memeriksa Akun Sales...");
        $salesAde = User::where('name', 'like', '%Ade%')
            ->orWhere('email', 'like', '%ade%')
            ->orWhere('username', 'like', '%ade%')
            ->first();

        if (!$salesAde) {
            $salesAde = User::create([
                'name'     => 'Ade Zulvida',
                'username' => 'ade_zulvida',
                'email'    => 'ade-zulvida@pedia-group.jp',
                'password' => Hash::make('password123'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info("   + Akun Ade Zulvida dibuat.");
        } else {
            $this->info("   ✓ Sales Ade: {$salesAde->name} (ID: {$salesAde->id})");
        }

        $salesYudi = User::where('name', 'like', '%Yudi%')
            ->orWhere('email', 'like', '%yudi%')
            ->orWhere('username', 'like', '%yudi%')
            ->first();

        if (!$salesYudi) {
            $salesYudi = User::create([
                'name'     => 'Yudi (Sales)',
                'username' => 'yudi',
                'email'    => 'yudi@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);
            $this->info("   + Akun Sales Yudi dibuat (ID: {$salesYudi->id}).");
        } else {
            $this->info("   ✓ Sales Yudi: {$salesYudi->name} (ID: {$salesYudi->id})");
        }

        $adminUser = User::whereIn('role', ['Super Admin', 'Admin'])->first() ?: $salesAde;

        // 2. Baca File Excel
        $this->info("\n2. Membaca spreadsheet Excel...");
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $sheet = $spreadsheet->getSheetByName('Base Sales') ?: $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $this->info("   ✓ Ditemukan Sheet: '{$sheet->getTitle()}' dengan total baris: {$highestRow}");

        // Siapkan prefix kode perusahaan
        $codePrefix = 'PTI' . date('Ymd');
        $lastCust = Customer::where('company_code', 'LIKE', $codePrefix . '%')
            ->orderByDesc('company_code')
            ->first();
        $counter = $lastCust ? (int) substr($lastCust->company_code, -7) : 0;

        $imported = 0;
        $skipped = 0;
        $assignedAde = 0;
        $assignedYudi = 0;
        $assignedOther = 0;
        $statusActiveCount = 0;
        $statusInactiveCount = 0;

        $bar = $this->output->createProgressBar($highestRow - 3);
        $bar->start();

        // Mulai looping dari baris ke-4 (baris 1-3 adalah judul & header)
        for ($row = 4; $row <= $highestRow; $row++) {
            $compNameRaw = $sheet->getCell("B{$row}")->getValue();
            if (!$compNameRaw || empty(trim((string)$compNameRaw))) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $companyName = trim((string)$compNameRaw);
            if (str_starts_with($companyName, '-') || 
                stripos($companyName, 'contoh') === 0 || 
                stripos($companyName, 'kolom') === 0 || 
                stripos($companyName, 'tidak perlu') !== false ||
                stripos($companyName, 'jangan typo') !== false ||
                stripos($companyName, 'cara cek') !== false
            ) {
                $skipped++;
                $bar->advance();
                continue;
            }

            // Cek duplikasi di database
            if (Customer::where('company_name', $companyName)->exists()) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $kawasan = trim((string)($sheet->getCell("C{$row}")->getValue() ?? ''));
            $kota    = trim((string)($sheet->getCell("D{$row}")->getValue() ?? ''));

            // Tanggal Last Request
            $dateLastVal = $sheet->getCell("E{$row}")->getValue();
            $lastReqStr = null;
            if ($dateLastVal) {
                if (is_numeric($dateLastVal)) {
                    $lastReqStr = ExcelDate::excelToDateTimeObject($dateLastVal)->format('d/m/Y');
                } else {
                    $lastReqStr = (string)$dateLastVal;
                }
            }

            // Status Aktivitas (Aktif -> Active, Vakum -> Inactive)
            $statusRaw = strtolower(trim((string)($sheet->getCell("F{$row}")->getValue() ?? '')));
            if (in_array($statusRaw, ['vakum', 'inactive', 'tidak aktif'])) {
                $status = 'Inactive';
                $statusInactiveCount++;
            } else {
                $status = 'Active';
                $statusActiveCount++;
            }

            $catatan = trim((string)($sheet->getCell("G{$row}")->getValue() ?? ''));
            $pic     = trim((string)($sheet->getCell("H{$row}")->getValue() ?? ''));
            $telp    = trim((string)($sheet->getCell("I{$row}")->getValue() ?? ''));

            // Sales Assignment
            $colAde      = trim((string)($sheet->getCell("J{$row}")->getValue() ?? ''));
            $colYudi     = trim((string)($sheet->getCell("K{$row}")->getValue() ?? ''));
            $colExisting = trim((string)($sheet->getCell("L{$row}")->getValue() ?? ''));
            $col14       = trim((string)($sheet->getCell("M{$row}")->getValue() ?? ''));

            $salesId = null;
            if (!empty($colAde) || stripos($col14, 'Ade') !== false) {
                $salesId = $salesAde->id;
                $assignedAde++;
            } elseif (!empty($colYudi) || stripos($col14, 'Yudi') !== false) {
                $salesId = $salesYudi->id;
                $assignedYudi++;
            } elseif (!empty($colExisting)) {
                $salesId = $salesAde->id; // Assign existing ke Ade atau default
                $assignedOther++;
            } else {
                $salesId = $salesAde->id;
                $assignedOther++;
            }

            // Generate Kode Perusahaan Unik
            $counter++;
            $companyCode = $codePrefix . str_pad($counter, 7, '0', STR_PAD_LEFT);

            // Bangun catatan gabungan jika ada date last request
            $combinedNotes = [];
            if (!empty($catatan) && $catatan !== '-') {
                $combinedNotes[] = $catatan;
            }
            if (!empty($lastReqStr)) {
                $combinedNotes[] = "Last Request: {$lastReqStr}";
            }
            $finalNotes = count($combinedNotes) > 0 ? implode(" | ", $combinedNotes) : null;

            // Bangun alamat dari Kawasan & Kota
            $address = null;
            if (!empty($kawasan) && !empty($kota)) {
                $address = "{$kawasan}, {$kota}";
            } elseif (!empty($kawasan)) {
                $address = $kawasan;
            } elseif (!empty($kota)) {
                $address = $kota;
            }

            Customer::create([
                'company_name'  => $companyName,
                'company_code'  => $companyCode,
                'customer_type' => 'PT',
                'industry'      => 'General',
                'area_category' => empty($kawasan) ? null : substr($kawasan, 0, 50),
                'city'          => empty($kota) ? null : substr($kota, 0, 100),
                'address'       => $address,
                'phone'         => empty($telp) ? null : substr($telp, 0, 30),
                'status'        => $status,
                'sales_id'      => $salesId,
                'created_by'    => $adminUser->id,
                'approved_by'   => $adminUser->id,
                'approved_at'   => now(),
                'notes'         => $finalNotes,
                'cp_name'       => empty($pic) ? null : substr($pic, 0, 100),
                'cp_phone'      => empty($telp) ? null : substr($telp, 0, 30),
            ]);

            $imported++;
            $bar->advance();
        }

        $bar->finish();
        $this->info("\n");

        $this->info("========================================================");
        $this->info("✅ IMPORT CUSTOMER EXCEL BERHASIL SELESAI!");
        $this->info("========================================================");
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Baris Diproses', $highestRow - 3],
                ['Customer Berhasil Diimport', $imported],
                ['Baris Kosong / Duplikat Dilewati', $skipped],
                ['Status Aktif (Active)', $statusActiveCount],
                ['Status Vakum (Inactive)', $statusInactiveCount],
                ['Ditugaskan ke Sales Ade Zulvida', $assignedAde],
                ['Ditugaskan ke Sales Yudi', $assignedYudi],
                ['Ditugaskan ke Sales Lain / Existing', $assignedOther],
            ]
        );
        $this->info("========================================================");

        return 0;
    }
}
