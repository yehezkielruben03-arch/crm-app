<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditRevenueCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_revenue_command_runs_cleanly_and_proves_totals(): void
    {
        Carbon::setTestNow('2026-11-17 12:00:00');

        $sales = User::factory()->create([
            'name'   => 'Ade Zulvida',
            'role'   => 'Sales',
            'status' => 'Active',
        ]);

        $customer = Customer::create([
            'company_name' => 'PT Maruka Indonesia',
            'sales_id'     => $sales->id,
            'status'       => 'Active',
        ]);

        $rfq = Rfq::create([
            'rfq_number'    => '261105-0062',
            'customer_id'   => $customer->id,
            'customer_name' => $customer->company_name,
            'sales_id'      => $sales->id,
            'sales_name'    => $sales->name,
            'rfq_date'      => '2026-11-05',
            'status'        => Rfq::STATUS_GOAL,
            'updated_at'    => '2026-11-12 10:00:00',
        ]);

        RfqItem::create([
            'rfq_id'             => $rfq->id,
            'product_name'       => 'Sparepart Mesin',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 35000000,
            'margin'             => 22.2,
            'price_after_margin' => 42800000, // Draf RFQ awal Rp 42.8 Jt
        ]);

        PurchaseOrder::create([
            'po_number'   => 'PO-2026-0010',
            'customer_id' => $customer->id,
            'sales_id'    => $sales->id,
            'rfq_id'      => $rfq->id,
            'po_date'     => '2026-11-12',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 25750000, // Real Deal PO Rp 25.75 Jt
            'tax_amount'  => 2832500,
            'grand_total' => 28582500,
            'approved_at' => '2026-11-12 10:00:00',
        ]);

        $code = \Illuminate\Support\Facades\Artisan::call('crm:audit-revenue');
        $output = \Illuminate\Support\Facades\Artisan::output();
        
        $this->assertEquals(0, $code);
        $this->assertStringContainsString('FULL SYSTEM AUDIT', $output);
        $this->assertStringContainsString('PO-2026-0010', $output);
        $this->assertStringContainsString('42.800.000', $output);
        $this->assertStringContainsString('25.750.000', $output);
        $this->assertStringContainsString('-Rp 17.050.000', $output);

        Carbon::setTestNow();
    }
}
