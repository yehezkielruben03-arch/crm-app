<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\PurchaseOrder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class E2eRfqFlowTest extends TestCase
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

        Storage::fake('public');

        $this->sales = User::factory()->create([
            'name'     => 'Sales Marketing Test',
            'username' => 'sales_mkt_e2e',
            'email'    => 'sales_mkt_e2e@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Sales Marketing',
            'status'   => 'Active',
        ]);

        $this->admin = User::factory()->create([
            'name'     => 'Admin Purchase Test',
            'username' => 'admin_pur_e2e',
            'email'    => 'admin_pur_e2e@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Admin',
            'status'   => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'name'     => 'Leader Test',
            'username' => 'leader_e2e',
            'email'    => 'leader_e2e@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Leader',
            'status'   => 'Active',
        ]);

        $this->customer = Customer::factory()->create([
            'company_name' => 'PT E2E Test',
            'company_code' => null,
            'sales_id'     => $this->sales->id,
            'status'       => 'Active',
        ]);

        $this->contact = CustomerContact::factory()->create([
            'customer_id' => $this->customer->id,
            'name'        => 'PIC Test',
            'position'    => 'Manager',
            'phone'       => '08123456789',
            'email'       => 'pic@e2etest.com',
        ]);
    }

    public function test_goal_requires_po_proof_upload(): void
    {
        $response = $this->actingAs($this->sales)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'need_date'           => '2026-08-15',
            'type'                => 'Non Projek',
            'notes'               => 'Test proof requirement',
            'items'               => [
                [
                    'product_name' => 'Product A',
                    'qty'          => 10,
                    'unit'         => 'pcs',
                    'description'  => 'Test product',
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $rfq = Rfq::withTrashed()->first();

        $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $rfq->items->first()->id => [
                    'product_name'     => 'Product A',
                    'qty'              => 10,
                    'unit'             => 'pcs',
                    'description'      => 'Test product',
                    'hpp'              => 50000,
                    'ongkir_pedia'     => 5000,
                    'ongkir_pelanggan' => 3000,
                    'margin'           => 1.15,
                    'ceiling'          => 1000,
                    'validity_days'    => 7,
                ],
            ],
        ]);

        $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));

        $response = $this->actingAs($this->sales)->post(route('rfq.approve_goal', $rfq), [
            'items' => [
                $rfq->items->first()->id => ['qty' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('po_file');
        $rfq->refresh();
        $this->assertNotEquals(Rfq::STATUS_GOAL, $rfq->status);
    }

    public function test_full_sales_to_goal_flow(): void
    {
        // ─── Step 1: Sales Marketing creates RFQ ─────────────────────────
        $response = $this->actingAs($this->sales)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'need_date'           => '2026-08-15',
            'type'                => 'Non Projek',
            'notes'               => 'Test E2E flow',
            'items'               => [
                [
                    'product_name' => 'Product A',
                    'qty'          => 10,
                    'unit'         => 'pcs',
                    'description'  => 'Test product',
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $rfq = Rfq::withTrashed()->first();
        $this->assertNotNull($rfq);
        $this->assertEquals('Pending Admin', $rfq->status);
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status);
        $this->assertEquals($this->sales->id, $rfq->sales_id);
        $this->assertEquals('Non Projek', $rfq->type);
        $this->assertEquals('2026-08-15', $rfq->need_date->format('Y-m-d'));
        $this->assertCount(1, $rfq->items);

        // ─── Step 2: Admin Purchase opens price form ─────────────────────
        $response = $this->actingAs($this->admin)->get(route('rfq.price_form', $rfq));
        $response->assertStatus(200);

        // ─── Step 3: Admin Purchase submits price ────────────────────────
        $item = $rfq->items->first();
        $response = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $item->id => [
                    'product_name'     => 'Product A',
                    'qty'              => 10,
                    'unit'             => 'pcs',
                    'description'      => 'Test product',
                    'hpp'              => 50000,
                    'ongkir_pedia'     => 5000,
                    'ongkir_pelanggan' => 3000,
                    'margin'           => 1.15,
                    'ceiling'          => 1000,
                    'validity_days'    => 7,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
        $item->refresh();
        $this->assertEquals(50000, $item->hpp);
        $this->assertEquals(1.15, (float) $item->margin);

        $expectedPrice = ceil((50000 + 5000 + 3000) * 1.15 / 1000) * 1000;
        $this->assertEquals($expectedPrice, $item->price_after_margin);

        // ─── Step 4: Leader approves RFQ ─────────────────────────────────
        $response = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        // ─── Step 5: Sales Marketing views RFQ detail ────────────────────
        $response = $this->actingAs($this->sales)->get(route('rfq.show', $rfq));
        $response->assertStatus(200);

        // ─── Step 6: Sales Marketing edits QTY ───────────────────────────
        $response = $this->actingAs($this->sales)->put(route('rfq.update_qty', $rfq), [
            'items' => [
                $item->id => ['qty' => 15],
            ],
        ]);
        $response->assertSessionHas('success');
        $item->refresh();
        $this->assertEquals(15, (int) $item->qty);

        // ─── Step 7: Sales Marketing downloads PDF (generates Quo) ───────
        $response = $this->actingAs($this->sales)->get(route('rfq.download_quotation', $rfq));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_QUOTATION_CREATED, $rfq->status);

        // ─── Step 8: Sales Marketing marks GOAL ──────────────────────────
        $pdfFile = UploadedFile::fake()->create('po_file.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->sales)->post(route('rfq.approve_goal', $rfq), [
            'items'  => [
                $item->id => ['qty' => 15],
            ],
            'po_file' => $pdfFile,
        ]);
        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->status);

        // ─── Step 9: Verify PurchaseOrder was created ────────────────────
        $po = PurchaseOrder::where('rfq_id', $rfq->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals($this->customer->id, $po->customer_id);
        $this->assertEquals($this->sales->id, $po->sales_id);
        $this->assertEquals(PurchaseOrder::STATUS_PENDING, $po->status);
        $this->assertNotNull($po->po_number);
        $this->assertStringStartsWith('PO-', $po->po_number);
        $this->assertCount(1, $po->items);

        $expectedGrandTotal = 15 * $item->price_after_margin;
        $this->assertEquals($expectedGrandTotal, (float) $po->grand_total);

        // ─── Step 10: Admin can see the PO ────────────────────────────────
        $response = $this->actingAs($this->admin)->get(route('po.show', $po));
        $response->assertStatus(200);

        // ─── Step 11: Sales can see the PO too (their own) ───────────────
        $response = $this->actingAs($this->sales)->get(route('po.show', $po));
        $response->assertStatus(200);

        // ─── Step 12: Verify notification was created for Admin ──────────
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'title'   => 'PO Baru (Dari GOAL Sales)',
        ]);
    }
}
