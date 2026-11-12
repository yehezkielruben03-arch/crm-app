<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Notification;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RfqRejectGoalTest extends TestCase
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
            'name'     => 'Sales Reject Goal',
            'username' => 'sales_reject_goal',
            'email'    => 'sales_reject_goal@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Sales Marketing',
            'status'   => 'Active',
        ]);

        $this->admin = User::factory()->create([
            'name'     => 'Admin Reject Goal',
            'username' => 'admin_reject_goal',
            'email'    => 'admin_reject_goal@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Admin',
            'status'   => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'name'     => 'Leader Reject Goal',
            'username' => 'leader_reject_goal',
            'email'    => 'leader_reject_goal@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Leader',
            'status'   => 'Active',
        ]);

        $this->customer = Customer::factory()->create([
            'company_name' => 'PT Uji Reject Goal',
            'company_code' => null,
            'sales_id'     => $this->sales->id,
            'status'       => 'Active',
        ]);

        $this->contact = CustomerContact::factory()->create([
            'customer_id' => $this->customer->id,
            'name'        => 'PIC Uji',
            'position'    => 'Purchasing',
            'phone'       => '081234567890',
            'email'       => 'pic@rejectgoal.test',
        ]);
    }

    private function advanceRfqToGoal(float $qty = 1): array
    {
        $rfq = Rfq::create([
            'rfq_number'          => '261112-0901',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_code'       => $this->customer->company_code,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'rfq_date'            => now()->toDateString(),
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'priority'            => Rfq::PRIORITY_NORMAL,
        ]);

        $item = RfqItem::create([
            'rfq_id'             => $rfq->id,
            'product_name'       => 'Switch Managed 24 Port',
            'qty'                => $qty,
            'unit'               => 'Unit',
            'hpp'                => 1000000,
            'ongkir_pedia'       => 0,
            'biaya_kirim'        => 0,
            'margin_type'        => 'percentage',
            'margin_value'       => 20,
            'margin'             => 20,
            'custom_ceiling'     => 1000,
            'ceiling'            => 1000,
            'validity_days'      => 7,
        ]);
        $item->price_after_margin = $item->calculatePriceAfterMargin();
        $item->save();

        $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => $qty,
                    'unit'           => 'Unit',
                    'hpp'            => 1000000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 1000,
                    'validity_days'  => 7,
                ],
            ],
        ])->assertRedirect(route('rfq.show', $rfq));

        $this->actingAs($this->leader)->post(route('rfq.approve', $rfq))->assertSessionHas('success');

        $poFile = UploadedFile::fake()->create('po_customer_qty2.pdf', 120, 'application/pdf');
        $this->actingAs($this->sales)->post(route('rfq.upload_po', $rfq), [
            'po_file' => $poFile,
        ])->assertSessionHas('success');

        $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq))->assertSessionHas('success');
        $this->actingAs($this->leader)->post(route('rfq.approve_goal', $rfq))->assertSessionHas('success');

        $rfq->refresh();
        $item->refresh();
        $po = PurchaseOrder::where('rfq_id', $rfq->id)->firstOrFail();

        return [$rfq, $item, $po];
    }

    public function test_admin_can_reject_goal_keep_po_file_edit_qty_and_reapprove_goal(): void
    {
        [$rfq, $item, $po] = $this->advanceRfqToGoal(1);

        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->status);
        $this->assertEquals(PurchaseOrder::STATUS_GOAL, $po->status);
        $this->assertEquals(1, (float) $po->items()->first()->quantity);
        $originalGrandTotal = (float) $po->grand_total;
        $storedPoPath = $rfq->po_file_path;

        // Admin detects qty mismatch (PO = 2, RFQ = 1) and rejects GOAL while keeping PO file
        $rejectResp = $this->actingAs($this->admin)->post(route('rfq.reject_goal', $rfq), [
            'rejection_reason' => 'Qty pada PO customer 2 Unit sedangkan di RFQ masih 1 Unit',
        ]);
        $rejectResp->assertRedirect(route('rfq.show', $rfq));
        $rejectResp->assertSessionHas('warning');

        $rfq->refresh();
        $po->refresh();

        $this->assertEquals(Rfq::STATUS_QUOTATION_SENT, $rfq->status);
        $this->assertEquals('Qty pada PO customer 2 Unit sedangkan di RFQ masih 1 Unit', $rfq->po_rejection_note);
        $this->assertEquals($this->admin->id, $rfq->po_rejected_by);
        $this->assertEquals($storedPoPath, $rfq->po_file_path);
        $this->assertTrue(Storage::disk('public')->exists($storedPoPath));
        $this->assertEquals(PurchaseOrder::STATUS_TIDAK_GOAL, $po->status);
        $this->assertStringContainsString('Qty pada PO customer 2 Unit', $po->rejection_reason);

        // Notifications sent to Leader and Sales
        $this->assertTrue(Notification::where('user_id', $this->leader->id)->where('title', 'like', '%Direject%')->exists());
        $this->assertTrue(Notification::where('user_id', $this->sales->id)->where('title', 'like', '%Direject%')->exists());

        // Detail page offers Reuse Stored PO button and View PO button
        $showResp = $this->actingAs($this->admin)->get(route('rfq.show', $rfq));
        $showResp->assertStatus(200);
        $showResp->assertSee('Gunakan File PO Tersimpan');
        $showResp->assertSee('Lihat Bukti PO Customer');
        $showResp->assertSee('Edit HPP / Revisi Penawaran');

        // Admin edits QTY from 1 -> 2 via submitPrice (price_form)
        $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 2,
                    'unit'           => 'Unit',
                    'hpp'            => 1000000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 1000,
                    'validity_days'  => 7,
                ],
            ],
        ])->assertRedirect(route('rfq.show', $rfq));

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);

        // Leader approves revised quotation
        $this->actingAs($this->leader)->post(route('rfq.approve', $rfq))->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        // Reuse existing PO file to resubmit for Admin verification -> Leader GOAL approval
        $this->actingAs($this->admin)->post(route('rfq.upload_po', $rfq), [
            'use_existing_po' => '1',
        ])->assertSessionHas('success');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PO_PENDING_ADMIN, $rfq->status);
        $this->assertNull($rfq->po_rejection_note);
        $this->assertEquals($storedPoPath, $rfq->po_file_path);

        $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq))->assertSessionHas('success');
        $this->actingAs($this->leader)->post(route('rfq.approve_goal', $rfq))->assertSessionHas('success');

        $rfq->refresh();
        $po->refresh();

        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->status);
        $this->assertEquals(1, PurchaseOrder::where('rfq_id', $rfq->id)->count());
        $this->assertEquals(PurchaseOrder::STATUS_GOAL, $po->status);
        $this->assertNull($po->rejection_reason);
        $this->assertEquals(2, (float) $po->items()->first()->quantity);
        $this->assertEquals($originalGrandTotal * 2, (float) $po->grand_total);
    }

    public function test_leader_can_reject_goal_and_delete_po_file_then_edit_directly(): void
    {
        [$rfq, $item, $po] = $this->advanceRfqToGoal(1);
        $oldPath = $rfq->po_file_path;

        $resp = $this->actingAs($this->leader)->post(route('rfq.reject_goal', $rfq), [
            'rejection_reason' => 'File PO lama salah lampiran dan harga perlu koreksi',
            'remove_po_file'   => '1',
        ]);
        $resp->assertRedirect(route('rfq.show', $rfq));

        $rfq->refresh();
        $po->refresh();

        $this->assertEquals(Rfq::STATUS_QUOTATION_SENT, $rfq->status);
        $this->assertNull($rfq->po_file_path);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertEquals(PurchaseOrder::STATUS_TIDAK_GOAL, $po->status);

        // Reuse attempt without existing PO file fails gracefully
        $reuseFail = $this->actingAs($this->sales)->post(route('rfq.upload_po', $rfq), [
            'use_existing_po' => '1',
        ]);
        $reuseFail->assertSessionHas('error');
        $this->assertEquals(Rfq::STATUS_QUOTATION_SENT, $rfq->fresh()->status);

        // Leader directly edits qty via submitPrice -> immediately Approved
        $this->actingAs($this->leader)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 3,
                    'unit'           => 'Unit',
                    'hpp'            => 1000000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 1000,
                    'validity_days'  => 7,
                ],
            ],
        ])->assertRedirect(route('rfq.show', $rfq));

        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->fresh()->status);
        $this->assertEquals(3, (float) $item->fresh()->qty);
    }

    public function test_reject_goal_authorization_visibility_and_validation_guards(): void
    {
        [$rfq, $item, $po] = $this->advanceRfqToGoal(1);

        // Show page button visibility
        $this->actingAs($this->admin)->get(route('rfq.show', $rfq))
            ->assertSee('Koreksi Data Setelah GOAL')
            ->assertSee('Reject GOAL');
        $this->actingAs($this->leader)->get(route('rfq.show', $rfq))
            ->assertSee('Koreksi Data Setelah GOAL')
            ->assertSee('Reject GOAL');
        $this->actingAs($this->sales)->get(route('rfq.show', $rfq))
            ->assertDontSee('Koreksi Data Setelah GOAL')
            ->assertDontSee(", 'goal')");

        // Sales cannot reject GOAL
        $this->actingAs($this->sales)->post(route('rfq.reject_goal', $rfq), [
            'rejection_reason' => 'Sales tidak boleh reject GOAL',
        ])->assertStatus(403);

        // Direct edit_qty while status is GOAL is blocked until GOAL is rejected
        $this->actingAs($this->admin)->get(route('rfq.edit_qty', $rfq))->assertStatus(403);
        $this->actingAs($this->admin)->put(route('rfq.update_qty', $rfq), [
            'revision_notes' => 'Direct edit bypass attempt',
        ])->assertStatus(403);

        // Invalid rejection_reason fails validation
        $this->actingAs($this->admin)->post(route('rfq.reject_goal', $rfq), [
            'rejection_reason' => 'ab',
        ])->assertSessionHasErrors('rejection_reason');

        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->fresh()->status);
        $this->assertEquals(PurchaseOrder::STATUS_GOAL, $po->fresh()->status);

        // Admin cannot call reject_goal on a non-GOAL RFQ
        $this->actingAs($this->admin)->post(route('rfq.reject_goal', $rfq), [
            'rejection_reason' => 'Alasan sah pertama kali',
        ])->assertRedirect(route('rfq.show', $rfq));

        $this->actingAs($this->admin)->post(route('rfq.reject_goal', $rfq), [
            'rejection_reason' => 'Percobaan kedua saat sudah Quotation Sent',
        ])->assertStatus(403);
    }
}
