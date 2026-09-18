<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SalesDashboardCustomerHealthTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_dashboard_treats_old_purchase_orders_as_follow_up_and_inactive(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $sales = User::factory()->create([
            'name' => 'Sales Health',
            'username' => 'sales_health',
            'email' => 'sales.health@test.com',
            'password' => Hash::make('password'),
            'role' => 'Sales',
            'status' => 'Active',
        ]);

        $followUpCustomer = Customer::create([
            'company_name' => 'PT Follow Up Customer',
            'status' => Customer::STATUS_ACTIVE,
            'company_code' => 'PTI202607220000001',
            'sales_id' => $sales->id,
            'created_by' => $sales->id,
        ]);

        $inactiveCustomer = Customer::create([
            'company_name' => 'PT Inactive Customer',
            'status' => Customer::STATUS_ACTIVE,
            'company_code' => 'PTI202607220000002',
            'sales_id' => $sales->id,
            'created_by' => $sales->id,
        ]);

        PurchaseOrder::create([
            'po_number' => 'PO-2026-0001',
            'customer_id' => $followUpCustomer->id,
            'sales_id' => $sales->id,
            'po_date' => now()->subMonths(4)->toDateString(),
            'status' => PurchaseOrder::STATUS_GOAL,
            'grand_total' => 5000000,
            'subtotal' => 5000000,
            'tax_amount' => 0,
        ]);

        PurchaseOrder::create([
            'po_number' => 'PO-2026-0002',
            'customer_id' => $inactiveCustomer->id,
            'sales_id' => $sales->id,
            'po_date' => now()->subMonths(14)->toDateString(),
            'status' => PurchaseOrder::STATUS_GOAL,
            'grand_total' => 5000000,
            'subtotal' => 5000000,
            'tax_amount' => 0,
        ]);

        $response = $this->actingAs($sales)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('Perlu Follow Up (>3 bln)', false);
        $response->assertSeeText('Klien Tidak Aktif (>12 bln)', false);
        $response->assertSeeText('PT Follow Up Customer');
        $response->assertSeeText('PT Inactive Customer');
    }
}
