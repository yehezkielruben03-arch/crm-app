<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerNameCapitalizationTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name'     => 'Admin User',
            'username' => 'admin_user',
            'email'    => 'admin@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Super Admin',
            'status'   => 'Active',
            'phone'    => '081234567890',
        ]);

        $this->sales = User::create([
            'name'     => 'Ade Zulvida',
            'username' => 'sales_ade',
            'email'    => 'ade@pedia-technology.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'Sales',
            'status'   => 'Active',
            'phone'    => '081234567899',
        ]);
    }

    public function test_customer_company_name_and_pic_are_automatically_title_cased(): void
    {
        // 1. UPPERCASE input
        $customerUpper = Customer::create([
            'customer_type' => 'PT',
            'company_name'  => 'PRIMA JAYA KUSUMA',
            'company_code'  => 'PTI202610050000010',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
            'cp_name'       => 'BU KRISTIN',
        ]);

        $this->assertEquals('Prima Jaya Kusuma', $customerUpper->company_name);
        $this->assertEquals('Prima Jaya Kusuma, PT', $customerUpper->formal_company_name);
        $this->assertEquals('Bu Kristin', $customerUpper->resolved_pic_name);

        // 2. lowercase input
        $customerLower = Customer::create([
            'customer_type' => 'CV',
            'company_name'  => 'prima jaya kusuma',
            'company_code'  => 'PTI202610050000011',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
            'cp_name'       => 'budi santoso',
        ]);

        $this->assertEquals('Prima Jaya Kusuma', $customerLower->company_name);
        $this->assertEquals('Prima Jaya Kusuma, CV', $customerLower->formal_company_name);
        $this->assertEquals('Budi Santoso', $customerLower->resolved_pic_name);

        // 3. Mixed case input dengan Perorangan
        $customerMixed = Customer::create([
            'customer_type' => 'Perorangan',
            'company_name'  => 'pRiMa JaYa KuSuMa',
            'company_code'  => 'PTI202610050000012',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);

        $this->assertEquals('Prima Jaya Kusuma', $customerMixed->company_name);
        $this->assertEquals('Prima Jaya Kusuma, Perorangan', $customerMixed->formal_company_name);

        // 4. Spasi berlebih dengan Pemerintah
        $customerSpaces = Customer::create([
            'customer_type' => 'Pemerintah',
            'company_name'  => '   dinas kominfo jabar   ',
            'company_code'  => 'PTI202610050000013',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);

        $this->assertEquals('Dinas Kominfo Jabar', $customerSpaces->company_name);
        $this->assertEquals('Dinas Kominfo Jabar, Pemerintah', $customerSpaces->formal_company_name);
    }

    public function test_rfq_displays_title_cased_company_and_pic_in_quotation_preview(): void
    {
        $customer = Customer::create([
            'customer_type' => 'PT',
            'company_name'  => 'PT. PRIMA JAYA KUSUMA',
            'company_code'  => 'PTI202610050000014',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name'        => 'Up. BU KRISTIN',
            'position'    => 'Purchasing Manager',
            'phone'       => '081299887766',
            'is_primary'  => true,
        ]);

        $rfq = Rfq::create([
            'rfq_number'          => '261005-0014',
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_contact_id' => $contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_APPROVED,
            'type'                => 'Non Projek',
            'rfq_date'            => '2026-10-05',
        ]);

        $rfq->items()->create([
            'product_name'       => 'Perangkat Uji',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 100000,
            'margin'             => 25,
            'price_after_margin' => 125000,
        ]);

        $this->assertEquals('Prima Jaya Kusuma, PT', $rfq->resolved_company_name);
        $this->assertEquals('Bu Kristin', $rfq->resolved_pic_name);

        $response = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $response->assertStatus(200);
        $response->assertSee('Prima Jaya Kusuma, PT');
        $response->assertSee('Up. Bu Kristin');
    }

    public function test_customer_controller_store_and_update_enforces_title_case(): void
    {
        $payload = [
            'customer_type' => 'CV',
            'company_name'  => 'cv. karya sejahtera abadi',
            'cp_name'       => 'andi wijaya',
            'phone'         => '021888999',
            'address'       => 'Jl. Industri No 5',
        ];

        $response = $this->actingAs($this->admin)->post(route('customers.store'), $payload);
        $response->assertRedirect(route('customers.index'));

        $saved = Customer::where('company_name', 'Karya Sejahtera Abadi')->first();
        $this->assertNotNull($saved);
        $this->assertEquals('Karya Sejahtera Abadi', $saved->company_name);
        $this->assertEquals('Karya Sejahtera Abadi, CV', $saved->formal_company_name);
        $this->assertEquals('Andi Wijaya', $saved->cp_name);

        // Uji Update dengan UPPERCASE
        $updatePayload = [
            'customer_type' => 'CV',
            'company_name'  => 'KARYA SEJAHTERA ABADI MAKMUR',
            'cp_name'       => 'BAMBANG PAMUNGKAS',
        ];

        $updateResponse = $this->actingAs($this->admin)->put(route('customers.update', $saved), $updatePayload);
        $updateResponse->assertRedirect(route('customers.index'));

        $saved->refresh();
        $this->assertEquals('Karya Sejahtera Abadi Makmur', $saved->company_name);
        $this->assertEquals('Karya Sejahtera Abadi Makmur, CV', $saved->formal_company_name);
        $this->assertEquals('Bambang Pamungkas', $saved->cp_name);
    }
}
