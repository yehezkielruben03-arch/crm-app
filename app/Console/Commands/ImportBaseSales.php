<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportBaseSales extends Command
{
    protected $signature = 'crm:import-base-sales {file? : Path file Excel Salinan dari Base Sales.xlsx}';
    protected $description = 'Import dan sinkronkan database customer serta kepemilikan sales dari Salinan dari Base Sales.xlsx';

    public function handle()
    {
        $filePath = $this->argument('file') ?: database_path('data/Salinan dari Base Sales.xlsx');

        if (!File::exists($filePath)) {
            if (File::exists(base_path('file sampling/Salinan dari Base Sales.xlsx'))) {
                $filePath = base_path('file sampling/Salinan dari Base Sales.xlsx');
            } else {
                $this->error('File Excel tidak ditemukan di: ' . $filePath);
                return 1;
            }
        }

        $this->info('========================================================');
        $this->info('MEMULAI SINKRONISASI DATABASE CUSTOMER KE CRM');
        $this->info('Sumber File: ' . $filePath);
        $this->info('========================================================');

        // 1. Identifikasi Akun Sales
        $salesAde = User::where('email', 'ade-zulvida@pedia-group.jp')
            ->orWhere('username', 'ade_zulvida')
            ->first();

        $salesYudi = User::where('email', 'sales-pti1@pedia-group.jp')
            ->orWhere('username', 'Yudi')
            ->orWhere('username', 'yudi')
            ->first();

        $salesBaru = User::where('email', 'sales-pti2@pedia-group.jp')
            ->orWhere('username', 'sales_2')
            ->orWhere('username', 'sales_baru')
            ->first();

        $adminUser = User::whereIn('role', ['Super Admin', 'Admin', 'Leader'])->first() ?: $salesAde;

        $this->info('Akun Penugasan:');
        $this->info(' - Sales Ade Zulvida : ' . ($salesAde ? $salesAde->name . ' (ID: ' . $salesAde->id . ')' : 'Belum Ada'));
        $this->info(' - Sales Yudi        : ' . ($salesYudi ? $salesYudi->name . ' (ID: ' . $salesYudi->id . ')' : 'Belum Ada'));
        $this->info(' - Sales Baru        : ' . ($salesBaru ? $salesBaru->name . ' (ID: ' . $salesBaru->id . ')' : 'Belum Ada'));

        // 2. Baca File Excel
        $this->info("\nMembaca spreadsheet...");
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $sheet = $spreadsheet->getSheetByName('Salinan dari Base Sales') ?: $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $this->info('Sheet aktif: ' . $sheet->getTitle() . ' (Total baris: ' . $highestRow . ')');

        $codePrefix = 'PTI' . date('Ymd');
        $lastCust = Customer::where('company_code', 'LIKE', $codePrefix . '%')
            ->orderByDesc('company_code')
            ->first();
        $counter = $lastCust ? (int) substr($lastCust->company_code, -7) : 0;

        $createdCount = 0;
        $updatedCount = 0;
        $skippedEmpty = 0;
        $skippedDuplicates = 0;
        $countAde = 0;
        $countYudi = 0;
        $countSalesBaru = 0;
        $countUnassigned = 0;

        // Mulai looping dari baris ke-5 (baris 1-3 petunjuk, baris 4 header)
        for ($row = 5; $row <= $highestRow; $row++) {
            $compNameRaw = $sheet->getCell("B{$row}")->getValue();
            if (!$compNameRaw || empty(trim((string)$compNameRaw))) {
                $skippedEmpty++;
                continue;
            }

            $companyName = trim((string)$compNameRaw);
            if (str_starts_with($companyName, '-') || stripos($companyName, 'contoh') === 0) {
                $skippedEmpty++;
                continue;
            }

            // Cek Kolom M: jika ada catatan duplikat, dihiraukan / dilewati
            $colM = trim((string)($sheet->getCell("M{$row}")->getValue() ?? ''));
            if (!empty($colM) && stripos($colM, 'udh ada di atas') !== false) {
                $skippedDuplicates++;
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
                    $lastReqStr = trim((string)$dateLastVal);
                }
            }

            // Status Aktivitas (Aktif -> Active, Vakum -> Inactive)
            $statusRaw = strtolower(trim((string)($sheet->getCell("F{$row}")->getValue() ?? '')));
            if ($statusRaw === 'aktif' || $statusRaw === 'active') {
                $status = 'Active';
            } else {
                $status = 'Inactive';
            }

            $catatan = trim((string)($sheet->getCell("G{$row}")->getValue() ?? ''));
            $pic     = trim((string)($sheet->getCell("H{$row}")->getValue() ?? ''));
            $telp    = trim((string)($sheet->getCell("I{$row}")->getValue() ?? ''));

            // Sales Assignment (Kolom J Ade, Kolom K Yudi, Kolom L Sales 2)
            $colAde    = trim((string)($sheet->getCell("J{$row}")->getValue() ?? ''));
            $colYudi   = trim((string)($sheet->getCell("K{$row}")->getValue() ?? ''));
            $colSales2 = trim((string)($sheet->getCell("L{$row}")->getValue() ?? ''));

            $targetSalesId = null;
            if (!empty($colAde)) {
                $targetSalesId = $salesAde ? $salesAde->id : null;
                $countAde++;
            } elseif (!empty($colYudi)) {
                $targetSalesId = $salesYudi ? $salesYudi->id : null;
                $countYudi++;
            } elseif (!empty($colSales2)) {
                $targetSalesId = $salesBaru ? $salesBaru->id : null;
                $countSalesBaru++;
            } else {
                $targetSalesId = $adminUser ? $adminUser->id : null;
                $countUnassigned++;
            }

            // Susun Catatan
            $combinedNotes = [];
            if (!empty($catatan) && $catatan !== '-') {
                $combinedNotes[] = $catatan;
            }
            if (!empty($lastReqStr)) {
                $combinedNotes[] = 'Last Request: ' . $lastReqStr;
            }
            $finalNotes = count($combinedNotes) > 0 ? implode(' | ', $combinedNotes) : null;

            // Susun Alamat
            $address = null;
            if (!empty($kawasan) && !empty($kota)) {
                $address = $kawasan . ', ' . $kota;
            } elseif (!empty($kawasan)) {
                $address = $kawasan;
            } elseif (!empty($kota)) {
                $address = $kota;
            }

            // Cari apakah customer sudah ada di database
            $customer = Customer::where('company_name', $companyName)->first();

            if ($customer) {
                $customer->update([
                    'sales_id'      => $targetSalesId,
                    'status'        => $status,
                    'area_category' => empty($kawasan) ? $customer->area_category : substr($kawasan, 0, 50),
                    'city'          => empty($kota) ? $customer->city : substr($kota, 0, 100),
                    'address'       => $address ?: $customer->address,
                    'phone'         => empty($telp) ? $customer->phone : substr($telp, 0, 30),
                    'cp_name'       => empty($pic) ? $customer->cp_name : substr($pic, 0, 100),
                    'cp_phone'      => empty($telp) ? $customer->cp_phone : substr($telp, 0, 30),
                    'notes'         => $finalNotes ?: $customer->notes,
                ]);
                $updatedCount++;
            } else {
                $counter++;
                $companyCode = $codePrefix . str_pad($counter, 7, '0', STR_PAD_LEFT);

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
                    'sales_id'      => $targetSalesId,
                    'created_by'    => $adminUser ? $adminUser->id : null,
                    'approved_by'   => $adminUser ? $adminUser->id : null,
                    'approved_at'   => now(),
                    'notes'         => $finalNotes,
                    'cp_name'       => empty($pic) ? null : substr($pic, 0, 100),
                    'cp_phone'      => empty($telp) ? null : substr($telp, 0, 30),
                ]);
                $createdCount++;
            }
        }

        $this->info('');
        $this->info('========================================================');
        $this->info('SINKRONISASI DATABASE CUSTOMER BERHASIL SELESAI');
        $this->info('========================================================');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Customer Baru Dibuat', $createdCount],
                ['Customer Eksisting Diperbarui', $updatedCount],
                ['Duplikat Dilewati (Kolom M)', $skippedDuplicates],
                ['Baris Kosong Dilewati', $skippedEmpty],
                ['Total Portofolio Ade Zulvida', $countAde],
                ['Total Portofolio Yudi', $countYudi],
                ['Total Portofolio Sales Baru', $countSalesBaru],
                ['Total Portofolio Tanpa Centang', $countUnassigned],
            ]
        );
        $this->info('========================================================');

        return 0;
    }
}
