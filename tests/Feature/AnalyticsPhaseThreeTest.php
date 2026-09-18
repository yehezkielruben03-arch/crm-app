<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AnalyticsPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_page_displays_business_summary_for_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin Analytics',
            'username' => 'admin_analytics',
            'email' => 'admin_analytics@test.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        Customer::create([
            'company_name' => 'PT Analytics Test',
            'status' => Customer::STATUS_ACTIVE,
            'company_code' => 'PTI2026070000001',
            'sales_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        PurchaseOrder::create([
            'po_number' => 'PO-2026-0001',
            'customer_id' => 1,
            'sales_id' => $admin->id,
            'po_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_GOAL,
            'grand_total' => 25000000,
            'subtotal' => 25000000,
            'tax_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('analytics.index'));

        $response->assertOk();
        $response->assertSee('PT Analytics Test');
        $response->assertSee('Total Revenue');
        $response->assertSee('Menunggu Review');
    }
}
