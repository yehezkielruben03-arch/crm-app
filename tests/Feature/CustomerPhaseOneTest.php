<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerPhaseOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_create_without_sales_assignment_uses_placeholder_sales_owner(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin Test',
            'username' => 'admin_phase1',
            'email' => 'admin_phase1@test.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        $response = $this->actingAs($admin)->post(route('customers.store'), [
            'company_name' => 'PT Phase One Test',
            'status' => 'Prospect',
            'sales_id' => '',
            'customer_type' => 'Perusahaan',
            'industry' => 'Manufacturing',
            'address' => 'Jl. Test',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'country' => 'Indonesia',
        ]);

        $response->assertSessionHas('success');
        $customer = Customer::where('company_name', 'Phase One Test')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->sales_id);
        $this->assertEquals('Sales', User::find($customer->sales_id)->role);
    }
}
