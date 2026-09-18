<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Hash;

class SmokeTest extends TestCase
{
    /**
     * Seed minimum data needed for this test independently.
     * Tests use the same SQLite DB (not RefreshDatabase) so we
     * just need users to already exist – which our real seeder handles.
     * If they don't exist (e.g. fresh test run), we create them here.
     */
    private function ensureUsersExist(): array
    {
        $admin = User::where('role', 'Admin')->first()
            ?? User::create([
                'name'     => 'Admin CRM',
                'username' => 'admin_test',
                'email'    => 'admin_test@crm.com',
                'password' => Hash::make('password'),
                'role'     => 'Admin',
                'status'   => 'Active',
            ]);

        $sales = User::where('role', 'Sales')->first()
            ?? User::create([
                'name'     => 'Sales Test',
                'username' => 'sales_test',
                'email'    => 'sales_test@crm.com',
                'password' => Hash::make('password'),
                'role'     => 'Sales',
                'status'   => 'Active',
            ]);

        return [$admin, $sales];
    }

    public function test_all_pages_load_for_admin_and_sales(): void
    {
        [$admin, $sales] = $this->ensureUsersExist();

        $this->assertNotNull($admin, "Admin user should exist");
        $this->assertNotNull($sales, "Sales user should exist");

        $customer = Customer::first();
        $po = PurchaseOrder::first();

        $routes = [
            '/dashboard',
            '/notifications',
            '/customers',
            '/customers/create',
            '/purchase-orders',
            '/purchase-orders/create',
        ];

        if ($customer) {
            $routes[] = '/customers/' . $customer->id . '/edit';
        }

        if ($po) {
            $routes[] = '/purchase-orders/' . $po->id;
        }

        // Test Admin
        foreach ($routes as $route) {
            $response = $this->actingAs($admin)->get($route);
            if ($response->status() === 500 && $response->exception) {
                echo "\nError on $route (Admin): " . $response->exception->getMessage() . "\n";
            }
            $this->assertNotEquals(500, $response->status(), "Route {$route} failed for Admin with status: " . $response->status());
        }

        // Test Sales
        foreach ($routes as $route) {
            $response = $this->actingAs($sales)->get($route);
            if ($response->status() === 500 && $response->exception) {
                echo "\nError on $route (Sales): " . $response->exception->getMessage() . "\n";
            }
            $this->assertNotEquals(500, $response->status(), "Route {$route} failed for Sales with status: " . $response->status());
        }
    }
}
