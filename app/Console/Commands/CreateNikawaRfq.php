<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Notification;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Console\Command;

class CreateNikawaRfq extends Command
{
    protected $signature = 'rfq:create-nikawa';
    protected $description = 'Buat RFQ Projek PT Nikawa Textile Industry oleh Sales hanya dengan catatan untuk disimulasikan oleh Admin';

    public function handle(): int
    {
        $this->info('Memulai pembuatan RFQ Projek Nikawa Textile Industry...');

        // Cari user sales
        $sales = User::where('email', 'ade@crm.com')->first();
        if (!$sales) {
            $sales = User::whereIn('role', ['Sales', 'Sales Marketing'])->first();
        }

        if (!$sales) {
            $this->error('User Sales tidak ditemukan!');
            return 1;
        }

        // Cari atau buat customer PT Nikawa Textile Industry
        $customer = Customer::where('company_name', 'like', '%Nikawa%')->first();
        if (!$customer) {
            $customer = Customer::create([
                'company_name' => 'PT. NIKAWA TEXTILE INDUSTRY',
                'company_code' => 'NTI-001',
                'address'      => 'Kawasan Industri Mitrakarawang, Jl. Mitra Raya I, Kec. Ciampel, Kabupaten Karawang, Jawa Barat 41363',
                'city'         => 'Karawang',
                'province'     => 'Jawa Barat',
                'phone'        => '(0267) 440650',
                'cp_name'      => 'Pak Aldo',
                'cp_position'  => 'Procurement / PIC Project',
                'cp_phone'     => '(0267) 440650',
                'status'       => 'Active',
                'sales_id'     => $sales->id,
            ]);
        } else {
            $customer->update([
                'status'       => 'Active',
                'sales_id'     => $sales->id,
                'company_code' => $customer->company_code ?: 'NTI-001',
            ]);
        }

        // Pastikan kontak tersedia
        $contact = CustomerContact::where('customer_id', $customer->id)->first();
        if (!$contact) {
            $contact = CustomerContact::create([
                'customer_id' => $customer->id,
                'name'        => 'Pak Aldo',
                'position'    => 'Procurement / PIC Project',
                'phone'       => '(0267) 440650',
                'email'       => 'aldo@nikawatex.co.id',
                'is_primary'  => true,
            ]);
        }

        $salesNotes = "Projek Penawaran Telephone Panasonic + Pemasangan untuk PT. NIKAWA TEXTILE INDUSTRY (Kawasan Industri Mitrakarawang).

Ringkasan Kebutuhan Proyek dari Sales:
1. Hardware:
- 1 Unit PABX Expansion Panasonic KX-NS320
- 1 Unit Card Connection PABX Panasonic KX-NS5130
- 1 Unit Telepon Card PABX Panasonic KX-NS5172
- 9 Unit Pesawat Telepon Panasonic KX-T7665

2. Material Support:
- Supreme ITC Cable 2x2x0,6mm (500 meter)
- Roset RJ11
- UTP Cable Cat6 (60 meter)
- Box Panel LSA
- Consumable and Miscelanious Material

3. Jasa Pemasangan & Akomodasi:
- Expansion cabinet installation & expansion master card installation
- Extension card installation
- Cabling installation and termination for 10 Node new line
- Replace existing cabling installation to ensure sufficient space
- Telephone installation & oval duct installation (covered with warning tape)
- Documentation, programming, and configuration
- Manpower teknisi Pedia, akomodasi, bensin, tol, dan mobdemob

Catatan Teknis & Operasional Lapangan:
- Unit READY LIMITED stok tidak mengikat
- Pengerjaan weekend (Sabtu - Minggu), estimasi durasi 3 - 4 minggu
- Pekerjaan di ketinggian dilakukan oleh teknisi bersertifikasi (TKBT2)
- Dukungan alat bantu ketinggian (scaffolding dan mobile ladder) disediakan oleh user / klien
- Sistem pembayaran 30 hari dari tanggal invoice

Instruksi untuk Tim Admin Purchase:
Sales sengaja hanya mengisi catatan kebutuhan lapangan tanpa memasukkan rincian item barang. Mohon tim Admin Purchase bantu input seluruh item kebutuhan ke form HPP Margin (Hardware, Material Support, dan Jasa Pemasangan), tentukan modal HPP, ongkir, serta hitung margin dan pembulatan ceiling sesuai acuan projek.";

        // Buat RFQ Projek dengan 0 item (hanya catatan sales)
        $rfq = Rfq::create([
            'rfq_number'          => Rfq::generateRfqNumber(),
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_code'       => $customer->company_code,
            'customer_contact_id' => $contact->id,
            'type'                => 'Projek',
            'priority'            => 'High Priority',
            'sales_id'            => $sales->id,
            'sales_name'          => $sales->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => $salesNotes,
            'status'              => Rfq::STATUS_PENDING_ADMIN,
        ]);

        // Kirim notifikasi ke Admin Purchase dan Super Admin
        $admins = User::whereIn('role', ['Admin', 'Admin Purchase', 'Super Admin'])->get();
        foreach ($admins as $adm) {
            Notification::send(
                $adm->id,
                'warning',
                'RFQ Projek Baru Diterima',
                'RFQ Projek baru (' . $rfq->rfq_number . ') untuk ' . $rfq->customer_name . ' telah dibuat oleh Sales ' . $sales->name . ' (hanya catatan kebutuhan). Mohon segera isi rincian HPP dan margin.',
                route('rfq.show', $rfq)
            );
        }

        $this->info('BERHASIL MEMBUAT RFQ PROJEK NIKAWA:');
        $this->line('Nomor RFQ : ' . $rfq->rfq_number . ' (ID: ' . $rfq->id . ')');
        $this->line('Customer  : ' . $rfq->customer_name);
        $this->line('Tipe      : ' . $rfq->type);
        $this->line('Sales     : ' . $rfq->sales_name);
        $this->line('Status    : ' . $rfq->status . ' (Items: 0)');

        return 0;
    }
}
