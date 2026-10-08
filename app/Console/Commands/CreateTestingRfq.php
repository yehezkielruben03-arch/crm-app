<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Notification;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Console\Command;

class CreateTestingRfq extends Command
{
    protected $signature = 'rfq:create-testing';
    protected $description = 'Buat 2 RFQ baru oleh Sales (Non Projek dan Projek) hanya dengan catatan tanpa item untuk testing Admin';

    public function handle(): int
    {
        $this->info('Memulai pembuatan RFQ testing...');

        $sales = User::where('email', 'ade@crm.com')->first();
        if (!$sales) {
            $sales = User::whereIn('role', ['Sales', 'Sales Marketing'])->first();
        }

        if (!$sales) {
            $this->error('User Sales tidak ditemukan!');
            return 1;
        }

        // 1. Pastikan Customer 1 dan Customer 2 tersedia
        $cust1 = Customer::first();
        if (!$cust1) {
            $cust1 = Customer::create([
                'company_name' => 'PT Mega Solusi Teknologi',
                'company_code' => 'MST-001',
                'status'       => 'Active',
                'sales_id'     => $sales->id,
            ]);
        } else {
            $cust1->update([
                'status'       => 'Active',
                'sales_id'     => $sales->id,
                'company_code' => $cust1->company_code ?: 'MST-001',
            ]);
        }

        $contact1 = CustomerContact::where('customer_id', $cust1->id)->first();
        if (!$contact1) {
            $contact1 = CustomerContact::create([
                'customer_id' => $cust1->id,
                'name'        => 'Budi Santoso',
                'position'    => 'Procurement Manager',
                'phone'       => '081234567890',
                'email'       => 'budi@megasolusi.com',
                'is_primary'  => true,
            ]);
        }

        $cust2 = Customer::skip(1)->first();
        if (!$cust2) {
            $cust2 = Customer::create([
                'company_name' => 'PT Kreasi Sinergi Digital',
                'company_code' => 'KSD-002',
                'status'       => 'Active',
                'sales_id'     => $sales->id,
            ]);
        } else {
            $cust2->update([
                'status'       => 'Active',
                'sales_id'     => $sales->id,
                'company_code' => $cust2->company_code ?: 'KSD-002',
            ]);
        }

        $contact2 = CustomerContact::where('customer_id', $cust2->id)->first();
        if (!$contact2) {
            $contact2 = CustomerContact::create([
                'customer_id' => $cust2->id,
                'name'        => 'Dewi Anggraini',
                'position'    => 'Facility & IT Manager',
                'phone'       => '081298877110',
                'email'       => 'dewi@kreasinergi.co.id',
                'is_primary'  => true,
            ]);
        }

        // 2. Buat RFQ Non Projek (Hanya Catatan, Item 0)
        $rfqNonProjek = Rfq::create([
            'rfq_number'          => Rfq::generateRfqNumber(),
            'customer_id'         => $cust1->id,
            'customer_name'       => $cust1->company_name,
            'customer_code'       => $cust1->company_code,
            'customer_contact_id' => $contact1->id,
            'type'                => 'Non Projek',
            'priority'            => 'Normal',
            'sales_id'            => $sales->id,
            'sales_name'          => $sales->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => 'Kebutuhan pengadaan 5 unit Laptop Asus VivoBook 14 inch Core i5 RAM 16GB SSD 512GB untuk tim operasional finance. Mohon tim admin bantu carikan HPP dan hitung margin terbaik.',
            'status'              => Rfq::STATUS_PENDING_ADMIN,
        ]);

        // 3. Buat RFQ Projek (Hanya Catatan, Item 0)
        $rfqProjek = Rfq::create([
            'rfq_number'          => Rfq::generateRfqNumber(),
            'customer_id'         => $cust2->id,
            'customer_name'       => $cust2->company_name,
            'customer_code'       => $cust2->company_code,
            'customer_contact_id' => $contact2->id,
            'type'                => 'Projek',
            'priority'            => 'Normal',
            'sales_id'            => $sales->id,
            'sales_name'          => $sales->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => 'Permintaan instalasi Access Door dan Face Recognition untuk 2 pintu kantor utama PT Kreasi Sinergi Digital. Kebutuhan mencakup hardware magnet lock, bracket, exit button, jasa instalasi, serta material pipa dan kabel. Mohon admin susunkan rincian HPP dan margin.',
            'status'              => Rfq::STATUS_PENDING_ADMIN,
        ]);

        // 4. Kirim Notifikasi ke Admin Purchase dan Super Admin
        $admins = User::whereIn('role', ['Admin', 'Admin Purchase', 'Super Admin'])->get();
        foreach ($admins as $adm) {
            Notification::send(
                $adm->id,
                'warning',
                'RFQ Baru Diterima',
                'RFQ Baru diterima dari Sales ' . $sales->name . '! (' . $rfqNonProjek->rfq_number . ') untuk ' . $rfqNonProjek->customer_name . '. Mohon segera diisi HPP.',
                route('rfq.show', $rfqNonProjek)
            );

            Notification::send(
                $adm->id,
                'warning',
                'RFQ Baru Diterima',
                'RFQ Baru diterima dari Sales ' . $sales->name . '! (' . $rfqProjek->rfq_number . ') untuk ' . $rfqProjek->customer_name . '. Mohon segera diisi HPP.',
                route('rfq.show', $rfqProjek)
            );
        }

        $this->info('BERHASIL DIBUAT DUA RFQ TESTING:');
        $this->line('1. RFQ Non Projek: ' . $rfqNonProjek->rfq_number . ' (ID: ' . $rfqNonProjek->id . ')');
        $this->line('   Customer : ' . $rfqNonProjek->customer_name);
        $this->line('   Status   : ' . $rfqNonProjek->status . ' (Items: 0)');
        $this->line('2. RFQ Projek     : ' . $rfqProjek->rfq_number . ' (ID: ' . $rfqProjek->id . ')');
        $this->line('   Customer : ' . $rfqProjek->customer_name);
        $this->line('   Status   : ' . $rfqProjek->status . ' (Items: 0)');

        return 0;
    }
}
