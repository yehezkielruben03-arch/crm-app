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

class CustomerQuotationAddressTest extends TestCase
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

    public function test_customer_full_address_with_all_fields_matches_sample(): void
    {
        $customer = Customer::create([
            'customer_type' => 'PT',
            'company_name'  => 'PT. Yoshino Indonesia',
            'company_code'  => 'PTI202610030000001',
            'address'       => 'Kawasan GIIC Blok CF No.01 Deltamas',
            'village'       => 'Pasirranji',
            'district'      => 'Central Cikarang',
            'city'          => 'Bekasi Regency',
            'province'      => 'West Java',
            'postal_code'   => '17530',
            'phone'         => '(021) 22156672',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);

        $this->assertEquals(
            "Kawasan GIIC Blok CF No.01 Deltamas, Pasirranji,\nCentral Cikarang, Bekasi Regency, West Java 17530",
            $customer->full_address
        );

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name'        => 'Bu Kristin',
            'position'    => 'Purchasing Manager',
            'phone'       => '081299887766',
            'is_primary'  => true,
        ]);

        $rfq = Rfq::create([
            'rfq_number'          => '240327-0288',
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_contact_id' => $contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_APPROVED,
            'type'                => 'Non Projek',
            'rfq_date'            => '2024-03-27',
        ]);

        $rfq->items()->create([
            'product_name'       => 'Access Door Standalone',
            'qty'                => 5,
            'unit'               => 'Unit',
            'hpp'                => 500000,
            'margin'             => 30,
            'price_after_margin' => 650000,
        ]);

        $this->assertEquals(
            "Kawasan GIIC Blok CF No.01 Deltamas, Pasirranji,\nCentral Cikarang, Bekasi Regency, West Java 17530",
            $rfq->resolved_customer_address
        );
        $this->assertEquals('(021) 22156672', $rfq->resolved_customer_phone);
        $this->assertEquals('Bu Kristin', $rfq->resolved_pic_name);
        $this->assertEquals('Yoshino Indonesia, PT', $rfq->resolved_company_name);

        $response = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $response->assertStatus(200);
        $response->assertSee('Yoshino Indonesia, PT');
        $response->assertSee("Kawasan GIIC Blok CF No.01 Deltamas, Pasirranji,\nCentral Cikarang, Bekasi Regency, West Java 17530");
        $response->assertSee('Telp : (021) 22156672');
        $response->assertSee('Up. Bu Kristin');
    }

    public function test_customer_address_with_missing_components_still_renders_cleanly(): void
    {
        $customer = Customer::create([
            'customer_type' => 'CV',
            'company_name'  => 'Sinar Jaya Abadi',
            'company_code'  => 'PTI202610030000002',
            'address'       => 'Kawasan Industri MM2100',
            'village'       => null,
            'district'      => null,
            'city'          => 'Cikarang Barat',
            'province'      => 'Jawa Barat',
            'postal_code'   => '17520',
            'phone'         => '021-89901234',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);

        $this->assertEquals('Sinar Jaya Abadi, CV', $customer->formal_company_name);
        $this->assertEquals(
            "Kawasan Industri MM2100,\nCikarang Barat, Jawa Barat 17520",
            $customer->full_address
        );

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name'        => 'Up. Bpk. Budi Santoso',
            'position'    => 'Procurement',
            'phone'       => '081122334455',
            'is_primary'  => true,
        ]);

        $rfq = Rfq::create([
            'rfq_number'          => '240327-0289',
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_contact_id' => $contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_APPROVED,
            'type'                => 'Non Projek',
            'rfq_date'            => '2024-03-27',
        ]);

        $this->assertEquals('Bpk. Budi Santoso', $rfq->resolved_pic_name);
        $this->assertEquals('Sinar Jaya Abadi, CV', $rfq->resolved_company_name);

        $response = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $response->assertStatus(200);
        $response->assertSee('Sinar Jaya Abadi, CV');
        $response->assertSee("Kawasan Industri MM2100,\nCikarang Barat, Jawa Barat 17520");
        $response->assertSee('Telp : 021-89901234');
        $response->assertSee('Up. Bpk. Budi Santoso');
    }

    public function test_all_customer_types_follow_uniform_name_suffix_format(): void
    {
        $pt = Customer::create([
            'customer_type' => 'PT',
            'company_name'  => 'PT. Yoshino Indonesia', // accidentally typed PT
            'company_code'  => 'PTI202610030000003',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);
        $this->assertEquals('Yoshino Indonesia, PT', $pt->formal_company_name);

        $cv = Customer::create([
            'customer_type' => 'CV',
            'company_name'  => 'Karya Bangsa, CV', // accidentally typed suffix
            'company_code'  => 'PTI202610030000004',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);
        $this->assertEquals('Karya Bangsa, CV', $cv->formal_company_name);

        $perorangan = Customer::create([
            'customer_type' => 'Perorangan',
            'company_name'  => 'Budi Santoso',
            'company_code'  => 'PTI202610030000005',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);
        $this->assertEquals('Budi Santoso, Perorangan', $perorangan->formal_company_name);

        $pemerintah = Customer::create([
            'customer_type' => 'Pemerintah',
            'company_name'  => 'Dinas Kominfo',
            'company_code'  => 'PTI202610030000006',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
        ]);
        $this->assertEquals('Dinas Kominfo, Pemerintah', $pemerintah->formal_company_name);
    }
}
