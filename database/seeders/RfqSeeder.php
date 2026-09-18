<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class RfqSeeder extends Seeder
{
    public function run(): void
    {
        $ade = User::where('username', 'ade_zulvida')->first();
        $sales1 = User::where('username', 'sales_1')->first();
        $sales2 = User::where('username', 'sales_2')->first();

        $customer1 = Customer::where('company_name', 'PT. Karya Bangsa Tbk')->first();
        $customer2 = Customer::where('company_name', 'CV. Sehat Selalu')->first();
        $customer3 = Customer::where('company_name', 'Toko Makmur Sentosa')->first();

        $admin = User::where('role', 'Admin')->first();
        $leader = User::where('role', 'Leader')->first();

        if (!$ade || !$sales1 || !$sales2 || !$customer1 || !$customer2 || !$customer3) {
            $this->command->warn('⚠️  Skip RfqSeeder — missing users or customers');
            return;
        }

        // ════════════════════════════════════════════════════════════════
        // ADE ZULVIDA — 3 RFQ (customer PT. Karya Bangsa Tbk)
        // ════════════════════════════════════════════════════════════════

        // ── RFQ #1: Pending Admin (menunggu Admin isi harga) ──
        $rfq1 = Rfq::create([
            'rfq_number'  => Rfq::generateRfqNumber(),
            'customer_id' => $customer1->id,
            'customer_name' => $customer1->company_name,
            'customer_code' => $customer1->customer_code,
            'sales_id'    => $ade->id,
            'sales_name'  => $ade->name,
            'rfq_date'    => now()->subDays(2),
            'need_date'   => now()->addDays(14),
            'status'      => Rfq::STATUS_PENDING_ADMIN,
            'type'        => 'Proyek Baru',
            'notes'       => 'Butuh penawaran untuk server baru.',
        ]);

        RfqItem::create([
            'rfq_id'    => $rfq1->id,
            'product_name' => 'Server HPE ProLiant DL380 Gen11',
            'qty'       => 1,
            'unit'      => 'Unit',
            'description' => 'Server untuk aplikasi ERP',
        ]);

        // ── RFQ #2: Quotation Created (quotation sudah dikirim ke customer) ──
        $rfq2 = Rfq::create([
            'rfq_number'  => Rfq::generateRfqNumber(),
            'customer_id' => $customer1->id,
            'customer_name' => $customer1->company_name,
            'customer_code' => $customer1->customer_code,
            'sales_id'    => $ade->id,
            'sales_name'  => $ade->name,
            'rfq_date'    => now()->subDays(7),
            'need_date'   => now()->addDays(10),
            'status'      => Rfq::STATUS_QUOTATION_CREATED,
            'type'        => 'Repeat Order',
            'notes'       => 'Lisensi tahunan Office 365.',
        ]);

        RfqItem::create([
            'rfq_id'    => $rfq2->id,
            'product_name' => 'Microsoft 365 Business Premium',
            'qty'       => 25,
            'unit'      => 'Lisensi',
            'description' => 'Langganan 1 tahun',
            'hpp'       => 1500000,
            'ongkir_pedia' => 0,
            'ongkir_pelanggan' => 0,
            'margin'    => 1.2,
            'ceiling'   => 10000,
            'price_after_margin' => 1800000,
        ]);

        Quotation::create([
            'quo_number' => Quotation::generateQuoNumber(),
            'rfq_id'     => $rfq2->id,
            'customer_id' => $customer1->id,
            'sales_id'   => $ade->id,
            'created_by' => $ade->id,
            'status'     => Quotation::STATUS_SENT,
        ]);

        // ── RFQ #3: GOAL (deal selesai) ──
        $rfq3 = Rfq::create([
            'rfq_number'  => Rfq::generateRfqNumber(),
            'customer_id' => $customer1->id,
            'customer_name' => $customer1->company_name,
            'customer_code' => $customer1->customer_code,
            'sales_id'    => $ade->id,
            'sales_name'  => $ade->name,
            'rfq_date'    => now()->subDays(14),
            'need_date'   => now()->addDays(5),
            'status'      => Rfq::STATUS_GOAL,
            'type'        => 'Proyek Baru',
            'notes'       => 'Deal — server sudah dikirim.',
        ]);

        RfqItem::create([
            'rfq_id'    => $rfq3->id,
            'product_name' => 'Cisco Switch Catalyst 9300',
            'qty'       => 3,
            'unit'      => 'Unit',
            'description' => 'Switch 48 port PoE',
            'hpp'       => 25000000,
            'ongkir_pedia' => 100000,
            'ongkir_pelanggan' => 150000,
            'margin'    => 1.15,
            'ceiling'   => 50000,
            'price_after_margin' => 29000000,
        ]);

        // ════════════════════════════════════════════════════════════════
        // SALES 1 — 2 RFQ (customer CV. Sehat Selalu)
        // ════════════════════════════════════════════════════════════════

        // ── RFQ #4: Pending Leader (admin sudah isi harga, menunggu leader) ──
        $rfq4 = Rfq::create([
            'rfq_number'  => Rfq::generateRfqNumber(),
            'customer_id' => $customer2->id,
            'customer_name' => $customer2->company_name,
            'customer_code' => $customer2->customer_code,
            'sales_id'    => $sales1->id,
            'sales_name'  => $sales1->name,
            'rfq_date'    => now()->subDays(3),
            'need_date'   => now()->addDays(20),
            'status'      => Rfq::STATUS_PENDING_LEADER,
            'type'        => 'Lainnya',
            'notes'       => 'Pengadaan laptop untuk warehouse.',
        ]);

        RfqItem::create([
            'rfq_id'    => $rfq4->id,
            'product_name' => 'Laptop Lenovo ThinkPad X1 Carbon',
            'qty'       => 10,
            'unit'      => 'Unit',
            'description' => 'Laptop untuk tim warehouse',
            'hpp'       => 18000000,
            'ongkir_pedia' => 25000,
            'ongkir_pelanggan' => 50000,
            'margin'    => 1.1,
            'ceiling'   => 100000,
            'price_after_margin' => 19900000,
        ]);

        // ── RFQ #5: Approved (leader approve, siap buat quotation) ──
        $rfq5 = Rfq::create([
            'rfq_number'  => Rfq::generateRfqNumber(),
            'customer_id' => $customer2->id,
            'customer_name' => $customer2->company_name,
            'customer_code' => $customer2->customer_code,
            'sales_id'    => $sales1->id,
            'sales_name'  => $sales1->name,
            'rfq_date'    => now()->subDays(5),
            'need_date'   => now()->addDays(15),
            'status'      => Rfq::STATUS_APPROVED,
            'type'        => 'Repeat Order',
            'notes'       => 'Pesanan ulang UPS untuk gudang.',
        ]);

        RfqItem::create([
            'rfq_id'    => $rfq5->id,
            'product_name' => 'UPS APC Smart-UPS 3000VA',
            'qty'       => 5,
            'unit'      => 'Unit',
            'description' => 'UPS untuk server room',
            'hpp'       => 8500000,
            'ongkir_pedia' => 75000,
            'ongkir_pelanggan' => 100000,
            'margin'    => 1.12,
            'ceiling'   => 10000,
            'price_after_margin' => 9600000,
        ]);

        // ════════════════════════════════════════════════════════════════
        // SALES 2 — 2 RFQ (customer Toko Makmur Sentosa)
        // ════════════════════════════════════════════════════════════════

        // ── RFQ #6: Pending Admin (baru dibuat, belum diisi harga) ──
        $rfq6 = Rfq::create([
            'rfq_number'  => Rfq::generateRfqNumber(),
            'customer_id' => $customer3->id,
            'customer_name' => $customer3->company_name,
            'customer_code' => $customer3->customer_code,
            'sales_id'    => $sales2->id,
            'sales_name'  => $sales2->name,
            'rfq_date'    => now()->subDay(),
            'need_date'   => now()->addDays(30),
            'status'      => Rfq::STATUS_PENDING_ADMIN,
            'type'        => 'Proyek Baru',
            'notes'       => 'Pemasangan CCTV untuk toko.',
        ]);

        RfqItem::create([
            'rfq_id'    => $rfq6->id,
            'product_name' => 'CCTV Hikvision DS-2CD2T47G2-L',
            'qty'       => 16,
            'unit'      => 'Unit',
            'description' => 'Camera IP 4MP outdoor',
        ]);

        // ── RFQ #7: PO Received (Pending Leader) ──
        $rfq7 = Rfq::create([
            'rfq_number'  => Rfq::generateRfqNumber(),
            'customer_id' => $customer3->id,
            'customer_name' => $customer3->company_name,
            'customer_code' => $customer3->customer_code,
            'sales_id'    => $sales2->id,
            'sales_name'  => $sales2->name,
            'rfq_date'    => now()->subDays(10),
            'need_date'   => now()->addDays(7),
            'status'      => Rfq::STATUS_PO_PENDING_LEADER,
            'type'        => 'Repeat Order',
            'notes'       => 'PO sudah diupload, tinggal approval leader.',
        ]);

        RfqItem::create([
            'rfq_id'    => $rfq7->id,
            'product_name' => 'Kabel LAN Cat6 Belden 305m',
            'qty'       => 5,
            'unit'      => 'Box',
            'description' => 'Kabel UTP Cat6 305m per box',
            'hpp'       => 2500000,
            'ongkir_pedia' => 50000,
            'ongkir_pelanggan' => 50000,
            'margin'    => 1.1,
            'ceiling'   => 10000,
            'price_after_margin' => 2750000,
        ]);

        $this->command->info('✅ 7 RFQs seeded successfully!');
    }
}
