<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestingDateFilterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset / Ensure Test Users exist with password: password123
        $usersData = [
            [
                'name'     => 'Ruben (Super Admin)',
                'username' => 'ruben',
                'email'    => 'ruben@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Super Admin',
                'status'   => 'Active',
            ],
            [
                'name'     => 'Admin Purchase',
                'username' => 'admin_purchase',
                'email'    => 'admin@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Admin',
                'status'   => 'Active',
            ],
            [
                'name'     => 'Laras (Leader)',
                'username' => 'laras',
                'email'    => 'laras@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Leader',
                'status'   => 'Active',
            ],
            [
                'name'     => 'Ade Zulvida (Sales)',
                'username' => 'ade_zulvida',
                'email'    => 'ade@crm.com',
                'password' => Hash::make('password123'),
                'role'     => 'Sales',
                'status'   => 'Active',
                'monthly_target' => 500000000,
            ],
        ];

        $users = [];
        foreach ($usersData as $ud) {
            $u = User::updateOrCreate(
                ['email' => $ud['email']],
                $ud
            );
            $users[$ud['email']] = $u;
        }

        $sales = $users['ade@crm.com'];
        $leader = $users['laras@crm.com'];
        $admin = $users['admin@crm.com'];

        // 2. Customers with specific dates
        // Minggu Ini (Senin 26 Okt - Jumat 30 Okt 2026)
        $custMon = Customer::updateOrCreate(
            ['company_name' => 'PT Nusantara Solusi Prima (Senin)'],
            [
                'company_code' => 'NSP-01',
                'sales_id'     => $sales->id,
                'status'       => 'Active',
                'industry'     => 'Teknologi Informasi',
                'region'       => 'DKI Jakarta',
                'email'        => 'procurement@nusantarasolusi.co.id',
                'phone'        => '081211112222',
                'created_at'   => Carbon::parse('2026-10-26 09:30:00'),
                'updated_at'   => Carbon::parse('2026-10-26 09:30:00'),
            ]
        );

        $custWed = Customer::updateOrCreate(
            ['company_name' => 'CV Bintang Samudera (Rabu)'],
            [
                'company_code' => 'BSS-02',
                'sales_id'     => $sales->id,
                'status'       => 'Active',
                'industry'     => 'Manufaktur & Logistik',
                'region'       => 'Jawa Barat',
                'email'        => 'purchasing@bintangsamudera.com',
                'phone'        => '081233334444',
                'created_at'   => Carbon::parse('2026-10-28 11:15:00'),
                'updated_at'   => Carbon::parse('2026-10-28 11:15:00'),
            ]
        );

        $custFri = Customer::updateOrCreate(
            ['company_name' => 'PT Cipta Kreasi Digital (Jumat)'],
            [
                'company_code' => 'CKD-03',
                'sales_id'     => $sales->id,
                'status'       => 'Pending',
                'industry'     => 'Digital Agency',
                'region'       => 'DKI Jakarta',
                'email'        => 'contact@ciptakreasi.id',
                'phone'        => '081255556666',
                'created_at'   => Carbon::parse('2026-10-30 14:00:00'),
                'updated_at'   => Carbon::parse('2026-10-30 14:00:00'),
            ]
        );

        // Hari Ini (Sabtu 31 Okt 2026)
        $custToday = Customer::updateOrCreate(
            ['company_name' => 'PT Harapan Maju Bersama (Hari Ini)'],
            [
                'company_code' => 'HMB-04',
                'sales_id'     => $sales->id,
                'status'       => 'Prospect',
                'industry'     => 'Retail',
                'region'       => 'Banten',
                'email'        => 'info@harapanmaju.co.id',
                'phone'        => '081277778888',
                'created_at'   => Carbon::parse('2026-10-31 08:45:00'),
                'updated_at'   => Carbon::parse('2026-10-31 08:45:00'),
            ]
        );

        // Bulan Lalu (September 2026)
        $custLastMonth = Customer::updateOrCreate(
            ['company_name' => 'PT Global Industri Perkasa (Bulan Lalu)'],
            [
                'company_code' => 'GIP-05',
                'sales_id'     => $sales->id,
                'status'       => 'Active',
                'industry'     => 'Konstruksi',
                'region'       => 'Jawa Timur',
                'email'        => 'purchasing@globalindustri.com',
                'phone'        => '081299990000',
                'created_at'   => Carbon::parse('2026-09-15 10:00:00'),
                'updated_at'   => Carbon::parse('2026-09-15 10:00:00'),
            ]
        );

        // 3. RFQs and Items
        // ── RFQ 1: Senin, 26 Okt 2026 (Status: Approved) ──
        $rfq1 = Rfq::updateOrCreate(
            ['rfq_number' => 'RFQ/2026/10/TEST-001'],
            [
                'customer_id'   => $custMon->id,
                'customer_name' => $custMon->company_name,
                'customer_code' => $custMon->customer_code,
                'sales_id'      => $sales->id,
                'sales_name'    => $sales->name,
                'rfq_date'      => '2026-10-26',
                'need_date'     => '2026-11-10',
                'status'        => Rfq::STATUS_APPROVED,
                'type'          => 'Standar',
                'priority'      => Rfq::PRIORITY_NORMAL,
                'notes'         => 'Pengadaan Laptop Kantor i7 16GB',
                'created_at'    => Carbon::parse('2026-10-26 10:00:00'),
                'updated_at'    => Carbon::parse('2026-10-26 15:00:00'),
            ]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq1->id, 'product_name' => 'Lenovo ThinkPad E14 Gen 5 i7'],
            [
                'qty'                => 3,
                'unit'               => 'Unit',
                'hpp'                => 12000000,
                'margin'             => 25,
                'price_after_margin' => 15000000,
                'created_at'         => Carbon::parse('2026-10-26 10:00:00'),
            ]
        );

        // ── RFQ 2: Selasa, 27 Okt 2026 (Status: Pending Admin) ──
        $rfq2 = Rfq::updateOrCreate(
            ['rfq_number' => 'RFQ/2026/10/TEST-002'],
            [
                'customer_id'   => $custWed->id,
                'customer_name' => $custWed->company_name,
                'customer_code' => $custWed->customer_code,
                'sales_id'      => $sales->id,
                'sales_name'    => $sales->name,
                'rfq_date'      => '2026-10-27',
                'need_date'     => '2026-11-15',
                'status'        => Rfq::STATUS_PENDING_ADMIN,
                'type'          => 'Standar',
                'priority'      => Rfq::PRIORITY_HIGH,
                'notes'         => 'Switch Cisco Catalyst 24 Port PoE',
                'created_at'    => Carbon::parse('2026-10-27 14:20:00'),
                'updated_at'    => Carbon::parse('2026-10-27 14:20:00'),
            ]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq2->id, 'product_name' => 'Cisco Catalyst 24 Port Managed PoE+'],
            [
                'qty'                => 1,
                'unit'               => 'Unit',
                'hpp'                => 15000000,
                'margin'             => 20,
                'price_after_margin' => 18000000,
                'created_at'         => Carbon::parse('2026-10-27 14:20:00'),
            ]
        );

        // ── RFQ 3: Rabu, 28 Okt 2026 (Status: Quotation Sent) ──
        $rfq3 = Rfq::updateOrCreate(
            ['rfq_number' => 'RFQ/2026/10/TEST-003'],
            [
                'customer_id'   => $custWed->id,
                'customer_name' => $custWed->company_name,
                'customer_code' => $custWed->customer_code,
                'sales_id'      => $sales->id,
                'sales_name'    => $sales->name,
                'rfq_date'      => '2026-10-28',
                'need_date'     => '2026-11-20',
                'status'        => Rfq::STATUS_QUOTATION_SENT,
                'type'          => 'Projek',
                'priority'      => Rfq::PRIORITY_NORMAL,
                'notes'         => 'Pengadaan Server Rakitan + Rackmount',
                'created_at'    => Carbon::parse('2026-10-28 11:30:00'),
                'updated_at'    => Carbon::parse('2026-10-28 16:45:00'),
            ]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq3->id, 'product_name' => 'Custom Server 2U Dual Xeon 64GB'],
            [
                'qty'                => 1,
                'unit'               => 'Unit',
                'hpp'                => 60000000,
                'margin'             => 25,
                'price_after_margin' => 75000000,
                'created_at'         => Carbon::parse('2026-10-28 11:30:00'),
            ]
        );

        // ── RFQ 4: Kamis, 29 Okt 2026 (Status: GOAL & PO Terbit!) ──
        $rfq4 = Rfq::updateOrCreate(
            ['rfq_number' => 'RFQ/2026/10/TEST-004-GOAL'],
            [
                'customer_id'   => $custMon->id,
                'customer_name' => $custMon->company_name,
                'customer_code' => $custMon->customer_code,
                'sales_id'      => $sales->id,
                'sales_name'    => $sales->name,
                'rfq_date'      => '2026-10-26',
                'need_date'     => '2026-11-05',
                'status'        => Rfq::STATUS_GOAL,
                'type'          => 'Projek',
                'priority'      => Rfq::PRIORITY_URGENT,
                'notes'         => 'Deal GOAL! Server HPE DL380 Gen11',
                'created_at'    => Carbon::parse('2026-10-27 10:00:00'),
                'updated_at'    => Carbon::parse('2026-10-29 15:30:00'), // Goal approved Kamis
            ]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq4->id, 'product_name' => 'Server HPE ProLiant DL380 Gen11 Enterprise'],
            [
                'qty'                => 1,
                'unit'               => 'Unit',
                'hpp'                => 95000000,
                'margin'             => 26.3,
                'price_after_margin' => 120000000,
                'created_at'         => Carbon::parse('2026-10-27 10:00:00'),
            ]
        );
        PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO/2026/10/TEST-004'],
            [
                'customer_id'   => $custMon->id,
                'sales_id'      => $sales->id,
                'rfq_id'        => $rfq4->id,
                'rfq_reference' => $rfq4->rfq_number,
                'po_date'       => Carbon::parse('2026-10-29'),
                'subtotal'      => 120000000,
                'tax_amount'    => 13200000,
                'grand_total'   => 133200000,
                'status'        => PurchaseOrder::STATUS_GOAL,
                'approved_by'   => $leader->id,
                'approved_at'   => Carbon::parse('2026-10-29 15:30:00'),
                'created_at'    => Carbon::parse('2026-10-29 15:30:00'),
                'updated_at'    => Carbon::parse('2026-10-29 15:30:00'),
            ]
        );

        // ── RFQ 5: Jumat, 30 Okt 2026 (Status: GOAL & PO Terbit!) ──
        $rfq5 = Rfq::updateOrCreate(
            ['rfq_number' => 'RFQ/2026/10/TEST-005-GOAL'],
            [
                'customer_id'   => $custFri->id,
                'customer_name' => $custFri->company_name,
                'customer_code' => $custFri->customer_code,
                'sales_id'      => $sales->id,
                'sales_name'    => $sales->name,
                'rfq_date'      => '2026-10-28',
                'need_date'     => '2026-11-12',
                'status'        => Rfq::STATUS_GOAL,
                'type'          => 'Standar',
                'priority'      => Rfq::PRIORITY_NORMAL,
                'notes'         => 'Deal GOAL! 5 Unit PC Workstation CAD',
                'created_at'    => Carbon::parse('2026-10-28 16:00:00'),
                'updated_at'    => Carbon::parse('2026-10-30 16:30:00'), // Goal approved Jumat
            ]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq5->id, 'product_name' => 'Workstation PC Core i9 RTX 4070'],
            [
                'qty'                => 5,
                'unit'               => 'Unit',
                'hpp'                => 10000000,
                'margin'             => 30,
                'price_after_margin' => 13000000, // Total 5 x 13jt = 65jt
                'created_at'         => Carbon::parse('2026-10-28 16:00:00'),
            ]
        );
        PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO/2026/10/TEST-005'],
            [
                'customer_id'   => $custFri->id,
                'sales_id'      => $sales->id,
                'rfq_id'        => $rfq5->id,
                'rfq_reference' => $rfq5->rfq_number,
                'po_date'       => Carbon::parse('2026-10-30'),
                'subtotal'      => 65000000,
                'tax_amount'    => 7150000,
                'grand_total'   => 72150000,
                'status'        => PurchaseOrder::STATUS_GOAL,
                'approved_by'   => $leader->id,
                'approved_at'   => Carbon::parse('2026-10-30 16:30:00'),
                'created_at'    => Carbon::parse('2026-10-30 16:30:00'),
                'updated_at'    => Carbon::parse('2026-10-30 16:30:00'),
            ]
        );

        // ── RFQ 6: Hari Ini (Sabtu, 31 Okt 2026) ──
        $rfq6 = Rfq::updateOrCreate(
            ['rfq_number' => 'RFQ/2026/10/TEST-006-TODAY'],
            [
                'customer_id'   => $custToday->id,
                'customer_name' => $custToday->company_name,
                'customer_code' => $custToday->customer_code,
                'sales_id'      => $sales->id,
                'sales_name'    => $sales->name,
                'rfq_date'      => '2026-10-31',
                'need_date'     => '2026-11-25',
                'status'        => Rfq::STATUS_PENDING_LEADER,
                'type'          => 'Standar',
                'priority'      => Rfq::PRIORITY_NORMAL,
                'notes'         => 'Penawaran Access Point WiFi 6 Aruba',
                'created_at'    => Carbon::parse('2026-10-31 09:15:00'),
                'updated_at'    => Carbon::parse('2026-10-31 09:15:00'),
            ]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq6->id, 'product_name' => 'Aruba Instant On AP22 WiFi 6 Access Point'],
            [
                'qty'                => 10,
                'unit'               => 'Unit',
                'hpp'                => 2800000,
                'margin'             => 25,
                'price_after_margin' => 3500000, // Total 10 x 3.5jt = 35jt
                'created_at'         => Carbon::parse('2026-10-31 09:15:00'),
            ]
        );

        // ── RFQ 7: Bulan Lalu (September 2026) (Status: GOAL) ──
        $rfq7 = Rfq::updateOrCreate(
            ['rfq_number' => 'RFQ/2026/09/TEST-007-GOAL'],
            [
                'customer_id'   => $custLastMonth->id,
                'customer_name' => $custLastMonth->company_name,
                'customer_code' => $custLastMonth->customer_code,
                'sales_id'      => $sales->id,
                'sales_name'    => $sales->name,
                'rfq_date'      => '2026-09-15',
                'need_date'     => '2026-09-30',
                'status'        => Rfq::STATUS_GOAL,
                'type'          => 'Projek',
                'priority'      => Rfq::PRIORITY_NORMAL,
                'notes'         => 'Server Storage Synology RackStation Bulan Lalu',
                'created_at'    => Carbon::parse('2026-09-15 10:00:00'),
                'updated_at'    => Carbon::parse('2026-09-22 14:00:00'),
            ]
        );
        RfqItem::updateOrCreate(
            ['rfq_id' => $rfq7->id, 'product_name' => 'Synology RS4021xs+ 16-Bay NAS Storage'],
            [
                'qty'                => 2,
                'unit'               => 'Unit',
                'hpp'                => 85000000,
                'margin'             => 23.5,
                'price_after_margin' => 105000000, // Total 2 x 105jt = 210jt
                'created_at'         => Carbon::parse('2026-09-15 10:00:00'),
            ]
        );
        PurchaseOrder::updateOrCreate(
            ['po_number' => 'PO/2026/09/TEST-007'],
            [
                'customer_id'   => $custLastMonth->id,
                'sales_id'      => $sales->id,
                'rfq_id'        => $rfq7->id,
                'rfq_reference' => $rfq7->rfq_number,
                'po_date'       => Carbon::parse('2026-09-22'),
                'subtotal'      => 210000000,
                'tax_amount'    => 23100000,
                'grand_total'   => 233100000,
                'status'        => PurchaseOrder::STATUS_GOAL,
                'approved_by'   => $leader->id,
                'approved_at'   => Carbon::parse('2026-09-22 14:00:00'),
                'created_at'    => Carbon::parse('2026-09-22 14:00:00'),
                'updated_at'    => Carbon::parse('2026-09-22 14:00:00'),
            ]
        );

        $this->command->info('✅ Testing Data Filter Seeder completed successfully!');
    }
}
