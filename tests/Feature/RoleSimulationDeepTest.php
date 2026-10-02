<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class RoleSimulationDeepTest extends TestCase
{
    use RefreshDatabase;

    private User $salesA;
    private User $salesB;
    private User $admin;
    private User $leader;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->salesA = User::factory()->create([
            'name'     => 'Sales Ahmad Pratama',
            'username' => 'sales_ahmad',
            'email'    => 'ahmad@pedia.co.id',
            'phone'    => '081234567890',
            'role'     => 'Sales Marketing',
            'status'   => 'Active',
        ]);

        $this->salesB = User::factory()->create([
            'name'     => 'Sales Budi Santoso',
            'username' => 'sales_budi',
            'email'    => 'budi@pedia.co.id',
            'phone'    => '081234567891',
            'role'     => 'Sales Marketing',
            'status'   => 'Active',
        ]);

        $this->admin = User::factory()->create([
            'name'     => 'Admin Dewi Purwanti',
            'username' => 'admin_dewi',
            'email'    => 'dewi@pedia.co.id',
            'role'     => 'Admin',
            'status'   => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'name'     => 'Leader Hendra Kusuma',
            'username' => 'leader_hendra',
            'email'    => 'hendra@pedia.co.id',
            'role'     => 'Leader',
            'status'   => 'Active',
        ]);

        $this->superAdmin = User::factory()->create([
            'name'     => 'Super Admin Utama',
            'username' => 'superadmin_main',
            'email'    => 'superadmin@pedia.co.id',
            'role'     => 'Super Admin',
            'status'   => 'Active',
        ]);
    }

    /**
     * Complete lifecycle simulation across Sales, Admin, and Leader:
     */
    public function test_complete_role_lifecycle_simulation(): void
    {
        // =========================================================================
        // STEP 1: ROLE SALES A - Input Customer (Prospect) & Contact
        // =========================================================================
        $customerResp = $this->actingAs($this->salesA)->post(route('customers.store'), [
            'company_name'      => 'PT Teknologi Maju Bersama',
            'customer_type'     => 'Perusahaan',
            'industry'          => 'Information Technology',
            'status'            => 'Prospect',
            'address'           => 'Jl. Sudirman Kav 25, Menara Mandiri',
            'city'              => 'Jakarta Selatan',
            'province'          => 'DKI Jakarta',
            'country'           => 'Indonesia',
            'ongkir_pedia'      => 150000,
            'cp_name'           => 'Ibu Maya Lestari',
            'cp_position'       => 'IT Procurement Manager',
            'cp_phone'          => '081388889999',
            'cp_email'          => 'maya@teknologimaju.com',
        ]);
        
        $customerResp->assertSessionHas('success');
        
        // PT prefix is stripped by design for exact match deduplication
        $customer = Customer::where('company_name', 'Teknologi Maju Bersama')->first();
        $this->assertNotNull($customer, 'Customer should be successfully created');
        $this->assertEquals($this->salesA->id, $customer->sales_id);
        $this->assertEquals(Customer::STATUS_PROSPECT, $customer->status);
        $this->assertEquals(150000, (int) $customer->ongkir_pedia);

        $contact = CustomerContact::where('customer_id', $customer->id)->first();
        $this->assertNotNull($contact, 'Customer PIC contact should be automatically created');
        $this->assertEquals('Ibu Maya Lestari', $contact->name);

        // =========================================================================
        // STEP 2: ROLE ADMIN - Review & Approve Customer in Approval Center
        // =========================================================================
        $adminApprovalCenterResp = $this->actingAs($this->admin)->get(route('approvals.index'));
        $adminApprovalCenterResp->assertStatus(200);
        $adminApprovalCenterResp->assertSee('Teknologi Maju Bersama');

        // Admin approves the customer
        $approveCustomerResp = $this->actingAs($this->admin)->post(route('customers.approve', $customer));
        $approveCustomerResp->assertSessionHas('success');
        $customer->refresh();
        $this->assertEquals(Customer::STATUS_ACTIVE, $customer->status);
        $this->assertNotNull($customer->company_code, 'Company code generated upon Admin approval');

        // =========================================================================
        // STEP 3: ROLE SALES A - Create RFQ (Multi-item, Projek)
        // =========================================================================
        $rfqResp = $this->actingAs($this->salesA)->post(route('rfq.store'), [
            'customer_id'         => $customer->id,
            'customer_contact_id' => $contact->id,
            'type'                => 'Projek',
            'need_date'           => now()->addDays(7)->format('Y-m-d'),
            'notes'               => 'Proyek Instalasi Access Door Kantor Pusat',
            'items'               => [
                [
                    'category'     => 'Hardware',
                    'product_name' => 'Magnetic Lock 600 Lbs + Bracket ZL',
                    'qty'          => 2,
                    'unit'         => 'Set',
                    'description'  => 'Hardware Access Door Utama',
                ],
                [
                    'category'     => 'Jasa Pemasangan',
                    'product_name' => 'Jasa Pemasangan & Terminasi Access Door',
                    'qty'          => 2,
                    'unit'         => 'Titik',
                    'description'  => 'Instalasi teknisi bersertifikat',
                ],
                [
                    'category'     => 'Material Support',
                    'product_name' => 'Kabel Belden UTP Cat6 Original',
                    'qty'          => 1,
                    'unit'         => 'Roll',
                    'description'  => 'Material support perkabelan',
                ],
            ],
        ]);

        $rfqResp->assertSessionHas('success');
        $rfq = Rfq::with('items')->where('customer_id', $customer->id)->first();
        $this->assertNotNull($rfq);
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status);
        $this->assertEquals(3, $rfq->items->count());
        $this->assertEquals($this->salesA->id, $rfq->sales_id);

        // Check RFQ Number format YYMMDD-XXXX
        $todayPrefix = date('ymd');
        $this->assertStringStartsWith($todayPrefix, $rfq->rfq_number, "RFQ number should start with YYMMDD format: {$todayPrefix}");

        // Security check: Sales A CANNOT access price_form or submit price
        $salesForbiddenResp = $this->actingAs($this->salesA)->get(route('rfq.price_form', $rfq));
        $this->assertNotEquals(200, $salesForbiddenResp->status(), 'Sales should NOT have access to price form (HPP)');

        // =========================================================================
        // STEP 4: ROLE ADMIN - Input Harga & Test Feature 3.1 (Tambah Vendor Inline)
        // =========================================================================
        // Admin opens price form
        $adminViewResp = $this->actingAs($this->admin)->get(route('rfq.price_form', $rfq));
        $adminViewResp->assertStatus(200);

        // Feature 3.1: Admin adds vendor inline via AJAX
        $inlineVendorResp = $this->actingAs($this->admin)->postJson(route('vendors.store'), [
            'nama_vendor' => 'PT Mitra Solusi Security',
            'kategori'    => 'Hardware',
            'pic'         => 'Pak Rudi Suherman',
            'kontak'      => '081299900011',
            'alamat'      => 'Harco Mangga Dua Blok B No. 12',
        ]);

        $inlineVendorResp->assertStatus(200);
        $inlineVendorResp->assertJson(['success' => true]);
        $vendorData = $inlineVendorResp->json('vendor');
        $this->assertNotNull($vendorData['id']);
        $newVendorId = $vendorData['id'];

        $newVendor = Vendor::find($newVendorId);
        $this->assertNotNull($newVendor);
        $this->assertEquals('PT Mitra Solusi Security', $newVendor->nama_vendor);

        // Admin fills pricing for all 3 items
        $itemHardware = $rfq->items[0];
        $itemJasa     = $rfq->items[1];
        $itemMaterial = $rfq->items[2];

        $submitPriceResp = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $itemHardware->id => [
                    'product_name'     => $itemHardware->product_name,
                    'qty'              => $itemHardware->qty,
                    'unit'             => $itemHardware->unit,
                    'description'      => $itemHardware->description,
                    'vendor_id'        => $newVendorId,
                    'hpp'              => 1250000,
                    'ongkir_pedia'     => 50000,
                    'biaya_kirim'      => 0,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 25,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 14,
                ],
                $itemJasa->id => [
                    'product_name'     => $itemJasa->product_name,
                    'qty'              => $itemJasa->qty,
                    'unit'             => $itemJasa->unit,
                    'description'      => $itemJasa->description,
                    'hpp'              => 450000,
                    'ongkir_pedia'     => 0,
                    'biaya_kirim'      => 0,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 30,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 14,
                ],
                $itemMaterial->id => [
                    'product_name'     => $itemMaterial->product_name,
                    'qty'              => $itemMaterial->qty,
                    'unit'             => $itemMaterial->unit,
                    'description'      => $itemMaterial->description,
                    'hpp'              => 2100000,
                    'ongkir_pedia'     => 25000,
                    'biaya_kirim'      => 25000,
                    'margin_type'      => 'nominal',
                    'margin_value'     => 350000,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 14,
                ],
            ],
        ]);

        $submitPriceResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status, 'RFQ status should move to Pending Leader');

        $itemHardware->refresh();
        $this->assertEquals($newVendorId, $itemHardware->vendor_id, 'Hardware item should be associated with newly created inline vendor');
        $this->assertEquals(1250000, (int) $itemHardware->hpp);
        $this->assertGreaterThan(1250000, (int) $itemHardware->price_after_margin);

        // Security check: Admin CANNOT approve RFQ (only Leader / Super Admin)
        $adminApproveResp = $this->actingAs($this->admin)->post(route('rfq.approve', $rfq));
        $this->assertNotEquals(302, $adminApproveResp->status() === 403 ? 403 : 302, 'Admin should NOT have permission to approve RFQ');

        // =========================================================================
        // STEP 5: ROLE LEADER - Approval Center & Decision
        // =========================================================================
        // Leader opens approval center / dashboard
        $leaderDashResp = $this->actingAs($this->leader)->get(route('approvals.index'));
        $leaderDashResp->assertStatus(200);

        // Leader views RFQ detail
        $leaderViewResp = $this->actingAs($this->leader)->get(route('rfq.show', $rfq));
        $leaderViewResp->assertStatus(200);

        // Leader approves the RFQ
        $leaderApproveResp = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $leaderApproveResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status, 'RFQ status should now be Approved');

        // =========================================================================
        // STEP 6: ROLE SALES A - Download Quotation PDF & Check Content
        // =========================================================================
        $pdfResp = $this->actingAs($this->salesA)->get(route('rfq.download_quotation', $rfq));
        $pdfResp->assertStatus(200);
        $pdfResp->assertHeader('Content-Type', 'application/pdf');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_QUOTATION_CREATED, $rfq->status, 'Status moves to Quotation Created upon generation');

        // =========================================================================
        // STEP 7: ROLE SALES A - Comprehensive Revision Workflow
        // =========================================================================
        // Customer asks for change: qty of Hardware becomes 3, and Jasa becomes 3
        $revisionResp = $this->actingAs($this->salesA)->put(route('rfq.update_qty', $rfq), [
            'revision_notes' => 'Customer request: Tambah 1 unit lock dan revisi Qty',
            'items' => [
                $itemHardware->id => [
                    'id'           => $itemHardware->id,
                    'product_name' => $itemHardware->product_name,
                    'qty'          => 3, // Changed from 2 to 3
                    'unit'         => $itemHardware->unit,
                    'description'  => $itemHardware->description,
                ],
                $itemJasa->id => [
                    'id'           => $itemJasa->id,
                    'product_name' => $itemJasa->product_name,
                    'qty'          => 3, // Changed from 2 to 3
                    'unit'         => $itemJasa->unit,
                    'description'  => $itemJasa->description,
                ],
                $itemMaterial->id => [
                    'id'           => $itemMaterial->id,
                    'product_name' => $itemMaterial->product_name,
                    'qty'          => 1,
                    'unit'         => $itemMaterial->unit,
                    'description'  => $itemMaterial->description,
                ],
            ],
        ]);

        $revisionResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status, 'After revision, status MUST return to Pending Admin');
        $this->assertEquals('Customer request: Tambah 1 unit lock dan revisi Qty', $rfq->revision_notes, 'Revision notes should be recorded');

        $itemHardware->refresh();
        $this->assertEquals(3, (int) $itemHardware->qty);

        // =========================================================================
        // STEP 8: ADMIN & LEADER - Re-submission & Re-approval after revision
        // =========================================================================
        // Admin re-submits price
        $adminReSubmitResp = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $itemHardware->id => [
                    'product_name'     => $itemHardware->product_name,
                    'qty'              => 3,
                    'unit'             => 'Set',
                    'description'      => $itemHardware->description,
                    'vendor_id'        => $newVendorId,
                    'hpp'              => 1250000,
                    'ongkir_pedia'     => 50000,
                    'biaya_kirim'      => 0,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 25,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 14,
                ],
                $itemJasa->id => [
                    'product_name'     => $itemJasa->product_name,
                    'qty'              => 3,
                    'unit'             => 'Titik',
                    'description'      => $itemJasa->description,
                    'hpp'              => 450000,
                    'ongkir_pedia'     => 0,
                    'biaya_kirim'      => 0,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 30,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 14,
                ],
                $itemMaterial->id => [
                    'product_name'     => $itemMaterial->product_name,
                    'qty'              => 1,
                    'unit'             => 'Roll',
                    'description'      => $itemMaterial->description,
                    'hpp'              => 2100000,
                    'ongkir_pedia'     => 25000,
                    'biaya_kirim'      => 25000,
                    'margin_type'      => 'nominal',
                    'margin_value'     => 350000,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 14,
                ],
            ],
        ]);
        $adminReSubmitResp->assertSessionHas('success');

        // Leader re-approves
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
        $leaderReApproveResp = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $leaderReApproveResp->assertSessionHas('success');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        // =========================================================================
        // STEP 9: SALES A - Upload Client PO Proof
        // =========================================================================
        $fakePoPdf = UploadedFile::fake()->create('Client_PO_PT_Maju.pdf', 150, 'application/pdf');
        $uploadPoResp = $this->actingAs($this->salesA)->post(route('rfq.upload_po', $rfq), [
            'po_file' => $fakePoPdf,
        ]);

        $uploadPoResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PO_PENDING_ADMIN, $rfq->status);
        $this->assertNotNull($rfq->po_file_path);

        // =========================================================================
        // STEP 10: ADMIN & LEADER - Verify PO & Approve Goal
        // =========================================================================
        // Admin verifies PO
        $verifyPoResp = $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq));
        $verifyPoResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PO_PENDING_LEADER, $rfq->status);

        // Leader approves Goal
        $goalResp = $this->actingAs($this->leader)->post(route('rfq.approve_goal', $rfq), [
            'items' => [
                $itemHardware->id => ['qty' => 3],
                $itemJasa->id     => ['qty' => 3],
                $itemMaterial->id => ['qty' => 1],
            ],
        ]);

        $goalResp->assertRedirect(route('rfq.show', $rfq));
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->status, 'RFQ status should finally reach Goal');

        // Verify PurchaseOrder record was created
        $po = PurchaseOrder::where('rfq_id', $rfq->id)->first();
        $this->assertNotNull($po, 'PurchaseOrder entity should be automatically created');
        $this->assertEquals($customer->id, $po->customer_id);
        $this->assertEquals($this->salesA->id, $po->sales_id);
        $this->assertGreaterThan(0, (float) $po->grand_total);

        // =========================================================================
        // STEP 11: DATA ISOLATION / ACCESS CONTROL (Sales B check)
        // =========================================================================
        // Sales B cannot see or manipulate Sales A's RFQ
        $salesBRfqResp = $this->actingAs($this->salesB)->get(route('rfq.show', $rfq));
        $this->assertEquals(403, $salesBRfqResp->status(), 'Sales B must be rejected with 403 Forbidden when accessing Sales A RFQ detail');
    }

    /**
     * Test Leader rejection flow with notes returning RFQ to Admin
     */
    public function test_leader_rejection_flow_returns_to_admin(): void
    {
        $customer = Customer::create([
            'company_name' => 'PT Rejection Flow Test',
            'status'       => Customer::STATUS_ACTIVE,
            'sales_id'     => $this->salesA->id,
            'company_code' => 'REJ-001',
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name'        => 'Contact Rejection Test',
            'phone'       => '08123456789',
        ]);

        $rfq = Rfq::create([
            'rfq_number'          => Rfq::generateRfqNumber(),
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_contact_id' => $contact->id,
            'type'                => 'Non Projek',
            'sales_id'            => $this->salesA->id,
            'sales_name'          => $this->salesA->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'status'              => Rfq::STATUS_PENDING_LEADER, // Admin already submitted price
        ]);

        $item = $rfq->items()->create([
            'product_name'       => 'Server Rack 42U',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 5000000,
            'ongkir_pedia'       => 200000,
            'margin_type'        => 'percentage',
            'margin_value'       => 10, // Margin considered too low by Leader
            'price_after_margin' => 5720000,
        ]);

        // Leader rejects RFQ with notes
        $rejectResp = $this->actingAs($this->leader)->post(route('rfq.reject', $rfq), [
            'notes' => 'Margin 10% terlalu tipis untuk Rack 42U, tolong naikkan ke minimal 20%.',
        ]);

        $rejectResp->assertSessionHas('warning');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status, 'Rejected RFQ must return to Pending Admin');
        $this->assertEquals('Margin 10% terlalu tipis untuk Rack 42U, tolong naikkan ke minimal 20%.', $rfq->revision_notes);

        // Admin re-submits with 20% margin
        $adminReSubmitResp = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $item->id => [
                    'product_name'     => $item->product_name,
                    'qty'              => 1,
                    'unit'             => 'Unit',
                    'hpp'              => 5000000,
                    'ongkir_pedia'     => 200000,
                    'margin_type'      => 'percentage',
                    'margin_value'     => 20,
                    'custom_ceiling'   => 1000,
                    'validity_days'    => 14,
                ],
            ],
        ]);

        $adminReSubmitResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);

        // Leader now approves
        $approveResp = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $approveResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);
    }

    /**
     * Test role security boundaries and negative access control
     */
    public function test_role_security_boundaries(): void
    {
        $customer = Customer::create([
            'company_name' => 'PT Security Test',
            'status'       => Customer::STATUS_ACTIVE,
            'sales_id'     => $this->salesA->id,
            'company_code' => 'SEC-001',
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $customer->id,
            'name'        => 'PIC Security Test',
            'phone'       => '08123456789',
        ]);

        $rfq = Rfq::create([
            'rfq_number'          => Rfq::generateRfqNumber(),
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_contact_id' => $contact->id,
            'type'                => 'Non Projek',
            'sales_id'            => $this->salesA->id,
            'sales_name'          => $this->salesA->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'status'              => Rfq::STATUS_PENDING_LEADER,
        ]);

        $item = $rfq->items()->create([
            'product_name' => 'Test Item',
            'qty'          => 1,
            'unit'         => 'Pcs',
            'hpp'          => 100000,
            'price_after_margin' => 150000,
        ]);

        // 1. Sales CANNOT approve RFQ (must be 403)
        $salesApprove = $this->actingAs($this->salesA)->post(route('rfq.approve', $rfq));
        $this->assertEquals(403, $salesApprove->status());

        // 2. Sales CANNOT reject RFQ (must be 403)
        $salesReject = $this->actingAs($this->salesA)->post(route('rfq.reject', $rfq), ['notes' => 'Hack']);
        $this->assertEquals(403, $salesReject->status());

        // 3. Admin CANNOT approve RFQ (must be 403, requires Leader/Super Admin)
        $adminApprove = $this->actingAs($this->admin)->post(route('rfq.approve', $rfq));
        $this->assertEquals(403, $adminApprove->status());

        // 4. Sales CANNOT verify PO (must be 403, requires Admin/Super Admin)
        $rfq->update(['status' => Rfq::STATUS_PO_PENDING_ADMIN]);
        $salesVerifyPo = $this->actingAs($this->salesA)->post(route('rfq.verify_po', $rfq));
        $this->assertEquals(403, $salesVerifyPo->status());

        // 5. Admin CANNOT approve Goal (must be 403, requires Leader/Super Admin)
        $rfq->update(['status' => Rfq::STATUS_PO_PENDING_LEADER]);
        $adminApproveGoal = $this->actingAs($this->admin)->post(route('rfq.approve_goal', $rfq), [
            'items' => [$item->id => ['qty' => 1]],
        ]);
        $this->assertEquals(403, $adminApproveGoal->status());

        // 6. Sales B CANNOT update Qty of Sales A's RFQ
        $salesBUpdateQty = $this->actingAs($this->salesB)->put(route('rfq.update_qty', $rfq), [
            'revision_notes' => 'Unauthorized update',
            'items' => [
                $item->id => [
                    'id'           => $item->id,
                    'product_name' => 'Hacked Item',
                    'qty'          => 999,
                    'unit'         => 'Pcs',
                    'description'  => 'Hack',
                ]
            ]
        ]);
        $this->assertEquals(403, $salesBUpdateQty->status());
    }
}
