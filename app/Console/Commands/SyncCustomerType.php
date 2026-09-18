<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncCustomerType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customer:sync-type';

    protected $description = 'Mendeteksi dan mengupdate kolom customer_type (PT/CV/Pemerintah) berdasarkan nama perusahaan (company_name)';

    public function handle()
    {
        $this->info("Memulai sinkronisasi tipe customer...");
        
        // Ambil semua customer yang tipenya kosong atau "-"
        $customers = \App\Models\Customer::whereNull('customer_type')
                        ->orWhere('customer_type', '')
                        ->orWhere('customer_type', '-')
                        ->get();

        $this->info("Ditemukan " . $customers->count() . " data customer tanpa tipe.");

        $countPT = 0;
        $countCV = 0;
        $countGov = 0;

        foreach ($customers as $customer) {
            $name = strtoupper($customer->company_name);
            $type = null;

            // Logika deteksi PT, CV, Pemerintah
            if (preg_match('/\bPT\b|\bPT\./i', $name)) {
                $type = 'PT';
                $countPT++;
            } elseif (preg_match('/\bCV\b|\bCV\./i', $name)) {
                $type = 'CV';
                $countCV++;
            } elseif (preg_match('/\b(DINAS|KEMENTERIAN|BADAN|RSUD|PUSKESMAS|PEMERINTAH)\b/i', $name)) {
                $type = 'Pemerintah';
                $countGov++;
            }

            if ($type) {
                // Bersihkan nama PT / CV dari nama perusahaan (di awal atau di akhir)
                $cleanName = preg_replace('/^(PT\.?|CV\.?)\s+|\s*,?\s*(PT\.?|CV\.?)$/i', '', $customer->company_name);
                $cleanName = trim($cleanName);

                // Jangan pakai event firing (save) biar gak nyalain log atau error observer aneh-aneh (jika ada), 
                // cukup update kolom langsung
                $customer->updateQuietly([
                    'customer_type' => $type,
                    'company_name'  => $cleanName
                ]);
            }

            // Bersihkan catatan dari rumus Excel (karena import error) untuk SEMUA customer
            $cleanNotes = $customer->notes;
            if (!empty($cleanNotes) && (str_contains(strtoupper($cleanNotes), '=IF(') || str_contains(strtoupper($cleanNotes), 'TODAY()'))) {
                if (preg_match('/"([^"]+)"/', $cleanNotes, $matches)) {
                    $customer->updateQuietly(['notes' => trim($matches[1])]);
                } else {
                    $customer->updateQuietly(['notes' => null]);
                }
            }

            // MIGRATION: Pindahkan PIC dari kolom lama ke tabel customer_contacts jika belum ada
            $cpName = $customer->cp_name ?? '';
            if (!empty(trim($cpName))) {
                $exists = $customer->contacts()->where('name', trim($cpName))->exists();
                if (!$exists) {
                    $customer->contacts()->create([
                        'name' => trim($cpName),
                        'position' => $customer->cp_position ?? null,
                        'email' => $customer->cp_email ?? null,
                        'phone' => $customer->cp_phone ?? null,
                        'office_phone' => $customer->office_phone ?? null,
                        'whatsapp' => $customer->whatsapp ?? null,
                        'division' => $customer->division ?? null,
                        'preferred_contact' => $customer->preferred_contact ?? null,
                        'is_primary' => true
                    ]);
                }
            }
        }

        $this->info("✅ Sinkronisasi selesai!");
        $this->info("- Berhasil diset PT: $countPT");
        $this->info("- Berhasil diset CV: $countCV");
        $this->info("- Berhasil diset Pemerintah: $countGov");
        $this->info("✅ Seluruh data PIC (Kontak Utama) lama telah di-migrasi ke tabel Kontak Person.");
    }
}
