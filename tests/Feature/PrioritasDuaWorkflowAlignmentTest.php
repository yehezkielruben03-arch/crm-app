<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PrioritasDuaWorkflowAlignmentTest extends TestCase
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

        $this->admin = User::factory()->create([
            'name'     => 'Admin Test',
            'username' => 'admin_test',
            'email'    => 'admin_test@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Admin',
            'status'   => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'name'     => 'Leader Test',
            'username' => 'leader_test',
            'email'    => 'leader_test@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Leader',
            'status'   => 'Active',
        ]);

        $this->sales = User::factory()->create([
            'name'     => 'Sales Test',
            'username' => 'sales_test',
            'email'    => 'sales_test@test.com',
            'password' => Hash::make('password'),
            'role'     => 'Sales Marketing',
            'status'   => 'Active',
        ]);

        $this->customer = Customer::create([
            'company_name' => 'PT Mitra Sejati',
            'status'       => Customer::STATUS_ACTIVE,
            'sales_id'     => $this->sales->id,
            'created_by'   => $this->admin->id,
            'company_code' => 'MTR-01',
        ]);

        $this->contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name'        => 'Bapak Budi',
            'phone'       => '08123456789',
            'email'       => 'budi@mitra.com',
            'position'    => 'Procurement',
        ]);
    }

    /**
     * Test 1: Leader melihat RFQ berstatus Pending Leader dan Pending PO Leader di /approvals
     */
    public function test_leader_sees_both_pending_leader_and_pending_po_leader_in_approvals(): void
    {
        // RFQ 1: Draf awal menunggu Leader approve HPP
        $rfqDraft = Rfq::create([
            'rfq_number'          => '261114-0001',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PENDING_LEADER,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->toDateString(),
        ]);
        $rfqDraft->items()->create([
            'product_name'       => 'Server Dell R740',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 20000000,
            'margin'             => 20,
            'price_after_margin' => 24000000,
        ]);

        // RFQ 2: PO Klien masuk, menunggu Leader approve GOAL
        $rfqPo = Rfq::create([
            'rfq_number'          => '261114-0002',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PO_PENDING_LEADER,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->toDateString(),
        ]);
        $rfqPo->items()->create([
            'product_name'       => 'Switch Cisco 24 Port',
            'qty'                => 2,
            'unit'               => 'Unit',
            'hpp'                => 5000000,
            'margin'             => 15,
            'price_after_margin' => 5750000,
        ]);

        $response = $this->actingAs($this->leader)->get(route('approvals.index'));
        $response->assertOk();

        // RFQ 1 harus terlihat lengkap dengan tombol Approve HPP
        $response->assertSee('261114-0001');
        $response->assertSee('Edit HPP');

        // RFQ 2 (PO Klien) harus terlihat lengkap dengan badge dan tombol Approve GOAL
        $response->assertSee('261114-0002');
        $response->assertSee('PO Klien (Siap GOAL)');
        $response->assertSee('Approve GOAL');
    }

    /**
     * Test 2: Dashboard menghitung status STATUS_PO_PENDING_ADMIN dan STATUS_PO_PENDING_LEADER secara akurat
     */
    public function test_dashboard_pending_counters_include_po_pending_statuses(): void
    {
        // 1 RFQ Pending Admin biasa
        Rfq::create([
            'rfq_number'          => '261114-0010',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->toDateString(),
        ]);

        // 1 RFQ PO Pending Admin (PO Klien perlu diverifikasi Admin)
        Rfq::create([
            'rfq_number'          => '261114-0011',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PO_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->toDateString(),
        ]);

        // 1 RFQ PO Pending Leader (Siap di-GOAL-kan Leader)
        Rfq::create([
            'rfq_number'          => '261114-0012',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PO_PENDING_LEADER,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();

        // Admin pending harus berjumlah 2 (1 Pending Admin + 1 PO Pending Admin)
        $this->assertEquals(2, $response->viewData('rfqPendingAdmin'));

        // Leader pending harus berjumlah 1 (1 PO Pending Leader)
        $this->assertEquals(1, $response->viewData('rfqPendingLeader'));
        $this->assertEquals(3, $response->viewData('totalRfqPending'));
    }

    /**
     * Test 3: Menu Quotation mengarahkan ke RFQ berstatus quotation resmi
     */
    public function test_quotations_index_redirects_to_rfq_index_with_quotation_filter(): void
    {
        $response = $this->actingAs($this->sales)->get(route('quo.index'));
        $response->assertRedirect(route('rfq.index', ['status' => 'quotation']));

        // Kunjungi rfq.index dengan status=quotation
        $rfqQuo = Rfq::create([
            'rfq_number'          => '261114-0020',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_QUOTATION_SENT,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->toDateString(),
        ]);

        $rfqDraft = Rfq::create([
            'rfq_number'          => '261114-0021',
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_contact_id' => $this->contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'rfq_date'            => now()->toDateString(),
        ]);

        $quoPageResponse = $this->actingAs($this->sales)->get(route('rfq.index', ['status' => 'quotation']));
        $quoPageResponse->assertOk();
        $returnedRfqs = $quoPageResponse->viewData('rfqs');
        $this->assertNotEmpty($returnedRfqs, 'RFQs collection should not be empty');
        $this->assertEquals(1, $returnedRfqs->total());
        $quoPageResponse->assertSee($rfqQuo->quotation_number);
        $quoPageResponse->assertDontSee('261114-0021');
    }
}
