<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApprovalPhaseTwoTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_center_lists_prospect_customers_for_review(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin Approval',
            'username' => 'admin_approval',
            'email' => 'admin_approval@test.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $sales = User::factory()->create([
            'name' => 'Sales Approval',
            'username' => 'sales_approval',
            'email' => 'sales_approval@test.com',
            'password' => Hash::make('password'),
            'role' => 'Sales Marketing',
            'status' => 'Active',
        ]);

        Customer::create([
            'company_name' => 'PT Approval Test',
            'status' => Customer::STATUS_PROSPECT,
            'sales_id' => $sales->id,
            'created_by' => $sales->id,
            'company_code' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('approvals.index'));

        $response->assertOk();
        $response->assertSee('PT Approval Test');
    }
}
