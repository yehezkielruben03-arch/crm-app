<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\RfqPriceHistory;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RfqRevisionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $leader;
    private User $sales;
    private Customer $customer;
    private CustomerContact $contact;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::create([
            'name'     => 'Admin Purchase User',
            'username' => 'admin_purchase',
            'email'    => 'admin_purchase@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Admin Purchase',
            'status'   => 'Active',
            'phone'    => '081234567890',
        ]);

        $this->leader = User::create([
            'name'     => 'Leader User',
            'username' => 'leader_user',
            'email'    => 'leader_user@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Leader',
            'status'   => 'Active',
            'phone'    => '081234567891',
        ]);

        $this->sales = User::create([
            'name'     => 'Sales User',
            'username' => 'sales_user',
            'email'    => 'sales_user@crm.com',
            'password' => Hash::make('password123'),
            'role'     => 'Sales',
            'status'   => 'Active',
            'phone'    => '081234567892',
        ]);

        $this->customer = Customer::create([
            'company_name' => 'PT Mitra Sejati',
            'email'        => 'contact@mitrasejati.com',
            'status'       => 'Active',
            'sales_id'     => $this->sales->id,
            'address'      => 'Jl. Sudirman Kav 1',
        ]);

        $this->contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name'        => 'Bpk Hendra',
            'phone'       => '0811223344',
            'is_primary'  => true,
        ]);
    }

    public function test_price_edits_before_approval_show_revision_only_after_leader_approves(): void
    {
        // 1. Sales buat RFQ awal
        $rfq = Rfq::create([
            'rfq_number'          => 'RFQ-REV-TEST-001',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->format('Y-m-d'),
        ]);

        $item = $rfq->items()->create([
            'category'           => null,
            'product_name'       => 'CCTV Camera',
            'qty'                => 5,
            'unit'               => 'Unit',
            'hpp'                => 0,
            'margin'             => 25,
            'price_after_margin' => 0,
        ]);

        // 2. Admin input harga pertama kali (Draf 1)
        $resp1 = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Catatan draf 1',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 5,
                    'unit'           => 'Unit',
                    'hpp'            => 500000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 10000,
                    'validity_days'  => 14,
                ],
            ],
        ]);
        $resp1->assertSessionHas('success');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
        $this->assertEquals(1, RfqPriceHistory::where('rfq_id', $rfq->id)->count());

        // Verifikasi pada halaman show: sebelum approve, TIDAK BOLEH tampil Revisi
        $showResp1 = $this->actingAs($this->sales)->get(route('rfq.show', $rfq));
        $showResp1->assertStatus(200);
        $this->assertEquals(0, $showResp1->viewData('revisionCount'));
        $showResp1->assertSee('Draf (Menunggu Approval)');

        // 3. Admin mengedit harga KEDUA KALI sebelum disetujui Leader (Status masih Pending Leader)
        $resp2 = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Catatan draf revisi internal admin',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 5,
                    'unit'           => 'Unit',
                    'hpp'            => 520000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 10000,
                    'validity_days'  => 14,
                ],
            ],
        ]);
        $resp2->assertSessionHas('success');

        $rfq->refresh();
        // Sekarang tersimpan 2 snapshot di history
        $this->assertEquals(2, RfqPriceHistory::where('rfq_id', $rfq->id)->count());

        // SEBELUM DI-APPROVE: revisionCount tetap 0, belum muncul Revisi 1
        $showResp2 = $this->actingAs($this->sales)->get(route('rfq.show', $rfq));
        $showResp2->assertStatus(200);
        $this->assertEquals(0, $showResp2->viewData('revisionCount'));
        $showResp2->assertSee('Draf (Menunggu Approval)');

        // 4. Leader menyetujui (Approve)
        $approveResp = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $approveResp->assertSessionHas('success');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        // SETELAH DI-APPROVE: Keterangan REVISI I resmi muncul!
        $showRespApproved = $this->actingAs($this->sales)->get(route('rfq.show', $rfq));
        $showRespApproved->assertStatus(200);
        $this->assertEquals(1, $showRespApproved->viewData('revisionCount'));
        $showRespApproved->assertSee('Revisi 1');

        $previewResp = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $previewResp->assertStatus(200);
        $previewResp->assertSee('REVISI I');
        $this->assertEquals(1, $previewResp->viewData('revisionCount'));

        // Verifikasi template totals table mengikuti sampling
        $previewResp->assertSee('5 Unit');
        $previewResp->assertSee('TOTAL');
        $previewResp->assertSee('PPn');
        $previewResp->assertSee('TOTAL HARGA');
    }

    public function test_single_submission_without_pre_approval_edit_has_no_revision_after_approve(): void
    {
        $rfq = Rfq::create([
            'rfq_number'          => 'RFQ-REV-TEST-002',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->format('Y-m-d'),
        ]);

        $item = $rfq->items()->create([
            'product_name'       => 'Scanner Barcode',
            'qty'                => 2,
            'unit'               => 'Unit',
            'hpp'                => 0,
            'margin'             => 25,
            'price_after_margin' => 0,
        ]);

        // Input harga sekali saja
        $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Tanpa edit sebelum approve',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 2,
                    'unit'           => 'Unit',
                    'hpp'            => 750000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 10000,
                    'validity_days'  => 14,
                ],
            ],
        ]);

        // Leader approve
        $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));

        $previewResp = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $previewResp->assertStatus(200);
        $previewResp->assertDontSee('REVISI I');
        $this->assertEquals(0, $previewResp->viewData('revisionCount'));
    }
}
