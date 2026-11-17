<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckSalesPoCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_check_sales_po_command_runs_cleanly(): void
    {
        $sales = User::factory()->create([
            'role'   => 'Sales',
            'name'   => 'Yudi Siswanto',
            'status' => 'Active',
        ]);

        $customer1 = Customer::factory()->create([
            'company_name' => 'PT Wooin Indonesia',
            'sales_id'     => $sales->id,
            'status'       => 'Active',
        ]);

        $customer2 = Customer::factory()->create([
            'company_name' => 'PT NSK Bearings',
            'sales_id'     => $sales->id,
            'status'       => 'Active',
        ]);

        // PO 1: Wooin (November)
        PurchaseOrder::create([
            'po_number'   => 'PO-WOOIN-001',
            'customer_id' => $customer1->id,
            'sales_id'    => $sales->id,
            'po_date'     => now()->startOfMonth()->addDays(2),
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 20180000,
            'tax_amount'  => 2219800,
            'grand_total' => 20700000,
        ]);

        // PO 2: NSK (RFQ dibuat Oktober, PO Goal November)
        $rfqNsk = Rfq::create([
            'rfq_number'    => 'RFQ-NSK-001',
            'customer_id'   => $customer2->id,
            'customer_name' => $customer2->company_name,
            'sales_id'      => $sales->id,
            'sales_name'    => $sales->name,
            'rfq_date'      => now()->subMonth()->format('Y-m-d'), // Dibuat bulan lalu
            'status'        => Rfq::STATUS_GOAL,
        ]);

        RfqItem::create([
            'rfq_id'             => $rfqNsk->id,
            'product_name'       => 'NSK Precision Bearing',
            'qty'                => 10,
            'hpp'                => 1200000,
            'price_after_margin' => 1744000,
            'margin_value'       => 20,
        ]);

        PurchaseOrder::create([
            'po_number'   => 'PO-NSK-002',
            'rfq_id'      => $rfqNsk->id,
            'customer_id' => $customer2->id,
            'sales_id'    => $sales->id,
            'po_date'     => now()->startOfMonth()->addDays(5),
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 17440000,
            'tax_amount'  => 1918400,
            'grand_total' => 19358400,
        ]);

        // Jalankan command crm:check-po
        $this->artisan('crm:check-po')
            ->expectsOutputToContain('PEDIA CRM - AUDIT REALTIME PO & OMSET SETIAP SALES')
            ->expectsOutputToContain('Yudi Siswanto')
            ->expectsOutputToContain('Wooin')
            ->expectsOutputToContain('NSK')
            ->assertExitCode(0);
    }
}
