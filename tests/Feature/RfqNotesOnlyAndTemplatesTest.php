<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RfqNotesOnlyAndTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $admin;
    private User $leader;
    private Customer $customer;
    private CustomerContact $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name'     => 'Admin Purchase',
            'username' => 'admin_purchase',
            'email'    => 'admin@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Admin Purchase',
            'status'   => 'Active',
            'phone'    => '081234567890',
        ]);

        $this->leader = User::create([
            'name'     => 'Leader User',
            'username' => 'leader_user',
            'email'    => 'leader@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Leader',
            'status'   => 'Active',
            'phone'    => '081234567891',
        ]);

        $this->sales = User::create([
            'name'     => 'Sales Ade',
            'username' => 'sales_ade',
            'email'    => 'ade@pedia-technology.co.id',
            'password' => Hash::make('password123'),
            'role'     => 'Sales',
            'status'   => 'Active',
            'phone'    => '081234567892',
        ]);

        $this->customer = Customer::create([
            'customer_type' => 'PT',
            'company_name'  => 'Prima Jaya Kusuma',
            'company_code'  => 'PTI202610050000099',
            'status'        => Customer::STATUS_ACTIVE,
            'sales_id'      => $this->sales->id,
            'created_by'    => $this->admin->id,
            'cp_name'       => 'Ibu Kristin',
            'ongkir_pedia'  => 50000,
        ]);

        $this->contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name'        => 'Ibu Kristin',
            'position'    => 'Purchasing Manager',
            'phone'       => '081299887766',
            'is_primary'  => true,
        ]);
    }

    public function test_sales_can_create_rfq_with_only_notes_and_zero_items(): void
    {
        $response = $this->actingAs($this->sales)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'type'                => 'Projek',
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => 'Tolong carikan CCTV Hikvision 8 Channel plus instalasi dan kabel utp.',
            'items'               => [],
        ]);

        $this->assertDatabaseHas('rfqs', [
            'customer_id' => $this->customer->id,
            'type'        => 'Projek',
            'status'      => Rfq::STATUS_PENDING_ADMIN,
            'notes'       => 'Tolong carikan CCTV Hikvision 8 Channel plus instalasi dan kabel utp.',
        ]);

        $rfq = Rfq::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($rfq);
        $this->assertCount(0, $rfq->items);
        $response->assertRedirect(route('rfq.show', $rfq));
    }

    public function test_creating_rfq_fails_if_both_items_and_notes_are_missing(): void
    {
        $response = $this->actingAs($this->sales)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'type'                => 'Projek',
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => '',
            'items'               => [],
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertDatabaseMissing('rfqs', [
            'customer_id' => $this->customer->id,
        ]);
    }

    public function test_admin_sees_sales_notes_and_all_templates_on_price_form(): void
    {
        $rfq = Rfq::create([
            'rfq_number'          => 'RFQ-PRJ-202610-001',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'type'                => 'Projek',
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'rfq_date'            => now(),
            'notes'               => 'Catatan Khusus: Pengadaan 5 unit Access Point dan jasa setting mikrotik.',
            'created_by'          => $this->sales->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('rfq.price_form', $rfq));
        $response->assertStatus(200);

        // Verify Sales Notes banner is displayed
        $response->assertSee('Catatan dari Sales');
        $response->assertSee('Item belum dirinci oleh Sales');
        $response->assertSee('Pengadaan 5 unit Access Point dan jasa setting mikrotik.');

        // Verify all 3 Project category template blocks exist
        $response->assertSee('Blok Hardware');
        $response->assertSee('Blok Jasa Pemasangan');
        $response->assertSee('Blok Material Support');
        $response->assertSee('+ Tambah Item');
    }

    public function test_admin_can_add_items_and_submit_price_from_notes_only_rfq(): void
    {
        $rfq = Rfq::create([
            'rfq_number'          => 'RFQ-PRJ-202610-002',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'type'                => 'Projek',
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'rfq_date'            => now(),
            'notes'               => 'Catatan pengadaan router dan switch.',
            'created_by'          => $this->sales->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Catatan penawaran garansi 1 tahun.',
            'items' => [
                'new_123456_1' => [
                    'category'       => 'Hardware',
                    'product_name'   => 'Router Mikrotik CCR1009',
                    'qty'            => 2,
                    'unit'           => 'Unit',
                    'hpp'            => 6000000,
                    'ongkir_pedia'   => 50000,
                    'biaya_kirim'    => 25000,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 15,
                    'custom_ceiling' => 50000,
                ],
                'new_123456_2' => [
                    'category'       => 'Jasa Pemasangan',
                    'product_name'   => 'Jasa Konfigurasi Jaringan',
                    'qty'            => 1,
                    'unit'           => 'Lot',
                    'hpp'            => 1500000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 50000,
                ],
            ],
        ]);

        $response->assertRedirect(route('rfq.show', $rfq));

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
        $this->assertEquals('Catatan penawaran garansi 1 tahun.', $rfq->notes);
        $this->assertCount(2, $rfq->items);

        $routerItem = $rfq->items->where('product_name', 'Router Mikrotik CCR1009')->first();
        $this->assertNotNull($routerItem);
        $this->assertEquals('Hardware', $routerItem->category);
        $this->assertEquals(2, $routerItem->qty);
        $this->assertGreaterThan(6000000, $routerItem->price_after_margin);

        $jasaItem = $rfq->items->where('product_name', 'Jasa Konfigurasi Jaringan')->first();
        $this->assertNotNull($jasaItem);
        $this->assertEquals('Jasa Pemasangan', $jasaItem->category);
        $this->assertEquals(1, $jasaItem->qty);
        $this->assertGreaterThan(1500000, $jasaItem->price_after_margin);
    }

    public function test_leader_can_approve_rfq_and_preview_quotation(): void
    {
        $rfq = Rfq::create([
            'rfq_number'          => 'RFQ-NON-202610-003',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'type'                => 'Non Projek',
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'rfq_date'            => now(),
            'notes'               => 'Permintaan laptop kerja standar.',
            'created_by'          => $this->sales->id,
        ]);

        // Admin inputs price
        $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Unit ready stock garansi resmi.',
            'items' => [
                'new_789' => [
                    'product_name'   => 'Laptop ThinkPad L14',
                    'qty'            => 3,
                    'unit'           => 'Unit',
                    'hpp'            => 10000000,
                    'ongkir_pedia'   => 50000,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 12.5,
                    'custom_ceiling' => 50000,
                ],
            ],
        ]);

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);

        // Leader approves
        $approveResponse = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $approveResponse->assertRedirect();

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        // Leader previews quotation
        $previewResponse = $this->actingAs($this->leader)->get(route('rfq.preview_quotation', $rfq));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('Laptop ThinkPad L14');
        $previewResponse->assertSee('Unit ready stock garansi resmi.');
    }

    public function test_leader_can_see_create_buttons_and_create_rfq(): void
    {
        // 1. Leader visits rfq index and sees both create buttons
        $indexResponse = $this->actingAs($this->leader)->get(route('rfq.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Buat RFQ Projek');
        $indexResponse->assertSee('Buat RFQ Baru');

        // 2. Leader accesses create project and create forms
        $projectFormResponse = $this->actingAs($this->leader)->get(route('rfq.create_project'));
        $projectFormResponse->assertRedirect(route('rfq.create', ['type' => 'Projek']));

        $createFormResponse = $this->actingAs($this->leader)->get(route('rfq.create'));
        $createFormResponse->assertStatus(200);

        // 3. Leader submits a new RFQ successfully
        $createResponse = $this->actingAs($this->leader)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => 'Dibuat langsung oleh Leader Laras.',
            'items'               => [
                [
                    'product_name' => 'Server Dell PowerEdge',
                    'qty'          => 1,
                    'unit'         => 'Unit',
                ],
            ],
        ]);

        $this->assertDatabaseHas('rfqs', [
            'customer_id' => $this->customer->id,
            'type'        => 'Non Projek',
            'notes'       => 'Dibuat langsung oleh Leader Laras.',
        ]);

        $createdRfq = Rfq::where('notes', 'Dibuat langsung oleh Leader Laras.')->first();
        $this->assertNotNull($createdRfq);
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $createdRfq->status);
        $createResponse->assertRedirect(route('rfq.show', $createdRfq));
    }

    public function test_rfq_projek_creation_sets_pending_admin_and_centralizes_hpp_to_price_form(): void
    {
        // 1. Verify create form view does not contain HPP band inputs
        $adminCreateView = $this->actingAs($this->admin)->get(route('rfq.create', ['type' => 'Projek']));
        $adminCreateView->assertStatus(200);
        $adminCreateView->assertDontSee('pmx-cell--band');
        $adminCreateView->assertDontSee('HPP (Rp)');

        // 2. Admin creates RFQ Projek -> status is Pending Admin
        $this->actingAs($this->admin)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'type'                => 'Projek',
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => 'RFQ Projek oleh Admin Purchase',
            'items'               => [
                [
                    'category'     => 'Hardware',
                    'product_name' => 'Switch 24 Port Gigabit',
                    'qty'          => 2,
                    'unit'         => 'Unit',
                ],
            ],
        ]);

        $adminRfq = Rfq::where('notes', 'RFQ Projek oleh Admin Purchase')->first();
        $this->assertNotNull($adminRfq);
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $adminRfq->status);

        // 3. Edit view does not contain HPP band inputs
        $editView = $this->actingAs($this->admin)->get(route('rfq.edit', $adminRfq));
        $editView->assertStatus(200);
        $editView->assertDontSee('pmx-cell--band');
        $editView->assertDontSee('HPP (Rp)');

        // 4. Leader creates RFQ Projek -> status is Pending Admin
        $this->actingAs($this->leader)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'type'                => 'Projek',
            'rfq_date'            => now()->format('Y-m-d'),
            'notes'               => 'RFQ Projek oleh Leader',
            'items'               => [
                [
                    'category'     => 'Hardware',
                    'product_name' => 'Router Core MikroTik',
                    'qty'          => 1,
                    'unit'         => 'Unit',
                ],
            ],
        ]);

        $leaderRfq = Rfq::where('notes', 'RFQ Projek oleh Leader')->first();
        $this->assertNotNull($leaderRfq);
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $leaderRfq->status);

        // 5. Pricing is done via price_form and transitions to Pending Leader upon submit
        $priceFormResp = $this->actingAs($this->admin)->get(route('rfq.price_form', $adminRfq));
        $priceFormResp->assertStatus(200);

        $item = $adminRfq->items->first();
        $submitPriceResp = $this->actingAs($this->admin)->post(route('rfq.submit_price', $adminRfq), [
            'notes' => 'Catatan revisi kalkulasi HPP',
            'items' => [
                $item->id => [
                    'category'       => 'Hardware',
                    'product_name'   => 'Switch 24 Port Gigabit',
                    'qty'            => 2,
                    'unit'           => 'Unit',
                    'hpp'            => 1500000,
                    'ongkir_pedia'   => 50000,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 25,
                    'custom_ceiling' => 10000,
                ],
            ],
        ]);

        $adminRfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $adminRfq->status);
    }
}
