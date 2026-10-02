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
                    'biaya_kirim'      => 3000,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 15,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 7,
                ],
            ],
        ]);

        $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));

        // Try to approve GOAL without uploading PO first - should fail because status is not PO_PENDING_LEADER
        // The approveGoal method requires Leader/SuperAdmin and status PO_PENDING_LEADER
        // Since status is APPROVED, it will abort with 403
        $response = $this->actingAs($this->leader)->post(route('rfq.approve_goal', $rfq), [
            'items' => [
                $rfq->items->first()->id => ['qty' => 10],
            ],
        ]);

        // Should fail with 403 because status is APPROVED, not PO_PENDING_LEADER
        $response->assertStatus(403);
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
                    'biaya_kirim'      => 3000,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 15,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 7,
                ],
            ],
        ]);

        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
        $item->refresh();
        $this->assertEquals(50000, $item->hpp);
        $this->assertEquals(15, (float) $item->margin_value); // 15%

        // Debug: output actual values
        // With minimum profit rule: baseCost=58000, 15% margin = 8700 < 50000, so profit = 50000
        // withMargin = 58000 + 50000 = 108000, ceil to 1000 = 108000
        $expectedPrice = 108000;
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
            'revision_notes' => 'Updated quantity for client request',
            'items' => [
                $item->id => [
                    'id' => $item->id,
                    'product_name' => $item->product_name,
                    'qty' => 15,
                    'unit' => $item->unit,
                    'description' => $item->description,
                ],
            ],
        ]);
        $response->assertSessionHas('success');
        $item->refresh();
        $this->assertEquals(15, (int) $item->qty);
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status);

        // ─── Step 7: Admin re-submits price (after QTY revision) ──────────
        $response = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $item->id => [
                    'product_name'     => 'Product A',
                    'qty'              => 15,
                    'unit'             => 'pcs',
                    'description'      => 'Test product',
                    'hpp'              => 50000,
                    'ongkir_pedia'     => 5000,
                    'biaya_kirim'      => 3000,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 15,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 7,
                ],
            ],
        ]);
        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);

        // ─── Step 8: Leader re-approves ───────────────────────────────────
        $response = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        // ─── Step 9: Sales Marketing downloads PDF (generates Quo) ────────
        $response = $this->actingAs($this->sales)->get(route('rfq.download_quotation', $rfq));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_QUOTATION_CREATED, $rfq->status);

        // ─── Step 10: Sales Marketing uploads Client PO ───────────────────
        $pdfFile = UploadedFile::fake()->create('po_file.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->sales)->post(route('rfq.upload_po', $rfq), [
            'po_file' => $pdfFile,
        ]);
        
        // Debug: check response
        error_log("DEBUG: response status = " . $response->getStatusCode());
        
        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PO_PENDING_ADMIN, $rfq->status);
        
        $this->assertNotNull($rfq->po_file_path);

        // Verify Admin & Sales can access PO attachment without 403 Forbidden
        $adminViewPo = $this->actingAs($this->admin)->get(route('rfq.view_po', $rfq));
        $adminViewPo->assertStatus(200);

        $salesViewPo = $this->actingAs($this->sales)->get(route('rfq.view_po', $rfq));
        $salesViewPo->assertStatus(200);

        // ─── Step 11: Admin verifies PO ───────────────────────────────────
        $response = $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq));
        $response->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PO_PENDING_LEADER, $rfq->status);

        // ─── Step 12: Leader approves GOAL ────────────────────────────────
        $response = $this->actingAs($this->leader)->post(route('rfq.approve_goal', $rfq), [
            'items' => [
                $item->id => ['qty' => 15],
            ],
        ]);
        
        $response->assertRedirect(route('rfq.show', $rfq));
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->status);

        // ─── Step 11: Verify PurchaseOrder was created ────────────────────
        $po = PurchaseOrder::where('rfq_id', $rfq->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals($this->customer->id, $po->customer_id);
        $this->assertEquals($this->sales->id, $po->sales_id);
        $this->assertEquals(PurchaseOrder::STATUS_GOAL, $po->status); // Auto-approved when created from GOAL
        $this->assertNotNull($po->po_number);
        $this->assertStringStartsWith('PO-', $po->po_number);
        $this->assertCount(1, $po->items);

        $expectedGrandTotal = 15 * $item->price_after_margin;
        $this->assertEquals($expectedGrandTotal, (float) $po->grand_total);

        // ─── Step 12: Admin can see the PO ────────────────────────────────
        $response = $this->actingAs($this->admin)->get(route('po.show', $po));
        $response->assertStatus(200);

        // ─── Step 13: Sales can see the PO too (their own) ───────────────
        $response = $this->actingAs($this->sales)->get(route('po.show', $po));
        $response->assertStatus(200);

        // ─── Step 14: Admin & Sales can download PO PDF ──────────────────
        $adminPoDownload = $this->actingAs($this->admin)->get(route('po.download-pdf', $po));
        $adminPoDownload->assertStatus(200);

        $salesPoDownload = $this->actingAs($this->sales)->get(route('po.download-pdf', $po));
        $salesPoDownload->assertStatus(200);

        // ─── Step 15: Verify notification was created for Admin ──────────
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'title'   => 'PO Baru (Dari GOAL Sales)',
        ]);
    }
}
