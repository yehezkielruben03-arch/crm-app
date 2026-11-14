<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRbacAndIntegrityTighteningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_leader_cannot_access_user_management_routes(): void
    {
        $leader = User::factory()->create(['role' => 'Leader']);
        $admin = User::factory()->create(['role' => 'Admin']);

        // Leader mencoba akses halaman users -> 403 Forbidden
        $response = $this->actingAs($leader)->get(route('users.index'));
        $response->assertStatus(403);

        // Leader mencoba create user -> 403 Forbidden
        $createResponse = $this->actingAs($leader)->post(route('users.store'), [
            'name' => 'Hacker User',
            'email' => 'hacker@example.com',
            'username' => 'hacker',
            'password' => 'secret12345',
            'role' => 'Admin',
            'status' => 'Active',
        ]);
        $createResponse->assertStatus(403);

        // Admin diizinkan akses halaman users -> 200 OK
        $adminResponse = $this->actingAs($admin)->get(route('users.index'));
        $adminResponse->assertStatus(200);
    }

    public function test_sales_cannot_share_rfq_belonging_to_another_sales(): void
    {
        $salesA = User::factory()->create(['role' => 'Sales Marketing', 'name' => 'Sales A']);
        $salesB = User::factory()->create(['role' => 'Sales Marketing', 'name' => 'Sales B']);

        $customer = Customer::create([
            'company_name' => 'PT Test Customer',
            'customer_type' => 'End User',
            'status' => 'Active',
            'sales_id' => $salesB->id,
            'address' => 'Jl. Test No. 1',
            'city' => 'Jakarta',
        ]);
        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name' => 'PIC Test',
        ]);

        $rfqOfB = Rfq::create([
            'rfq_number' => 'RFQ-TEST-SHARE-001',
            'customer_id' => $customer->id,
            'customer_contact_id' => $contact->id,
            'customer_name' => $customer->company_name,
            'rfq_date' => now()->toDateString(),
            'type' => 'Non Projek',
            'sales_id' => $salesB->id,
            'sales_name' => $salesB->name,
            'status' => Rfq::STATUS_QUOTATION_SENT,
        ]);

        // Sales A mencoba share RFQ milik Sales B -> 403 Forbidden
        $shareAttempt = $this->actingAs($salesA)->post(route('chat.share_rfq', $rfqOfB), [
            'notes' => 'Mencoba intip data B',
        ]);
        $shareAttempt->assertStatus(403);

        // Sales B membagikan RFQ miliknya sendiri -> 302 Redirect sukses
        $validShare = $this->actingAs($salesB)->post(route('chat.share_rfq', $rfqOfB), [
            'notes' => 'Ini penawaran saya',
        ]);
        $validShare->assertStatus(302);
        $validShare->assertSessionHas('success');
    }

    public function test_cannot_create_rfq_with_contact_belonging_to_different_customer(): void
    {
        $sales = User::factory()->create(['role' => 'Sales Marketing']);

        $customerA = Customer::create([
            'company_name' => 'PT Pelanggan A',
            'customer_type' => 'End User',
            'status' => 'Active',
            'sales_id' => $sales->id,
            'address' => 'Jl. Pelanggan A',
            'city' => 'Surabaya',
        ]);
        $contactA = CustomerContact::create([
            'customer_id' => $customerA->id,
            'name' => 'PIC Pelanggan A',
        ]);

        $customerB = Customer::create([
            'company_name' => 'PT Pelanggan B',
            'customer_type' => 'End User',
            'status' => 'Active',
            'sales_id' => $sales->id,
            'address' => 'Jl. Pelanggan B',
            'city' => 'Bandung',
        ]);
        $contactB = CustomerContact::create([
            'customer_id' => $customerB->id,
            'name' => 'PIC Pelanggan B',
        ]);

        // Kirim customer_id = A tapi customer_contact_id = contactB milik customer B
        $invalidPayload = [
            'customer_id' => $customerA->id,
            'customer_contact_id' => $contactB->id, // Mismatch!
            'type' => 'Non Projek',
            'need_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Catatan uji coba',
            'items' => [
                [
                    'product_name' => 'Barang Uji',
                    'qty' => 1,
                ]
            ],
        ];

        $response = $this->actingAs($sales)->post(route('rfq.store'), $invalidPayload);
        $response->assertSessionHasErrors(['customer_contact_id']);

        // Kirim dengan contactA yang valid milik customer A -> Berhasil
        $validPayload = [
            'customer_id' => $customerA->id,
            'customer_contact_id' => $contactA->id, // Valid!
            'type' => 'Non Projek',
            'need_date' => now()->addDays(5)->toDateString(),
            'notes' => 'Catatan uji coba',
            'items' => [
                [
                    'product_name' => 'Barang Uji',
                    'qty' => 1,
                ]
            ],
        ];

        $validResponse = $this->actingAs($sales)->post(route('rfq.store'), $validPayload);
        $validResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rfqs', [
            'customer_id' => $customerA->id,
            'customer_contact_id' => $contactA->id,
        ]);
    }
}
