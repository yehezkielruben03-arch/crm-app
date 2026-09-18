<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $ade = User::where('username', 'ade_zulvida')->first();
        $sales1 = User::where('username', 'sales_1')->first();
        $sales2 = User::where('username', 'sales_2')->first();
        $admin = User::where('role', 'Super Admin')->first() ?? User::where('role', 'Admin')->first();

        $customer1 = Customer::where('company_name', 'PT. Karya Bangsa Tbk')->first();
        $customer2 = Customer::where('company_name', 'CV. Sehat Selalu')->first();
        $customer3 = Customer::where('company_name', 'Toko Makmur Sentosa')->first();

        if (!$ade || !$sales1 || !$sales2 || !$customer1 || !$customer2 || !$customer3) {
            $this->command->warn('⚠️  Skip PurchaseOrderSeeder — missing data');
            return;
        }

        $rfqGoal = Rfq::where('status', Rfq::STATUS_GOAL)->where('sales_id', $ade->id)->first();

        // ── PO #1: Ade — Goal (linked to RFQ Goal) ──
        $subtotal1 = 87000000;
        $tax1 = (int) ($subtotal1 * 0.11);
        $po1 = PurchaseOrder::create([
            'po_number'      => PurchaseOrder::generatePoNumber(),
            'customer_id'    => $customer1->id,
            'sales_id'       => $ade->id,
            'rfq_id'         => $rfqGoal?->id,
            'rfq_reference'  => $rfqGoal?->rfq_number,
            'po_date'        => now()->subDays(14)->format('Y-m-d'),
            'received_date'  => now()->subDays(13)->format('Y-m-d'),
            'due_date'       => now()->addDays(16)->format('Y-m-d'),
            'subtotal'       => $subtotal1,
            'tax_amount'     => $tax1,
            'grand_total'    => $subtotal1 + $tax1,
            'estimated_cost' => 65000000,
            'status'         => PurchaseOrder::STATUS_GOAL,
            'notes'          => 'PO sudah deal — barang dikirim.',
            'approved_by'    => $admin?->id,
            'approved_at'    => now()->subDays(10),
        ]);

        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po1->id, 'item_name' => 'Cisco Switch Catalyst 9300',  'quantity' => 3, 'unit_price' => 25000000, 'subtotal' => 75000000],
            ['purchase_order_id' => $po1->id, 'item_name' => 'SFP+ Module 10G',             'quantity' => 6, 'unit_price' => 2000000,  'subtotal' => 12000000],
        ]);

        // ── PO #2: Sales 1 — Pending (menunggu approval) ──
        $subtotal2 = 199000000;
        $tax2 = (int) ($subtotal2 * 0.11);
        $po2 = PurchaseOrder::create([
            'po_number'      => PurchaseOrder::generatePoNumber(),
            'customer_id'    => $customer2->id,
            'sales_id'       => $sales1->id,
            'po_date'        => now()->subDays(3)->format('Y-m-d'),
            'received_date'  => now()->subDays(2)->format('Y-m-d'),
            'due_date'       => now()->addDays(28)->format('Y-m-d'),
            'subtotal'       => $subtotal2,
            'tax_amount'     => $tax2,
            'grand_total'    => $subtotal2 + $tax2,
            'estimated_cost' => 150000000,
            'status'         => PurchaseOrder::STATUS_PENDING,
            'notes'          => 'PO baru dari CV. Sehat Selalu — menunggu review.',
        ]);

        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po2->id, 'item_name' => 'Laptop Lenovo ThinkPad X1 Carbon', 'quantity' => 10, 'unit_price' => 18000000, 'subtotal' => 180000000],
            ['purchase_order_id' => $po2->id, 'item_name' => 'Docking Station Lenovo',          'quantity' => 10, 'unit_price' => 1900000,  'subtotal' => 19000000],
        ]);

        // ── PO #3: Sales 2 — Revisi (perlu revisi) ──
        $subtotal3 = 44000000;
        $tax3 = (int) ($subtotal3 * 0.11);
        $po3 = PurchaseOrder::create([
            'po_number'      => PurchaseOrder::generatePoNumber(),
            'customer_id'    => $customer3->id,
            'sales_id'       => $sales2->id,
            'po_date'        => now()->subDays(7)->format('Y-m-d'),
            'received_date'  => now()->subDays(6)->format('Y-m-d'),
            'due_date'       => now()->addDays(23)->format('Y-m-d'),
            'subtotal'       => $subtotal3,
            'tax_amount'     => $tax3,
            'grand_total'    => $subtotal3 + $tax3,
            'estimated_cost' => 35000000,
            'status'         => PurchaseOrder::STATUS_REVISI,
            'notes'          => 'PO direvisi — harga tidak sesuai deal awal.',
            'rejection_reason' => 'Harga satuan melebihi estimasi, mohon disesuaikan.',
            'change_request_reason' => 'Harga kabel LAN terlalu tinggi, turunkan jadi Rp 2.200.000/box',
        ]);

        PurchaseOrderItem::insert([
            ['purchase_order_id' => $po3->id, 'item_name' => 'Kabel LAN Cat6 Belden 305m', 'quantity' => 5,  'unit_price' => 2500000, 'subtotal' => 12500000],
            ['purchase_order_id' => $po3->id, 'item_name' => 'RJ45 Cat6 Connector',        'quantity' => 200,'unit_price' => 5000,    'subtotal' => 1000000],
            ['purchase_order_id' => $po3->id, 'item_name' => 'Cable Tester Fluke',          'quantity' => 2,  'unit_price' => 7500000, 'subtotal' => 15000000],
        ]);

        $this->command->info('✅ 3 Purchase Orders with items seeded successfully!');
    }
}
