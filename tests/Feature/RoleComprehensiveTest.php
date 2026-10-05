<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RoleComprehensiveTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private User $superAdmin;
    private User $admin;
    private User $leader;
    private User $sales;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->superAdmin = User::firstOrCreate(
            ['email' => 'superadmin_test@crm.com'],
            [
                'name' => 'Super Admin Test',
                'username' => 'superadmin_test',
                'password' => Hash::make('password123'),
                'role' => 'Super Admin',
                'status' => 'Active',
            ]
        );

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_test@crm.com'],
            [
                'name' => 'Admin Test',
                'username' => 'admin_test',
                'password' => Hash::make('password123'),
                'role' => 'Admin',
                'status' => 'Active',
            ]
        );

        $this->leader = User::firstOrCreate(
            ['email' => 'leader_test@crm.com'],
            [
                'name' => 'Leader Test',
                'username' => 'leader_test',
                'password' => Hash::make('password123'),
                'role' => 'Leader',
                'status' => 'Active',
            ]
        );

        $this->sales = User::firstOrCreate(
            ['email' => 'sales_test@crm.com'],
            [
                'name' => 'Sales Test',
                'username' => 'sales_test',
                'password' => Hash::make('password123'),
                'role' => 'Sales',
                'status' => 'Active',
            ]
        );
    }

    /**
     * 1. Test Dashboard across all 4 roles
     */
    public function test_dashboard_accessible_by_all_roles(): void
    {
        foreach ([$this->superAdmin, $this->admin, $this->leader, $this->sales] as $user) {
            $response = $this->actingAs($user)->get('/dashboard');
            $response->assertStatus(200);
        }
    }

    /**
     * 2. Test Customer Management across roles
     */
    public function test_customer_management_features(): void
    {
        // All roles can view customer list
        foreach ([$this->superAdmin, $this->admin, $this->leader, $this->sales] as $user) {
            $response = $this->actingAs($user)->get('/customers');
            $response->assertStatus(200);
        }

        // Sales can create customer prospect
        $response = $this->actingAs($this->sales)->get('/customers/create');
        $response->assertStatus(200);

        $uniqueName = 'Audit Corp ' . time();
        $customerData = [
            'company_name' => $uniqueName,
            'industry' => 'Automotive',
            'city' => 'Bekasi',
            'cp_name' => 'Bapak Budi',
            'cp_phone' => '08123456789',
            'status' => 'Prospect',
        ];

        $postRes = $this->actingAs($this->sales)->post('/customers', $customerData);
        $postRes->assertRedirect();

        $customer = Customer::where('company_name', $uniqueName)->first();
        $this->assertNotNull($customer);

        // All roles can view customer detail
        foreach ([$this->superAdmin, $this->admin, $this->leader, $this->sales] as $user) {
            $response = $this->actingAs($user)->get('/customers/' . $customer->id);
            $response->assertStatus(200);
        }

        // Leader / Super Admin can approve customer
        $approveRes = $this->actingAs($this->leader)->post('/customers/' . $customer->id . '/approve');
        $this->assertTrue(in_array($approveRes->status(), [200, 302]));

        // Export customers
        $exportRes = $this->actingAs($this->admin)->get('/customers/export');
        $this->assertTrue(in_array($exportRes->getStatusCode(), [200, 302]));
    }

    /**
     * 3. Test Full RFQ Lifecycle across roles
     */
    public function test_rfq_lifecycle_across_roles(): void
    {
        $customer = Customer::create([
            'company_name' => 'Demo Pelanggan ' . time(),
            'company_code' => 'PTI' . date('Ymd') . rand(1000000, 9999999),
            'status' => 'Active',
            'sales_id' => $this->sales->id,
            'created_by' => $this->sales->id,
        ]);

        $contact = $customer->contacts()->create([
            'name' => 'Pak Kontak',
            'phone' => '0812345678',
            'is_primary' => true,
        ]);

        // A. Sales creates RFQ
        $rfqData = [
            'customer_id' => $customer->id,
            'customer_contact_id' => $contact->id,
            'type' => 'Non Projek',
            'need_date' => '2026-10-15',
            'items' => [
                [
                    'product_name' => 'Baut M10 Custom Steel',
                    'qty' => 100,
                    'unit' => 'Pcs',
                    'description' => 'Toleransi 0.05mm',
                ]
            ]
        ];

        $createRes = $this->actingAs($this->sales)->post('/rfqs', $rfqData);
        $createRes->assertRedirect();

        $rfq = Rfq::latest('id')->first();
        $this->assertNotNull($rfq);

        // B. All roles can view RFQ Detail
        foreach ([$this->superAdmin, $this->admin, $this->leader, $this->sales] as $user) {
            $detailRes = $this->actingAs($user)->get('/rfqs/' . $rfq->id);
            $detailRes->assertStatus(200);
        }

        // C. Admin can view & submit Pricing
        $priceFormRes = $this->actingAs($this->admin)->get('/rfqs/' . $rfq->id . '/price');
        $this->assertTrue(in_array($priceFormRes->status(), [200, 302]));

        $item = $rfq->items->first();
        if ($item) {
            $submitPriceRes = $this->actingAs($this->admin)->post('/rfqs/' . $rfq->id . '/price', [
                'action_type' => 'submit',
                'items' => [
                    $item->id => [
                        'product_name'     => 'Baut M10 Custom Steel',
                        'qty'              => 100,
                        'unit'             => 'Pcs',
                        'hpp'              => 50000,
                        'ongkir_pedia'     => 5000,
                        'biaya_kirim'      => 3000,
                        'margin_type'      => 'percentage',
                        'margin_value'     => 15,
                        'custom_ceiling'   => 1000,
                        'validity_days'    => 7,
                    ]
                ]
            ]);
            $this->assertTrue(in_array($submitPriceRes->status(), [200, 302]));

            // Leader approves the RFQ
            $approveRes = $this->actingAs($this->leader)->post('/rfqs/' . $rfq->id . '/approve');
            $this->assertTrue(in_array($approveRes->status(), [200, 302]));
        }

        // D. Preview & Download Quotation (Available after approval)
        $rfq->refresh();
        $previewRes = $this->actingAs($this->sales)->get('/rfqs/' . $rfq->id . '/preview-quotation');
        $this->assertTrue(in_array($previewRes->status(), [200, 302]));

        $downloadRes = $this->actingAs($this->sales)->get('/rfqs/' . $rfq->id . '/download-quotation');
        $this->assertTrue(in_array($downloadRes->getStatusCode(), [200, 302]));
    }

    /**
     * 4. Test Purchase Orders across roles with permission checks
     */
    public function test_purchase_order_features(): void
    {
        // All roles can view PO list
        foreach ([$this->superAdmin, $this->admin, $this->leader, $this->sales] as $user) {
            $response = $this->actingAs($user)->get('/purchase-orders');
            $response->assertStatus(200);
        }

        // Admin & Leader with CRUD permission can access create PO
        $adminRes = $this->actingAs($this->admin)->get('/purchase-orders/create');
        $adminRes->assertStatus(200);

        // Sales without direct CRUD permission cannot create master PO directly (403 Forbidden)
        $salesRes = $this->actingAs($this->sales)->get('/purchase-orders/create');
        $salesRes->assertStatus(403);
    }

    /**
     * 5. Test Analytics Access Control
     */
    public function test_analytics_access_control(): void
    {
        // Admin, Super Admin, Leader can access Analytics
        foreach ([$this->superAdmin, $this->admin, $this->leader] as $user) {
            $res = $this->actingAs($user)->get('/analytics');
            $this->assertTrue(in_array($res->status(), [200, 302]));
        }
    }

    /**
     * 6. Test Approvals Center Access
     */
    public function test_approval_center_access(): void
    {
        // Leader & Super Admin can access Approvals Center
        $leaderRes = $this->actingAs($this->leader)->get('/approvals');
        $this->assertTrue(in_array($leaderRes->status(), [200, 302]));

        $superAdminRes = $this->actingAs($this->superAdmin)->get('/approvals');
        $this->assertTrue(in_array($superAdminRes->status(), [200, 302]));
    }

    /**
     * 7. Test Vendor Management (Admin Purchase & Super Admin)
     */
    public function test_vendor_management(): void
    {
        $adminRes = $this->actingAs($this->admin)->get('/vendors');
        $adminRes->assertStatus(200);

        $superRes = $this->actingAs($this->superAdmin)->get('/vendors');
        $superRes->assertStatus(200);
    }

    /**
     * 8. Test User Management (Super Admin & Admin Only)
     */
    public function test_user_management(): void
    {
        $superRes = $this->actingAs($this->superAdmin)->get('/users');
        $superRes->assertStatus(200);

        $adminRes = $this->actingAs($this->admin)->get('/users');
        $adminRes->assertStatus(200);
    }

    /**
     * 9. Test Notification & Manual Book
     */
    public function test_notifications_and_manual_book(): void
    {
        foreach ([$this->superAdmin, $this->sales] as $user) {
            $notifRes = $this->actingAs($user)->get('/notifications');
            $notifRes->assertStatus(200);

            $pollRes = $this->actingAs($user)->get('/api/notifications/poll');
            $pollRes->assertStatus(200);

            $manualRes = $this->actingAs($user)->get('/manual-book');
            $manualRes->assertStatus(200);
        }
    }
}
