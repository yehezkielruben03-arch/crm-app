<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseSixDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_high_value_opportunity_summary(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin Phase Six',
            'username' => 'admin_phase_six',
            'email' => 'admin_phase_six@test.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        Customer::create([
            'company_name' => 'PT Phase Six',
            'status' => Customer::STATUS_PROSPECT,
            'company_code' => 'PTP62026070000001',
            'sales_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        PurchaseOrder::create([
            'po_number' => 'PO-2026-0003',
            'customer_id' => 1,
            'sales_id' => $admin->id,
            'po_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_PENDING,
            'grand_total' => 4000000,
            'subtotal' => 4000000,
            'tax_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Potensi Nilai Tertinggi');
        $response->assertSee('Review customer approval');
    }
}
