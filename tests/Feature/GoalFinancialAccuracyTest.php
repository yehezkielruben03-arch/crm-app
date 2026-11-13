<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
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

class GoalFinancialAccuracyTest extends TestCase
{
    use RefreshDatabase;

    private User $sales;
    private User $admin;
    private User $leader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');

        $this->sales = User::factory()->create([
            'name'     => 'Sales Tester',
            'username' => 'sales_tester',
            'email'    => 'sales_tester@example.com',
            'password' => Hash::make('password'),
            'role'     => 'Sales Marketing',
            'status'   => 'Active',
        ]);

        $this->admin = User::factory()->create([
            'name'     => 'Admin Tester',
            'username' => 'admin_tester',
            'email'    => 'admin_tester@example.com',
            'password' => Hash::make('password'),
            'role'     => 'Admin',
            'status'   => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'name'     => 'Leader Tester',
            'username' => 'leader_tester',
            'email'    => 'leader_tester@example.com',
            'password' => Hash::make('password'),
            'role'     => 'Leader',
            'status'   => 'Active',
        ]);
    }

    public function test_pc_rakitan_bundle_does_not_double_count_when_approved_goal(): void
    {
        $customer = Customer::factory()->create([
            'company_name'  => 'PT Solusi Komputasi Indonesia',
            'customer_type' => 'PT',
            'sales_id'      => $this->sales->id,
            'status'        => 'Active',
        ]);

        $contact = CustomerContact::factory()->create([
            'customer_id' => $customer->id,
            'name'        => 'Pak Andi',
            'phone'       => '081122334455',
        ]);

        $rfq = Rfq::create([
            'rfq_number'          => '261113-9001',
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_contact_id' => $contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'rfq_date'            => now()->toDateString(),
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'tax_type'            => 'exclude',
        ]);

        // 1 Parent Bundle item: Laptop Lenovo V15
        $bundleParent = RfqItem::create([
            'rfq_id'             => $rfq->id,
            'parent_id'          => null,
            'is_bundle'          => true,
            'category'           => 'Hardware',
            'product_name'       => 'Laptop Lenovo V15 G5 IRL',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 15000000,
            'price_after_margin' => 18400000,
            'margin_type'        => 'percentage',
            'margin_value'       => 20,
            'validity_days'      => 7,
        ]);

        // 3 Child components with parent_id = $bundleParent->id
        $comp1 = RfqItem::create([
            'rfq_id'             => $rfq->id,
            'parent_id'          => $bundleParent->id,
            'is_bundle'          => false,
            'show_in_quotation'  => true,
            'category'           => 'Hardware',
            'product_name'       => 'Laptop Lenovo V15 Unit Fisik',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 10000000,
            'price_after_margin' => 12000000,
            'margin_type'        => 'percentage',
            'margin_value'       => 20,
            'validity_days'      => 7,
        ]);

        $comp2 = RfqItem::create([
            'rfq_id'             => $rfq->id,
            'parent_id'          => $bundleParent->id,
            'is_bundle'          => false,
            'show_in_quotation'  => true,
            'category'           => 'Hardware',
            'product_name'       => 'Tambah RAM 16GB',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 2500000,
            'price_after_margin' => 3200000,
            'margin_type'        => 'percentage',
            'margin_value'       => 20,
            'validity_days'      => 7,
        ]);

        $comp3 = RfqItem::create([
            'rfq_id'             => $rfq->id,
            'parent_id'          => $bundleParent->id,
            'is_bundle'          => false,
            'show_in_quotation'  => true,
            'category'           => 'Hardware',
            'product_name'       => 'Upgrade Windows 11 Pro',
            'qty'                => 1,
            'unit'               => 'Unit',
            'hpp'                => 2500000,
            'price_after_margin' => 3200000,
            'margin_type'        => 'percentage',
            'margin_value'       => 20,
            'validity_days'      => 7,
        ]);

        // Advance to PO Pending Leader
        $rfq->update(['status' => Rfq::STATUS_APPROVED]);
        $poPdf = UploadedFile::fake()->create('po_client.pdf', 80, 'application/pdf');
        $this->actingAs($this->sales)->post(route('rfq.upload_po', $rfq), ['po_file' => $poPdf])->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq))->assertSessionHas('success');

        // Leader approves GOAL
        $this->actingAs($this->leader)->post(route('rfq.approve_goal', $rfq))->assertSessionHas('success');

        $rfq->refresh();
        $this->assertEquals(Rfq::STATUS_GOAL, $rfq->status);

        $po = PurchaseOrder::where('rfq_id', $rfq->id)->firstOrFail();
        $this->assertEquals(PurchaseOrder::STATUS_GOAL, $po->status);

        // Verification 1: Exactly 1 item in PO (the parent package), NOT 4 items!
        $this->assertCount(1, $po->items);
        $this->assertEquals('Laptop Lenovo V15 G5 IRL', $po->items->first()->item_name);

        // Verification 2: Subtotal is exactly 18.400.000, NOT 36.800.000 (No double counting!)
        $this->assertEquals(18400000, (float) $po->subtotal);

        // Verification 3: Tax is 11% PPN = 2.024.000
        $this->assertEquals(2024000, (float) $po->tax_amount);

        // Verification 4: Grand total is exactly 20.424.000
        $this->assertEquals(20424000, (float) $po->grand_total);
    }

    public function test_include_tax_perorangan_sets_zero_tax_amount_and_matches_grand_total(): void
    {
        $customer = Customer::factory()->create([
            'company_name'  => 'Budi Santoso, Perorangan',
            'customer_type' => 'Perorangan',
            'sales_id'      => $this->sales->id,
            'status'        => 'Active',
        ]);

        $contact = CustomerContact::factory()->create([
            'customer_id' => $customer->id,
            'name'        => 'Budi Santoso',
            'phone'       => '081299887766',
        ]);

        $rfq = Rfq::create([
            'rfq_number'          => '261113-9002',
            'customer_id'         => $customer->id,
            'customer_name'       => $customer->company_name,
            'customer_contact_id' => $contact->id,
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'rfq_date'            => now()->toDateString(),
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'type'                => 'Non Projek',
            'tax_type'            => 'include',
        ]);

        $item = RfqItem::create([
            'rfq_id'             => $rfq->id,
            'parent_id'          => null,
            'is_bundle'          => false,
            'category'           => 'Hardware',
            'product_name'       => 'Monitor 24 Inch LG',
            'qty'                => 2,
            'unit'               => 'Unit',
            'hpp'                => 1500000,
            'price_after_margin' => 2000000, // 2 x 2.000.000 = 4.000.000
            'margin_type'        => 'percentage',
            'margin_value'       => 25,
            'validity_days'      => 7,
        ]);

        $rfq->update(['status' => Rfq::STATUS_APPROVED]);
        $poPdf = UploadedFile::fake()->create('po_budi.pdf', 60, 'application/pdf');
        $this->actingAs($this->sales)->post(route('rfq.upload_po', $rfq), ['po_file' => $poPdf])->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('rfq.verify_po', $rfq))->assertSessionHas('success');
        $this->actingAs($this->leader)->post(route('rfq.approve_goal', $rfq))->assertSessionHas('success');

        $po = PurchaseOrder::where('rfq_id', $rfq->id)->firstOrFail();

        $this->assertEquals(4000000, (float) $po->subtotal);
        $this->assertEquals(0, (float) $po->tax_amount); // Include PPN -> 0 separate tax
        $this->assertEquals(4000000, (float) $po->grand_total);
    }
}
