<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorDirectInputTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $rfq;
    protected $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'role' => 'Admin',
            'name' => 'Admin Purchase Test',
            'email' => 'admin_purchase_test@example.com',
            'status' => 'Active',
        ]);

        $sales = User::factory()->create([
            'role' => 'Sales',
            'name' => 'Sales Test',
        ]);

        $customer = Customer::create([
            'company_name' => 'PT Test Customer',
            'company_code' => 'CUST-001',
            'email' => 'cust@example.com',
            'phone' => '0812345678',
            'address' => 'Jakarta',
            'sales_id' => $sales->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name' => 'Pak Contact',
            'email' => 'contact@example.com',
            'phone' => '0812999999',
            'is_primary' => true,
        ]);

        $this->rfq = Rfq::create([
            'rfq_number' => 'RFQ-TEST-001',
            'customer_id' => $customer->id,
            'customer_name' => $customer->company_name,
            'customer_code' => $customer->company_code,
            'customer_contact_id' => $contact->id,
            'type' => 'Projek',
            'sales_id' => $sales->id,
            'sales_name' => $sales->name,
            'rfq_date' => now()->format('Y-m-d'),
            'status' => Rfq::STATUS_PENDING_ADMIN,
        ]);

        $this->item = RfqItem::create([
            'rfq_id' => $this->rfq->id,
            'category' => 'Hardware',
            'product_name' => 'Router Cisco C9200',
            'qty' => 1,
            'unit' => 'Unit',
            'hpp' => 1000000,
            'ongkir_pedia' => 0,
            'ongkir_pelanggan' => 0,
            'margin' => 20,
            'margin_value' => 20,
            'ceiling' => 1000,
            'validity_days' => 7,
            'price_after_margin' => 1200000,
        ]);
    }

    public function test_typed_new_vendor_name_automatically_creates_and_links_vendor()
    {
        $response = $this->actingAs($this->admin)->post(route('rfq.submit_price', $this->rfq), [
            'notes' => 'Catatan test',
            'items' => [
                $this->item->id => [
                    'product_name' => $this->item->product_name,
                    'qty' => 1,
                    'unit' => 'Unit',
                    'vendor_name' => 'Toko Komputer Mangga Dua',
                    'description' => 'Garansi distributor 1 tahun',
                    'hpp' => 1500000,
                    'ongkir_pedia' => 0,
                    'biaya_kirim' => 0,
                    'margin_type' => 'percentage',
                    'margin_value' => 25,
                    'custom_ceiling' => 1000,
                    'validity_days' => 7,
                ],
            ],
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vendors', [
            'nama_vendor' => 'Toko Komputer Mangga Dua',
        ]);

        $vendor = Vendor::where('nama_vendor', 'Toko Komputer Mangga Dua')->first();
        $this->assertNotNull($vendor);

        $this->item->refresh();
        $this->assertEquals($vendor->id, $this->item->vendor_id);
    }

    public function test_typed_existing_vendor_name_links_without_duplicate()
    {
        $existingVendor = Vendor::create([
            'nama_vendor' => 'PT Synnex Metrodata',
            'kategori' => 'Hardware & IT',
            'status' => Vendor::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('rfq.submit_price', $this->rfq), [
            'items' => [
                $this->item->id => [
                    'product_name' => $this->item->product_name,
                    'qty' => 1,
                    'unit' => 'Unit',
                    'vendor_name' => 'PT Synnex Metrodata',
                    'description' => 'Spek switch',
                    'hpp' => 2000000,
                    'ongkir_pedia' => 0,
                    'biaya_kirim' => 0,
                    'margin_type' => 'percentage',
                    'margin_value' => 20,
                    'custom_ceiling' => 1000,
                    'validity_days' => 7,
                ],
            ],
        ]);

        $response->assertSessionHas('success');

        $this->assertEquals(1, Vendor::where('nama_vendor', 'PT Synnex Metrodata')->count());

        $this->item->refresh();
        $this->assertEquals($existingVendor->id, $this->item->vendor_id);
    }
}
