<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\User;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsErrors;

use Illuminate\Support\Facades\Auth;

class CustomerImport implements ToModel, WithHeadingRow, WithCalculatedFormulas
{
    private bool $isSales = false;
    public int $importedCount = 0;
    private array $mapping = [];
    private int $headingRow = 1;
    private array $seenNames = [];
    private ?int $codeCounter = null;
    private ?string $codePrefix = null;

    public function __construct(array $mapping = [], int $headingRow = 1)
    {
        $this->mapping = $mapping;
        $this->headingRow = $headingRow;
        if (Auth::check()) {
            $user = Auth::user();
            $this->isSales = !$user->isAdminOrAbove();
        }

        // Cache kode perusahaan terakhir untuk performa bulk import super cepat
        $this->codePrefix = 'PTI' . now()->format('Ymd');
        $last = Customer::where('company_code', 'LIKE', $this->codePrefix . '%')
            ->orderByDesc('company_code')
            ->first();
        $this->codeCounter = $last ? (int) substr($last->company_code, -7) : 0;
    }

    public function headingRow(): int
    {
        return $this->headingRow;
    }

    private function generateSequentialCompanyCode(): string
    {
        $this->codeCounter++;
        return $this->codePrefix . str_pad($this->codeCounter, 7, '0', STR_PAD_LEFT);
    }

    public function model(array $row)
    {
        // 1. Flexible Aliases untuk Company Name
        $companyName = $row['nama_perusahaan'] ?? $row['perusahaan'] ?? $row['nama_pt'] ?? $row['nama_client'] ?? $row['company_name'] ?? $row['nama'] ?? $row['client'] ?? null;
        $companyName = trim((string)$companyName);

        // Skip baris kosong, baris instruksi (dimulai dengan -, berisi kata 'contoh', 'jangan typo', dll)
        if (empty($companyName) || 
            str_starts_with($companyName, '-') || 
            stripos($companyName, 'contoh') === 0 || 
            stripos($companyName, 'jangan typo') !== false ||
            stripos($companyName, 'kolom') === 0 ||
            stripos($companyName, 'cara cek') !== false
        ) {
            return null;
        }

        // Cek Duplicate internal dalam file Excel (In-Memory deduplication)
        $cleanKey = strtolower(preg_replace('/[^a-z0-9]/i', '', $companyName));
        if (isset($this->seenNames[$cleanKey])) {
            return null;
        }
        $this->seenNames[$cleanKey] = true;

        // Cek Duplicate Company Name di Database
        if (Customer::where('company_name', $companyName)->exists()) {
            return null;
        }

        // 2. Flexible Aliases untuk Field Lain
        $email = trim((string)($row['email'] ?? $row['alamat_email'] ?? $row['e_mail'] ?? ''));
        if (!empty($email) && Customer::where('email', $email)->exists()) {
            return null; // Skip if email exists
        }

        $region = $row['kawasan'] ?? $row['region'] ?? $row['area'] ?? $row['wilayah'] ?? $row['lokasi'] ?? $row['kawasan_industri'] ?? $row['kota'] ?? $row['provinsi'] ?? null;
        $industry = $row['industri'] ?? $row['industry'] ?? $row['bidang'] ?? $row['sektor'] ?? $row['bidang_usaha'] ?? $row['jenis_industri'] ?? $row['kategori_industri'] ?? null;
        
        $finalStatus = 'Active';
        $rawStatus = strtolower(trim((string)($row['status_aktivitas'] ?? $row['status'] ?? '')));
        if (in_array($rawStatus, ['vakum', 'inactive', 'tidak aktif'])) {
            $finalStatus = 'Inactive';
        } elseif (in_array($rawStatus, ['prospek', 'prospect', 'lead'])) {
            $finalStatus = 'Prospect';
        }
        if ($this->isSales) {
            $finalStatus = 'Prospect';
        }

        // 3. Tentukan Sales ID (Logika Ceklis mapping)
        $salesId = $this->isSales ? Auth::id() : null;
        if (!$this->isSales && $salesId === null) {
            foreach ($row as $key => $value) {
                $cleanValue = trim((string)$value);
                if ($cleanValue === '✓' || strtolower($cleanValue) === 'v' || $cleanValue === '1' || strtolower($cleanValue) === 'ya') {
                    foreach ($this->mapping as $headerAsli => $userId) {
                        $snakeHeader = \Illuminate\Support\Str::slug($headerAsli, '_');
                        if ($snakeHeader === $key && !empty($userId)) {
                            $salesId = $userId;
                            break 2;
                        }
                    }
                }
            }
        }

        // 4. Generate Kode Perusahaan bila langsung berstatus Active
        $companyCode = null;
        if ($finalStatus === 'Active') {
            $companyCode = $this->generateSequentialCompanyCode();
        }

        $rawPhone = $row['no_telp'] ?? $row['telp'] ?? $row['telepon'] ?? $row['phone'] ?? null;
        $cleanPhone = $rawPhone ? substr(trim((string)$rawPhone), 0, 100) : null;

        $rawCpPhone = $row['cp_telepon'] ?? $row['telp_pic'] ?? $row['hp_pic'] ?? $row['no_telp'] ?? null;
        $cleanCpPhone = $rawCpPhone ? substr(trim((string)$rawCpPhone), 0, 100) : null;

        $this->importedCount++;

        return new Customer([
            'company_name'      => $companyName,
            'brand_name'        => $row['brand'] ?? $row['merek'] ?? $row['brand_name'] ?? null,
            'customer_type'     => $row['jenis_pelanggan'] ?? $row['jenis_perusahaan'] ?? $row['tipe'] ?? null,
            'industry'          => empty($industry) ? 'General' : $industry,
            'company_scale'     => $row['skala_perusahaan'] ?? $row['skala'] ?? $row['company_scale'] ?? null,
            'area_category'     => $row['kategori_area'] ?? $row['kategori'] ?? null,
            'region'            => empty($region) ? 'Unknown' : $region,
            'nib'               => $row['nib'] ?? $row['no_nib'] ?? null,
            'division'          => $row['divisi'] ?? $row['departemen'] ?? $row['division'] ?? null,
            'address'           => $row['alamat'] ?? $row['address'] ?? $row['alamat_lengkap'] ?? null,
            'city'              => $row['kota'] ?? $row['city'] ?? $row['kabupaten'] ?? null,
            'province'          => $row['provinsi'] ?? $row['province'] ?? null,
            'postal_code'       => $row['kode_pos'] ?? $row['kodepos'] ?? $row['postal_code'] ?? null,
            'country'           => $row['negara'] ?? $row['country'] ?? null,
            'phone'             => $cleanPhone,
            'office_phone'      => $row['telepon_kantor'] ?? $row['telp_kantor'] ?? $row['office_phone'] ?? null,
            'whatsapp'          => $row['whatsapp'] ?? $row['wa'] ?? $row['no_wa'] ?? null,
            'preferred_contact' => $row['preferred_contact'] ?? $row['kontak_pilihan'] ?? null,
            'email'             => empty($email) ? null : $email,
            'website'           => $row['website'] ?? $row['web'] ?? $row['situs'] ?? null,
            'npwp'              => $row['npwp'] ?? $row['no_npwp'] ?? null,
            'status'            => $finalStatus,
            'company_code'      => $companyCode,
            'created_by'        => Auth::id(),
            'sales_id'          => $salesId,
            'notes'             => $row['catatan'] ?? $row['notes'] ?? $row['keterangan'] ?? null,
            'cp_name'           => $row['pic'] ?? $row['cp_nama'] ?? $row['nama_pic'] ?? $row['contact_person'] ?? null,
            'cp_position'       => $row['cp_jabatan'] ?? $row['jabatan_pic'] ?? $row['jabatan'] ?? null,
            'cp_email'          => $row['cp_email'] ?? $row['email_pic'] ?? null,
            'cp_phone'          => $cleanCpPhone,
        ]);
    }

    public function getFailedRows(): array
    {
        return [];
    }
}
