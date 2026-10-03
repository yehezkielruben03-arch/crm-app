<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\RfqPriceHistory;
use App\Models\User;
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
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

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

    public function test_price_edits_before_leader_approval_do_not_increment_revision_version(): void
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
            'unit'               => 'unit',
            'hpp'                => 0,
            'margin'             => 25,
            'price_after_margin' => 0,
        ]);

        $this->assertEquals(0, RfqPriceHistory::where('rfq_id', $rfq->id)->count());

        // 2. Admin input harga pertama kali
        $responseSubmit1 = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Catatan draf 1',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 5,
                    'unit'           => 'unit',
                    'hpp'            => 500000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 10000,
                ],
            ],
        ]);
        $responseSubmit1->assertSessionHas('success');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
        $this->assertEquals(1, RfqPriceHistory::where('rfq_id', $rfq->id)->count());
        $history1 = RfqPriceHistory::where('rfq_id', $rfq->id)->first();
        $this->assertEquals(1, $history1->version);
        $this->assertNull($history1->approved_at);

        // 3. Admin mengedit harga KEDUA KALI sebelum disetujui Leader (Status masih Pending Leader)
        $responseSubmit2 = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Catatan draf revisi internal admin',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 5,
                    'unit'           => 'unit',
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
        $responseSubmit2->assertSessionHas('success');

        $rfq->refresh();
        // Verifikasi: History tetap 1 baris, versi tetap 1 (tidak melompat ke versi 2 / Rev 1)
        $this->assertEquals(1, RfqPriceHistory::where('rfq_id', $rfq->id)->count());
        $historyUpdated = RfqPriceHistory::where('rfq_id', $rfq->id)->first();
        $this->assertEquals(1, $historyUpdated->version);
        $this->assertNull($historyUpdated->approved_at);

        // 4. Leader menolak dan mengembalikan ke Admin (Reject)
        $rejectResp = $this->actingAs($this->leader)->post(route('rfq.reject', $rfq), [
            'revision_notes' => 'Tolong HPP disesuaikan diskon distributor',
        ]);
        $rejectResp->assertSessionHas('info', fn($msg) => true);
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status);

        // 5. Admin memperbaiki harga ketiga kali setelah reject (masih pra-approval)
        $responseSubmit3 = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Draf penyesuaian diskon distributor',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 5,
                    'unit'           => 'unit',
                    'hpp'            => 480000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'fee_eu'         => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 25,
                    'custom_ceiling' => 10000,
                    'validity_days'  => 14,
                ],
            ],
        ]);
        $responseSubmit3->assertSessionHas('success');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
        // Tetap hanya 1 history dan tetap Versi 1
        $this->assertEquals(1, RfqPriceHistory::where('rfq_id', $rfq->id)->count());
        $historyBeforeApprove = RfqPriceHistory::where('rfq_id', $rfq->id)->first();
        $this->assertEquals(1, $historyBeforeApprove->version);

        // 6. Leader menyetujui (Approve)
        $approveResp = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $approveResp->assertSessionHas('success');
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_APPROVED, $rfq->status);

        $historyApproved = RfqPriceHistory::where('rfq_id', $rfq->id)->first();
        $this->assertNotNull($historyApproved->approved_at);

        // 7. Verifikasi Preview Quotation: TIDAK BOLEH muncul tulisan REVISI I
        $previewResp = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $previewResp->assertStatus(200);
        $previewResp->assertDontSee('REVISI I');
        $this->assertEquals(0, $previewResp->viewData('revisionCount'));

        // 8. Klien meminta diskon / revisi harga setelah disetujui (Pasca-Approval)
        // Admin melakukan revisi harga baru
        $responseRevise = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Diskon khusus klien deal',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 5,
                    'unit'           => 'unit',
                    'hpp'            => 480000,
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
        $responseRevise->assertSessionHas('success');

        $rfq->refresh();
        // Sekarang resmi menjadi 2 versi: Versi 1 (Approved) dan Versi 2 (Revisi baru, unapproved)
        $this->assertEquals(2, RfqPriceHistory::where('rfq_id', $rfq->id)->count());
        $latestHistory = RfqPriceHistory::where('rfq_id', $rfq->id)->orderByDesc('version')->first();
        $this->assertEquals(2, $latestHistory->version);
        $this->assertNull($latestHistory->approved_at);

        // 9. Admin edit revisi 2 sebelum Leader menyetujui revisi 2
        $responseEditRev2 = $this->actingAs($this->admin)->post(route('rfq.submit_price', $rfq), [
            'notes' => 'Perbaikan draf revisi 2',
            'items' => [
                $item->id => [
                    'product_name'   => $item->product_name,
                    'qty'            => 5,
                    'unit'           => 'unit',
                    'hpp'            => 475000,
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
        $responseEditRev2->assertSessionHas('success');

        // Jumlah versi tetap 2, tidak naik ke versi 3
        $this->assertEquals(2, RfqPriceHistory::where('rfq_id', $rfq->id)->count());

        // 10. Leader menyetujui Revisi 2
        $approveRev2Resp = $this->actingAs($this->leader)->post(route('rfq.approve', $rfq));
        $approveRev2Resp->assertSessionHas('success');

        $rfq->refresh();
        $latestHistory->refresh();
        $this->assertNotNull($latestHistory->approved_at);

        // 11. Verifikasi Preview Quotation sekarang WAJIB menampilkan REVISI I
        $previewRev2Resp = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $rfq));
        $previewRev2Resp->assertStatus(200);
        $previewRev2Resp->assertSee('REVISI I');
        $this->assertEquals(1, $previewRev2Resp->viewData('revisionCount'));
    }
}
