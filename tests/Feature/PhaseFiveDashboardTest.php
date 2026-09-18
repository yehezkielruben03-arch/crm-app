<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseFiveDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_shows_priority_recommendations(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin Phase Five',
            'username' => 'admin_phase_five',
            'email' => 'admin_phase_five@test.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        Customer::create([
            'company_name' => 'PT Phase Five',
            'status' => Customer::STATUS_PROSPECT,
            'company_code' => 'PTP52026070000001',
            'sales_id' => $admin->id,
            'created_by' => $admin->id,
        ]);

        PurchaseOrder::create([
            'po_number' => 'PO-2026-0002',
            'customer_id' => 1,
            'sales_id' => $admin->id,
            'po_date' => now()->toDateString(),
            'status' => PurchaseOrder::STATUS_PENDING,
            'grand_total' => 2000000,
            'subtotal' => 2000000,
            'tax_amount' => 0,
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Rekomendasi Prioritas');
        $response->assertSee('Review customer approval');
    }
}
