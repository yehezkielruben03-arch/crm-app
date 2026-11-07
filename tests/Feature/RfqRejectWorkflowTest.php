<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RfqRejectWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $leader;
    protected User $admin;
    protected User $sales;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        $this->leader = User::create([
            'name'     => 'Leader Test',
            'username' => 'leadertest',
            'email'    => 'leader@crm.test',
            'password' => Hash::make('password'),
            'role'     => 'Leader',
            'status'   => 'Active',
        ]);

        $this->admin = User::create([
            'name'     => 'Admin Test',
            'username' => 'admintest',
            'email'    => 'admin@crm.test',
            'password' => Hash::make('password'),
            'role'     => 'Admin Purchase',
            'status'   => 'Active',
        ]);

        $this->sales = User::create([
            'name'     => 'Sales Test',
            'username' => 'salestest',
            'email'    => 'sales@crm.test',
            'password' => Hash::make('password'),
            'role'     => 'Sales',
            'status'   => 'Active',
        ]);

        $this->customer = Customer::create([
            'company_name' => 'PT Mitra Penolakan',
            'brand_name'   => 'Mitra Tolak',
            'sales_id'     => $this->sales->id,
            'status'       => Customer::STATUS_ACTIVE,
        ]);
    }

    private function createPendingLeaderRfq(): Rfq
    {
        $rfq = Rfq::create([
            'rfq_number'    => 'RFQ-TEST-REJECT-' . uniqid(),
            'customer_id'   => $this->customer->id,
            'customer_name' => $this->customer->company_name,
            'sales_id'      => $this->sales->id,
            'sales_name'    => $this->sales->name,
            'rfq_date'      => now(),
            'status'        => Rfq::STATUS_PENDING_LEADER,
        ]);

        RfqItem::create([
            'rfq_id'             => $rfq->id,
            'product_name'       => 'Item Uji Reject',
            'qty'                => 2,
            'unit'               => 'Pcs',
            'hpp'                => 1000000,
            'margin_type'        => 'percentage',
            'margin_value'       => 15,
            'price_after_margin' => 1150000,
        ]);

        return $rfq;
    }

    public function test_leader_can_return_rfq_to_admin_with_revision_notes(): void
    {
        $rfq = $this->createPendingLeaderRfq();

        $response = $this->actingAs($this->leader)
            ->from(route('rfq.show', $rfq))
            ->post(route('rfq.reject', $rfq), [
                'action_type'    => 'revision',
                'revision_notes' => 'Margin 15% terlalu rendah, mohon naikkan ke minimal 25%',
            ]);

        $response->assertRedirect(route('rfq.index'));
        $response->assertSessionHas('warning');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_ADMIN, $rfq->status);
        $this->assertEquals('Margin 15% terlalu rendah, mohon naikkan ke minimal 25%', $rfq->revision_notes);
        $this->assertEquals($this->leader->id, $rfq->revision_by);
    }

    public function test_leader_can_cancel_rfq_permanently(): void
    {
        $rfq = $this->createPendingLeaderRfq();

        $response = $this->actingAs($this->leader)
            ->from(route('rfq.show', $rfq))
            ->post(route('rfq.reject', $rfq), [
                'action_type'    => 'cancel',
                'revision_notes' => 'Proyek dibatalkan karena spesifikasi produk diskontinu dari pabrik',
            ]);

        $response->assertRedirect(route('rfq.index'));
        $response->assertSessionHas('warning');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_CANCELLED, $rfq->status);
        $this->assertTrue($rfq->isCancelled());
        $this->assertEquals('Proyek dibatalkan karena spesifikasi produk diskontinu dari pabrik', $rfq->revision_notes);
    }

    public function test_leader_reject_from_approvals_page_redirects_to_approvals(): void
    {
        $rfq = $this->createPendingLeaderRfq();

        $response = $this->actingAs($this->leader)
            ->from(route('approvals.index'))
            ->post(route('rfq.reject', $rfq), [
                'action_type'    => 'cancel',
                'revision_notes' => 'Ditolak langsung dari antrean persetujuan',
            ]);

        $response->assertRedirect(route('approvals.index'));
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_CANCELLED, $rfq->status);
    }

    public function test_sales_user_cannot_reject_rfq(): void
    {
        $rfq = $this->createPendingLeaderRfq();

        $response = $this->actingAs($this->sales)
            ->post(route('rfq.reject', $rfq), [
                'action_type'    => 'cancel',
                'revision_notes' => 'Hacker attempt',
            ]);

        $response->assertStatus(403);
        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_PENDING_LEADER, $rfq->status);
    }
}
