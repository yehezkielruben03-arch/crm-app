<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\PurchaseOrder;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeepRootSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected User $syarwani;
    protected User $leader;
    protected User $admin;
    protected User $sales;
    protected Customer $customer;
    protected CustomerContact $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        Storage::fake('public');

        // 1. Akun Atasan (Super Admin Syarwani)
        $this->syarwani = User::create([
            'name'     => 'Syarwani',
            'username' => 'syarwani',
            'email'    => 'syarwani@pedia-group.jp',
            'password' => Hash::make('Pedia551%'),
            'role'     => 'Super Admin',
            'status'   => 'Active',
            'phone'    => '081200000008',
        ]);

        // 2. Akun Leader (Laras)
        $this->leader = User::create([
            'name'     => 'Laras (Leader)',
            'username' => 'laras',
            'email'    => 'laras@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Leader',
            'status'   => 'Active',
            'phone'    => '081200000003',
        ]);

        // 3. Akun Admin Purchase
        $this->admin = User::create([
            'name'     => 'Admin Purchase',
            'username' => 'admin_purchase',
            'email'    => 'admin@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Admin',
            'status'   => 'Active',
            'phone'    => '081200000002',
        ]);

        // 4. Akun Sales Marketing
        $this->sales = User::create([
            'name'     => 'Ade Zulvida',
            'username' => 'ade_zulvida',
            'email'    => 'ade@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Sales',
            'status'   => 'Active',
            'phone'    => '081200000004',
        ]);

        // 5. Customer & Contact
        $this->customer = Customer::create([
            'company_name' => 'PT Karawang Sukses Mandiri',
            'company_code' => 'KSM-001',
            'email'        => 'procurement@ksm.com',
            'phone'        => '02188997766',
            'address'      => 'Kawasan Industri KIIC Karawang',
            'sales_id'     => $this->sales->id,
            'status'       => Customer::STATUS_ACTIVE,
        ]);

        $this->contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name'        => 'Bpk. Hendra',
            'email'       => 'hendra@ksm.com',
            'phone'       => '081234567890',
            'is_primary'  => true,
        ]);
    }

    /**
     * SIMULASI 1: Verifikasi Login & Hak Akses Penuh Akun Syarwani (Super Admin)
     */
    public function test_syarwani_super_admin_credentials_and_full_privileges(): void
    {
        // Test Login via HTTP Post
        $loginResp = $this->post(route('login'), [
            'email'    => 'syarwani@pedia-group.jp',
            'password' => 'Pedia551%',
        ]);
        $loginResp->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->syarwani);

        // Akses halaman-halaman vital
        $this->actingAs($this->syarwani)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($this->syarwani)->get(route('approvals.index'))->assertStatus(200);
        $this->actingAs($this->syarwani)->get(route('users.index'))->assertStatus(200);
        $this->actingAs($this->syarwani)->get(route('analytics.index'))->assertStatus(200);
        $this->actingAs($this->syarwani)->get(route('rfq.create'))->assertStatus(200);
        $this->actingAs($this->syarwani)->get(route('rfq.create_project'))->assertRedirect(route('rfq.create', ['type' => 'Projek']));
    }

    /**
     * SIMULASI 2: Siklus Lengkap End-to-End dengan Verifikasi Rumus HPP, Exclude PPN, Idempotensi, & GOAL
     */
    public function test_full_root_lifecycle_with_all_rules_and_formulas(): void
    {
        // ── STEP 1: Sales buat RFQ Awal (Non Projek) ─────────────────────────
        $storeResp = $this->actingAs($this->sales)->post(route('rfq.store'), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'need_date'           => '2026-10-15',
            'type'                => 'Non Projek',
            'priority'            => 'Urgent',
            'notes'               => 'Mohon diproses untuk pengadaan access door',
            'items'               => [
                [
                    'product_name' => 'ZKTeco SenseFace 2A (Face, Finger, Mifare Card)',
                    'qty'          => 1,
                    'unit'         => 'unit',
                    'description'  => 'Mesin absensi dan akses kontrol pintu ZKTeco',
                ],
            ],
        ]);
        $storeResp->assertSessionHas('success');

        $rfq = Rfq::first();
        $this->assertNotNull($rfq);
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status);
        $this->assertEquals('Non Projek', $rfq->type);

        // ── STEP 2: Sales / Leader Mengubah RFQ dari Non Projek ke Projek ─────
        $updateResp = $this->actingAs($this->sales)->put(route('rfq.update', $rfq), [
            'customer_id'         => $this->customer->id,
            'customer_contact_id' => $this->contact->id,
            'need_date'           => '2026-10-15',
            'type'                => 'Projek', // Berhasil switch ke Projek!
            'priority'            => 'High Priority',
            'notes'               => 'Diubah menjadi paket Projek Access Door Karawang',
            'items'               => [
                [
                    'category'     => 'Hardware',
                    'product_name' => 'ZKTeco SenseFace 2A (Face, Finger, Mifare Card)',
                    'qty'          => 1,
                    'unit'         => 'unit',
                    'description'  => 'Mesin absensi dan akses kontrol pintu ZKTeco lengkap',
                ],
                [
                    'category'     => 'Jasa Pemasangan',
                    'product_name' => 'Instalasi Access Door & Penarikan Kabel',
                    'qty'          => 1,
                    'unit'         => 'lot',
                    'description'  => 'Jasa pasang dan testing oleh teknisi',
                ],
            ],
        ]);
        $updateResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals('Projek', $rfq->type);
        $this->assertCount(2, $rfq->items);

        // ── STEP 3: Admin Purchase Menghitung HPP (Exclude PPN & Ketik Vendor) ──
        $item1 = $rfq->items()->where('product_name', 'like', '%ZKTeco%')->first();
        $item2 = $rfq->items()->where('product_name', 'like', '%Instalasi%')->first();

        // Vendor ketik langsung: "PT Solusi Akses Utama"
        // HPP ZKTeco dihitung Exclude PPN (2.185.440 / 1.11 = 1.968.865)
        // Ongkir Pedia = 100.000, Biaya Kirim = 256.500
        // Base Modal = 1.968.865 + 100.000 + 256.500 = 2.325.365
        // Margin 50% + ceiling 50.000 -> 3.500.000
        $priceResp = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => "- Seluruh unit access door ready stock siap pasang\n- Garansi resmi 1 tahun",
            'items' => [
                $item1->id => [
                    'category'       => 'Hardware',
                    'product_name'   => $item1->product_name,
                    'qty'            => 1,
                    'unit'           => 'unit',
                    'description'    => $item1->description,
                    'vendor_name'    => 'PT Solusi Akses Utama', // Otomatis dibuat & di-link!
                    'hpp'            => 1968865,
                    'ongkir_pedia'   => 100000,
                    'biaya_kirim'    => 256500,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 50,
                    'custom_ceiling' => 50000,
                    'validity_days'  => 14,
                ],
                $item2->id => [
                    'category'       => 'Jasa Pemasangan',
                    'product_name'   => $item2->product_name,
                    'qty'            => 1,
                    'unit'           => 'lot',
                    'description'    => $item2->description,
                    'vendor_name'    => 'Mainpower Pedia',
                    'hpp'            => 1300000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 30,
                    'custom_ceiling' => 1000,
                    'validity_days'  => 14,
                ],
            ],
        ]);
        $priceResp->assertSessionHas('success');

        $rfq->refresh();
        $item1->refresh();
        $item2->refresh();

        // Verifikasi Vendor baru otomatis terdaftar di database
        $newVendor = Vendor::where('nama_vendor', 'PT Solusi Akses Utama')->first();
        $this->assertNotNull($newVendor);
        $this->assertEquals($newVendor->id, $item1->vendor_id);

        // Verifikasi Formula Harga Satuan
        $this->assertEquals(3500000, (float) $item1->price_after_margin);
        // Modal Jasa 1.300.000 x 1.3 = 1.690.000
        $this->assertEquals(1690000, (float) $item2->price_after_margin);
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);

        // ── STEP 4: Leader Laras Menguji Alur Revisi & Penolakan ───────────────
        $rejectResp = $this->actingAs($this->leader)->post(route('rfq.reject', $rfq), [
            'revision_notes' => 'Tolong turunkan margin jasa instalasi menjadi 20%',
        ]);
        $rejectResp->assertSessionHas('info', fn($msg) => true);
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status);
        $this->assertEquals('Tolong turunkan margin jasa instalasi menjadi 20%', $rfq->revision_notes);

        // Admin menyesuaikan harga sesuai permintaan Leader
        $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'items' => [
                $item1->id => [
                    'category'       => 'Hardware',
                    'product_name'   => $item1->product_name,
                    'qty'            => 1,
                    'unit'           => 'unit',
                    'description'    => $item1->description,
                    'vendor_name'    => 'PT Solusi Akses Utama',
                    'hpp'            => 1968865,
                    'ongkir_pedia'   => 100000,
                    'biaya_kirim'    => 256500,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 50,
                    'custom_ceiling' => 50000,
                    'validity_days'  => 14,
                ],
                $item2->id => [
                    'category'       => 'Jasa Pemasangan',
                    'product_name'   => $item2->product_name,
                    'qty'            => 1,
                    'unit'           => 'lot',
                    'description'    => $item2->description,
                    'vendor_name'    => 'Mainpower Pedia',
                    'hpp'            => 1300000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20, // Margin disesuaikan 20%
                    'custom_ceiling' => 1000,
                    'validity_days'  => 14,
                ],
            ],
        ]);
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);

        // ── STEP 5: Leader Approve HPP & Uji Idempotensi (Double Click) ───────
        // Klik 1: Sukses approve
        $appr1 = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $appr1->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        // Klik 2: Double click milidetik berikutnya -> TIDAK BOLEH 403, harus redirect dengan info!
        $appr2 = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $appr2->assertSessionHas('info');
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->fresh()->status);

        // ── STEP 6: Sales Download PDF & Preview Quotation (Pemeriksaan PPN) ───
        $previewResp = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $previewResp->assertStatus(200);

        // Subtotal = 3.500.000 + 1.560.000 = 5.060.000
        // PPN 11% = 5.060.000 * 0.11 = 556.600
        // Grand Total = 5.616.600
        $previewResp->assertSee('5.060.000');
        $previewResp->assertSee('556.600');
        $previewResp->assertSee('5.616.600');
        $previewResp->assertSee('ready stock');

        $pdfResp = $this->actingAs($this->sales)->get(route('rfq.download_quotation', $rfq));
        $pdfResp->assertStatus(200);
        $pdfResp->assertHeader('Content-Type', 'application/pdf');

        // Tandai Quotation Sent
        $sentResp = $this->actingAs($this->sales)->post(route('rfq.mark_quotation_sent', $rfq));
        $sentResp->assertSessionHas('success');
        $this->assertEquals(Rfq::STATUS_QUOTATION_SENT, $rfq->fresh()->status);

        // ── STEP 7: Sales Upload Bukti PO Customer ────────────────────────────
        $fakeFile = UploadedFile::fake()->create('po_customer_ksm.pdf', 300, 'application/pdf');
        $uploadResp = $this->actingAs($this->sales)->post(route('rfq.upload_po', $rfq), [
            'po_file' => $fakeFile,
        ]);
        $uploadResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PO_PENDING_ADMIN, $rfq->status);
        $this->assertNotNull($rfq->po_file_path);

        // ── STEP 8: Admin Purchase Verifikasi PO ──────────────────────────────
        // Klik 1: Verifikasi sukses
        $verify1 = $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq));
        $verify1->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PO_PENDING_LEADER, $rfq->status);

        // Klik 2: Double submit verifikasi -> Idempoten, redirect info
        $verify2 = $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq));
        $verify2->assertSessionHas('info');

        // ── STEP 9: Super Admin Syarwani Menyetujui Menjadi GOAL ───────────────
        // Super Admin Syarwani memiliki hak mengeksekusi approveGoal
        $goalResp1 = $this->actingAs($this->syarwani)->post(route('rfq.approve_goal', $rfq));
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->status);

        // Periksa Purchase Order resmi otomatis tercipta
        $po = PurchaseOrder::where('rfq_id', $rfq->id)->first();
        $this->assertNotNull($po);
        $this->assertEquals(PurchaseOrder::STATUS_GOAL, $po->status);
        $this->assertEquals(5060000, (float) $po->grand_total);
        $this->assertCount(2, $po->items);

        // Klik 2: Double click approveGoal -> Idempoten, redirect info
        $goalResp2 = $this->actingAs($this->syarwani)->post(route('rfq.approve_goal', $rfq));
        $goalResp2->assertSessionHas('info');
        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->fresh()->status);
    }

    /**
     * SIMULASI 3: Pengujian Keamanan & Indikator Batas Role (Security Boundaries)
     */
    public function test_security_boundaries_and_error_handling(): void
    {
        $rfq = Rfq::create([
            'rfq_number'          => 'RFQ-SEC-001',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->format('Y-m-d'),
        ]);

        // 1. Indikasi: Sales mencoba menyetujui RFQ sendiri (HPP) -> WAJIB 403
        $salesApprove = $this->actingAs($this->sales)->post(route('rfq.approve', $rfq));
        $this->assertEquals(403, $salesApprove->status());

        // 2. Indikasi: Sales mencoba reject RFQ -> WAJIB 403
        $salesReject = $this->actingAs($this->sales)->post(route('rfq.reject', $rfq), ['revision_notes' => 'Hack']);
        $this->assertEquals(403, $salesReject->status());

        // 3. Indikasi: Download quotation saat status masih Pending Admin -> WAJIB 403
        $earlyDownload = $this->actingAs($this->sales)->get(route('rfq.download_quotation', $rfq));
        $this->assertEquals(403, $earlyDownload->status());

        // 4. Indikasi: Admin mencoba approve GOAL tanpa hak Leader/Super Admin -> WAJIB 403
        $rfq->update(['status' => Rfq::STATUS_PO_PENDING_LEADER]);
        $adminGoal = $this->actingAs($this->admin)->post(route('rfq.approve_goal', $rfq));
        $this->assertEquals(403, $adminGoal->status());

        // 5. Indikasi: Guest (tidak login) mencoba akses HPP form -> Redirect ke Login
        auth()->logout();
        $guestResp = $this->get(route('rfq.price_form', $rfq));
        $guestResp->assertRedirect(route('login'));
    }
}
