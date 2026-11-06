<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RfqRevisionOrderingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $leader;
    protected User $sales;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        if (\App\Models\RolePermission::count() === 0) {
            $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        }

        $this->admin = User::factory()->create([
            'role'   => 'Admin',
            'name'   => 'Admin Purchase Order Test',
            'status' => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'role'   => 'Leader',
            'name'   => 'Leader Rian Order Test',
            'status' => 'Active',
        ]);

        $this->sales = User::factory()->create([
            'role'   => 'Sales',
            'name'   => 'Sales Order Test',
            'status' => 'Active',
        ]);

        $uniqueKey = uniqid();
        $this->customer = Customer::create([
            'company_name' => 'PT Pelita Solusi Order',
            'company_code' => 'CUST-' . $uniqueKey,
            'email'        => "order_{$uniqueKey}@pelita.com",
            'phone'        => '0812345678',
            'address'      => 'Kawasan Industri GIIC',
            'sales_id'     => $this->sales->id,
            'status'       => Customer::STATUS_ACTIVE,
        ]);
    }

    public function test_revised_rfq_and_approved_rfq_jump_to_page_one_first_row(): void
    {
        $contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name'        => 'PIC Test',
            'email'       => 'pic@pelita.com',
            'phone'       => '0812999999',
            'is_primary'  => true,
        ]);

        // RFQ 1: Dibuat 10 hari lalu (Quotation Sent)
        $rfq1 = Rfq::create([
            'rfq_number'          => 'RFQ-REV-100',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_code'       => $this->customer->company_code,
            'customer_contact_id' => $contact->id,
            'type'                => 'Hardware',
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'rfq_date'            => now()->subDays(10)->format('Y-m-d'),
            'status'              => Rfq::STATUS_QUOTATION_SENT,
        ]);
        DB::table('rfqs')->where('id', $rfq1->id)->update([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $item1 = RfqItem::create([
            'rfq_id'             => $rfq1->id,
            'product_name'       => 'Cisco Switch 24 Port',
            'qty'                => 10,
            'unit'               => 'Unit',
            'hpp'                => 10000000,
            'margin_type'        => 'percentage',
            'margin_value'       => 15,
            'price_after_margin' => 11500000,
        ]);

        // RFQ 2: Dibuat 3 hari lalu
        $rfq2 = Rfq::create([
            'rfq_number'          => 'RFQ-REV-200',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_code'       => $this->customer->company_code,
            'customer_contact_id' => $contact->id,
            'type'                => 'Hardware',
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'rfq_date'            => now()->subDays(3)->format('Y-m-d'),
            'status'              => Rfq::STATUS_PENDING_ADMIN,
        ]);
        DB::table('rfqs')->where('id', $rfq2->id)->update([
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        // 1. Cek urutan awal: RFQ 2 harus sebelum RFQ 1 karena updated_at RFQ 2 lebih baru (-3d vs -10d)
        $respInitial = $this->actingAs($this->admin)->get(route('rfq.index'));
        $respInitial->assertOk();
        $initialRfqs = $respInitial->viewData('rfqs');
        $this->assertEquals($rfq2->id, $initialRfqs->first()->id);

        // 2. Sekarang Sales mengajukan Revisi QTY pada RFQ 1
        $reviseResp = $this->actingAs($this->sales)->put(route('rfq.update_qty', $rfq1), [
            'items' => [
                [
                    'id'           => $item1->id,
                    'product_name' => 'Cisco Switch 24 Port (Nego Klien)',
                    'description'  => 'Spek tetap, qty disesuaikan budget',
                    'qty'          => 5,
                    'unit'         => 'Unit',
                ]
            ],
            'revision_notes' => 'Customer minta diskon dan turunkan qty jadi 5 unit',
        ]);
        $reviseResp->assertRedirect(route('rfq.show', $rfq1));

        $rfq1->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq1->status);
        $this->assertEquals('Customer minta diskon dan turunkan qty jadi 5 unit', $rfq1->revision_notes);

        // 3. Admin membuka /rfqs (Halaman 1) -> RFQ 1 sekarang HARUS di Baris Pertama (paling atas)!
        $respAfterRevise = $this->actingAs($this->admin)->get(route('rfq.index'));
        $respAfterRevise->assertOk();
        $rfqsAfterRevise = $respAfterRevise->viewData('rfqs');
        $this->assertEquals($rfq1->id, $rfqsAfterRevise->first()->id, 'RFQ 1 yang direvisi harus berada di urutan pertama di Halaman 1!');
        $respAfterRevise->assertSee('Revisi Qty');
        $respAfterRevise->assertSee('Customer minta diskon dan turunkan qty jadi 5 unit');

        // 4. Admin isi HPP dan Leader menyetujui (Approve)
        $rfq1->update(['status' => Rfq::STATUS_PENDING_LEADER]);
        $approveResp = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq1));
        $approveResp->assertRedirect();

        $rfq1->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq1->status);

        // 5. Sales membuka /rfqs (Halaman 1) -> RFQ 1 yang baru di-ACC Leader HARUS tetap di urutan pertama (paling atas)!
        $respAfterApprove = $this->actingAs($this->sales)->get(route('rfq.index'));
        $respAfterApprove->assertOk();
        $rfqsAfterApprove = $respAfterApprove->viewData('rfqs');
        $this->assertEquals($rfq1->id, $rfqsAfterApprove->first()->id, 'RFQ 1 yang di-approve harus berada di urutan pertama Halaman 1 untuk Sales!');
        $respAfterApprove->assertSee('Approved');
    }
}
