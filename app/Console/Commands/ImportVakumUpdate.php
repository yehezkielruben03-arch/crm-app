<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportVakumUpdate extends Command
{
    protected $signature = 'crm:import-vakum-update {--prune : Hapus customer eksisting yang tidak ada di file Excel baru} {--all-active : Set status semua customer menjadi Active}';
    protected $description = 'Import dan sinkronisasi database customer dari file 2026-10_Update Database pelanggan Vakum.xlsx (Sheet update + Sheet Salinan dari Base Sales)';

    public function handle()
    {
        $filePath = database_path('data/2026-10_Update Database pelanggan Vakum.xlsx');

        if (!file_exists($filePath)) {
            $filePath = base_path('file sampling/2026-10_Update Database pelanggan Vakum.xlsx');
        }

        if (!file_exists($filePath)) {
            $this->error("❌ File Excel tidak ditemukan di: {$filePath}");
            return 1;
        }

        $this->info('========================================================');
        $this->info('🚀 SINKRONISASI DATABASE CUSTOMER DARI SHEET UPDATE');
        $this->info('📂 File: ' . basename($filePath));
        $this->info('========================================================');

        // 1. Identifikasi Sales User
        $userAde = User::where('id', 4)->orWhere('name', 'like', '%Ade%')->first();
        $userYudi = User::where('id', 7)->orWhere('name', 'like', '%Yudi%')->first();
        $userSalesBaru = User::where('id', 6)->orWhere('name', 'like', '%Sani%')->orWhere('name', 'like', '%Sales Baru%')->first();

        $adeId = $userAde ? $userAde->id : 4;
        $yudiId = $userYudi ? $userYudi->id : 7;
        $salesBaruId = $userSalesBaru ? $userSalesBaru->id : 6;

        $this->info("Akun Sales Terpetakan:");
        $this->info(" - Ade Zulvida       : ID {$adeId}");
        $this->info(" - Yudi Siswanto     : ID {$yudiId}");
        $this->info(" - Sani / Sales Baru : ID {$salesBaruId}");

        // 2. Baca Spreadsheet Excel
        $this->info("\nMembaca file Excel...");
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        $sheetUpdate = $spreadsheet->getSheetByName('update');
        $sheetBase = $spreadsheet->getSheetByName('Salinan dari Base Sales');

        if (!$sheetUpdate) {
            $this->error("❌ Sheet 'update' tidak ditemukan di file Excel!");
            return 1;
        }

        // 3. Bangun kamus data pelengkap dari Sheet 1 (Salinan dari Base Sales)
        $enrichment = [];
        if ($sheetBase) {
            $baseHighestRow = $sheetBase->getHighestRow();
            for ($r = 5; $r <= $baseHighestRow; $r++) {
                $ptVal = $sheetBase->getCell("B{$r}")->getValue();
                if (!$ptVal || empty(trim((string)$ptVal))) {
                    continue;
                }
                $ptName = trim((string)$ptVal);
                $key = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ptName));

                if (!isset($enrichment[$key])) {
                    $dateVal = $sheetBase->getCell("E{$r}")->getValue();
                    $dateStr = null;
                    if ($dateVal) {
                        if (is_numeric($dateVal)) {
                            $dateStr = ExcelDate::excelToDateTimeObject($dateVal)->format('d/m/Y');
                        } else {
                            $dateStr = trim((string)$dateVal);
                        }
                    }

                    $enrichment[$key] = [
                        'raw_name'  => $ptName,
                        'kawasan'   => trim((string)($sheetBase->getCell("C{$r}")->getValue() ?? '')),
                        'kota'      => trim((string)($sheetBase->getCell("D{$r}")->getValue() ?? '')),
                        'date_last' => $dateStr,
                        'status_akt'=> trim((string)($sheetBase->getCell("F{$r}")->getValue() ?? '')),
                        'catatan'   => trim((string)($sheetBase->getCell("G{$r}")->getValue() ?? '')),
                        'pic'       => trim((string)($sheetBase->getCell("H{$r}")->getValue() ?? '')),
                        'telp'      => trim((string)($sheetBase->getCell("I{$r}")->getValue() ?? '')),
                    ];
                }
            }
            $this->info("✓ Berhasil memuat data kawasan, kota, dan riwayat request untuk " . count($enrichment) . " PT dari Sheet 1.");
        }

        // 4. Proses Sheet 'update' (Baris 5 ke bawah)
        $updateHighestRow = min($sheetUpdate->getHighestRow(), 945);

        // Siapkan prefix kode perusahaan
        $codePrefix = 'PTI' . date('Ymd');
        $lastCust = Customer::where('company_code', 'LIKE', $codePrefix . '%')
            ->orderByDesc('company_code')
            ->first();
        $counter = $lastCust ? (int) substr($lastCust->company_code, -7) : 0;

        $createdCount = 0;
        $updatedCount = 0;
        $skippedDuplicates = 0;
        $countAde = 0;
        $countYudi = 0;
        $countSalesBaru = 0;
        $validCompanies = [];
        $processedKeys = [];

        DB::beginTransaction();

        try {
            for ($row = 5; $row <= $updateHighestRow; $row++) {
                $compNameRaw = $sheetUpdate->getCell("B{$row}")->getValue();
                if (!$compNameRaw || empty(trim((string)$compNameRaw))) {
                    continue;
                }

                $companyName = trim((string)$compNameRaw);
                if (
                    str_starts_with($companyName, '-') ||
                    stripos($companyName, 'contoh') === 0 ||
                    stripos($companyName, 'kolom') === 0 ||
                    stripos($companyName, 'tidak perlu') !== false ||
                    stripos($companyName, 'cara cek') !== false
                ) {
                    continue;
                }

                $cleanKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $companyName));

                // Deteksi alokasi sales dari kolom C (Ade), D (Yudi), E (Sani)
                $colAde  = trim((string)($sheetUpdate->getCell("C{$row}")->getValue() ?? ''));
                $colYudi = trim((string)($sheetUpdate->getCell("D{$row}")->getValue() ?? ''));
                $colSani = trim((string)($sheetUpdate->getCell("E{$row}")->getValue() ?? ''));

                $salesId = null;
                if (!empty($colAde)) {
                    $salesId = $adeId;
                } elseif (!empty($colYudi)) {
                    $salesId = $yudiId;
                } elseif (!empty($colSani)) {
                    $salesId = $salesBaruId;
                }

                // Cek apakah sudah diproses sebelumnya dalam iterasi ini (cegah duplikat nama)
                if (in_array($cleanKey, $processedKeys, true)) {
                    $skippedDuplicates++;
                    continue;
                }
                $processedKeys[] = $cleanKey;
                $validCompanies[] = $companyName;

                // Ambil data pengaya dari sheet 1
                $enrich = $enrichment[$cleanKey] ?? [];
                $kawasan   = $enrich['kawasan'] ?? null;
                $kota      = $enrich['kota'] ?? null;
                $dateLast  = $enrich['date_last'] ?? null;
                $statusAkt = $enrich['status_akt'] ?? null;
                $catatan   = $enrich['catatan'] ?? null;
                $pic       = $enrich['pic'] ?? null;
                $telp      = $enrich['telp'] ?? null;

                // Susun catatan rapi
                $notesParts = [];
                if (!empty($statusAkt)) {
                    $notesParts[] = "[Status Aktivitas: {$statusAkt}]";
                }
                if (!empty($dateLast)) {
                    $notesParts[] = "[Last Request: {$dateLast}]";
                }
                if (!empty($catatan) && $catatan !== '-') {
                    $notesParts[] = $catatan;
                }
                $fullNotes = !empty($notesParts) ? implode(' ', $notesParts) : null;

                // Status CRM: Active agar langsung bisa digunakan sales untuk RFQ
                $status = 'Active';

                // Hitung statistik sales
                if ($salesId === $adeId) {
                    $countAde++;
                } elseif ($salesId === $yudiId) {
                    $countYudi++;
                } elseif ($salesId === $salesBaruId) {
                    $countSalesBaru++;
                }

                // Cari customer eksisting (pencarian fleksibel)
                $cleanSearchName = preg_replace('/^(PT\.?|CV\.?)\s+|\s*,?\s*(PT\.?|CV\.?)$/i', '', $companyName);
                $customer = Customer::where('company_name', $companyName)
                    ->orWhere('company_name', 'like', "%" . trim($cleanSearchName) . "%")
                    ->first();

                $payload = [
                    'company_name'  => $companyName,
                    'customer_type' => 'PT',
                    'industry'      => !empty($kawasan) ? $kawasan : ($customer->industry ?? null),
                    'area_category' => !empty($kawasan) ? $kawasan : ($customer->area_category ?? null),
                    'city'          => !empty($kota) ? $kota : ($customer->city ?? null),
                    'phone'         => !empty($telp) ? $telp : ($customer->phone ?? null),
                    'status'        => $status,
                    'sales_id'      => $salesId,
                    'notes'         => $fullNotes ?: ($customer->notes ?? null),
                    'cp_name'       => !empty($pic) ? $pic : ($customer->cp_name ?? null),
                ];

                if ($customer) {
                    $customer->update($payload);
                    $updatedCount++;
                } else {
                    $counter++;
                    $payload['company_code'] = $codePrefix . str_pad((string)$counter, 7, '0', STR_PAD_LEFT);
                    $payload['created_by']   = 1;
                    $payload['approved_by']  = 1;
                    $payload['approved_at']  = now();
                    Customer::create($payload);
                    $createdCount++;
                }
            }

            // 5. Opsi Pruning (Hapus customer asing / dummy testing lama)
            $prunedCount = 0;
            if ($this->option('prune')) {
                $allCurrent = Customer::all();
                foreach ($allCurrent as $cust) {
                    $cKey = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $cust->company_name));
                    if (!in_array($cKey, $processedKeys, true)) {
                        $cust->forceDelete();
                        $prunedCount++;
                    }
                }
            }

            DB::commit();

            $totalInDb = Customer::count();

            $this->info("\n========================================================");
            $this->info("✅ SINKRONISASI DATABASE BERHASIL!");
            $this->info("========================================================");
            $this->table(
                ['Metrik', 'Jumlah'],
                [
                    ['Customer Baru Dibuat', $createdCount],
                    ['Customer Eksisting Diperbarui', $updatedCount],
                    ['Customer Asing / Dummy Dihapus (Pruned)', $prunedCount],
                    ['Duplikat Baris Dilewati', $skippedDuplicates],
                    ['Portofolio Ade Zulvida (ID 4)', $countAde],
                    ['Portofolio Yudi Siswanto (ID 7)', $countYudi],
                    ['Portofolio Sani / Sales Baru (ID 6)', $countSalesBaru],
                    ['Total Akhir Customer di Database', $totalInDb],
                ]
            );
            $this->info("========================================================");

            return 0;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("\n❌ GAGAL! Terjadi kesalahan: " . $e->getMessage());
            return 1;
        }
    }
}
