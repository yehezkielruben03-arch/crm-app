<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\SalesTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardSalesPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_performance_widget_counts_po_goal_in_current_month_even_if_rfq_created_in_previous_month(): void
    {
        Carbon::setTestNow('2026-11-17 12:00:00');

        $admin = User::factory()->create([
            'role'   => 'Admin',
            'status' => 'Active',
        ]);

        $sales = User::factory()->create([
            'name'           => 'Yudi Siswanto',
            'role'           => 'Sales',
            'status'         => 'Active',
            'monthly_target' => 500000000,
        ]);

        SalesTarget::create([
            'sales_id'      => $sales->id,
            'target_month'  => 11,
            'target_year'   => 2026,
            'target_amount' => 500000000,
        ]);

        $customer1 = Customer::create([
            'company_name' => 'PT Wooin Electronics Indonesia',
            'sales_id'     => $sales->id,
            'status'       => 'Active',
        ]);

        $customer2 = Customer::create([
            'company_name' => 'PT NSK-Warner Indonesia',
            'sales_id'     => $sales->id,
            'status'       => 'Active',
        ]);

        // RFQ 1 (Wooin): dibuat 28 Oktober 2026 (bulan 10), tapi PO Goal 13 November 2026
        $rfq1 = Rfq::create([
            'rfq_number'    => '261028-0020',
            'customer_id'   => $customer1->id,
            'customer_name' => $customer1->company_name,
            'sales_id'      => $sales->id,
            'sales_name'    => $sales->name,
            'rfq_date'      => '2026-10-28',
            'status'        => Rfq::STATUS_GOAL,
            'updated_at'    => '2026-11-13 17:55:00',
        ]);

        RfqItem::create([
            'rfq_id'             => $rfq1->id,
            'product_name'       => 'Printer Jet T650',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 18000000,
            'margin'             => 15,
            'price_after_margin' => 20700000,
        ]);

        PurchaseOrder::create([
            'po_number'   => 'PO-2026-0020',
            'customer_id' => $customer1->id,
            'sales_id'    => $sales->id,
            'rfq_id'      => $rfq1->id,
            'po_date'     => '2026-11-13',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 20700000,
            'tax_amount'  => 2277000,
            'grand_total' => 22977000,
            'approved_at' => '2026-11-13 17:55:00',
        ]);

        // RFQ 2 (NSK): Dibuat 13 November 2026. Draf RFQ Rp 20.180.000, tapi PO deal Rp 17.440.000
        $rfq2 = Rfq::create([
            'rfq_number'    => '261113-0102',
            'customer_id'   => $customer2->id,
            'customer_name' => $customer2->company_name,
            'sales_id'      => $sales->id,
            'sales_name'    => $sales->name,
            'rfq_date'      => '2026-11-13',
            'status'        => Rfq::STATUS_GOAL,
            'updated_at'    => '2026-11-17 14:50:00',
        ]);

        RfqItem::create([
            'rfq_id'             => $rfq2->id,
            'product_name'       => 'Monitor Set',
            'qty'                => 1,
            'unit'               => 'Set',
            'hpp'                => 17000000,
            'margin'             => 18.7,
            'price_after_margin' => 20180000, // Draf RFQ
        ]);

        PurchaseOrder::create([
            'po_number'   => 'PO-2026-0025',
            'customer_id' => $customer2->id,
            'sales_id'    => $sales->id,
            'rfq_id'      => $rfq2->id,
            'po_date'     => '2026-11-17',
            'status'      => PurchaseOrder::STATUS_GOAL,
            'subtotal'    => 17440000, // Real deal PO
            'tax_amount'  => 1918400,
            'grand_total' => 19358400,
            'approved_at' => '2026-11-17 14:50:00',
        ]);

        Cache::flush();

        // Admin mengakses Dashboard
        $response = $this->actingAs($admin)->get(route('dashboard'));
        $response->assertOk();

        // Pastikan view menerima data salesPerformance dengan angka yang benar
        $response->assertViewHas('salesPerformance', function ($perf) {
            $yudi = collect($perf)->firstWhere('name', 'Yudi Siswanto');
            // Wooin (20.700.000) + NSK (17.440.000) = 38.140.000
            return $yudi !== null 
                && (float) $yudi['achieved'] === 38140000.0
                && $yudi['percentage'] === 7.6;
        });

        // Pastikan revenueThisMonth juga menghitung 38.140.000
        $response->assertViewHas('revenueThisMonth', 38140000.0);

        Carbon::setTestNow();
    }
}
