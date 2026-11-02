<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Notification;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RfqAdminNoteTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $leader;
    protected User $sales;
    protected Customer $customer;
    protected Rfq $rfq;
    protected RfqItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        if (\App\Models\RolePermission::count() === 0) {
            $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        }

        $this->admin = User::factory()->create([
            'role'   => 'Admin',
            'name'   => 'Admin Purchase Test',
            'status' => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'role'   => 'Leader',
            'name'   => 'Leader Rian Test',
            'status' => 'Active',
        ]);

        $this->sales = User::factory()->create([
            'role'   => 'Sales',
            'name'   => 'Sales Marketing Test',
            'status' => 'Active',
        ]);

        $uniqueKey = uniqid();
        $this->customer = Customer::create([
            'company_name' => 'PT Joyson Safety Systems',
            'company_code' => 'CUST-' . $uniqueKey,
            'email'        => "procurement_{$uniqueKey}@joyson.com",
            'phone'        => '0812345678',
            'address'      => 'Kawasan Industri MM2100',
            'sales_id'     => $this->sales->id,
            'status'       => Customer::STATUS_ACTIVE,
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $this->customer->id,
            'name'        => 'Bapak Andhika',
            'email'       => "andhika_{$uniqueKey}@joyson.com",
            'phone'       => '0812999999',
            'is_primary'  => true,
        ]);

        $this->rfq = Rfq::create([
            'rfq_number'          => 'RFQ-' . $uniqueKey,
            'customer_id'         => $this->customer->id,
            'customer_name'       => $this->customer->company_name,
            'customer_code'       => $this->customer->company_code,
            'customer_contact_id' => $contact->id,
            'type'                => 'Hardware',
            'sales_id'            => $this->sales->id,
            'sales_name'          => $this->sales->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'status'              => Rfq::STATUS_PENDING_ADMIN,
            'notes'               => 'Permintaan Router Cisco C9115AXI',
        ]);

        $this->item = RfqItem::create([
            'rfq_id'             => $this->rfq->id,
            'category'           => 'Hardware',
            'product_name'       => 'C9115AXI-X Cisco Catalyst AP',
            'qty'                => 2,
            'unit'               => 'Unit',
            'hpp'                => 5000000,
            'ongkir_pedia'       => 0,
            'ongkir_pelanggan'   => 0,
            'margin'             => 20,
            'margin_value'       => 20,
            'ceiling'            => 1000,
            'validity_days'      => 7,
            'price_after_margin' => 6000000,
        ]);
    }

    public function test_admin_can_save_admin_note_via_submit_price()
    {
        $noteText = "· C9115AXI-X : Cisco Catalyst 9115AX Series > EOL, ganti type C9115AXI-F\n· AIR-DNA-E : Cisco Wireless DNA > tidak ada, paling pendek 3 tahun\nfile sudah dimasukan ke folder sales";

        $response = $this->actingAs($this->admin)->post(route('rfq.submit_price', $this->rfq), [
            'notes'      => 'Catatan resmi penawaran di PDF',
            'admin_note' => $noteText,
            'items'      => [
                $this->item->id => [
                    'product_name'   => $this->item->product_name,
                    'qty'            => 2,
                    'unit'           => 'Unit',
                    'hpp'            => 5500000,
                    'ongkir_pedia'   => 0,
                    'biaya_kirim'    => 0,
                    'margin_type'    => 'percentage',
                    'margin_value'   => 20,
                    'custom_ceiling' => 1000,
                    'validity_days'  => 7,
                ],
            ],
        ]);

        $response->assertSessionHas('success');

        $this->rfq->refresh();
        $this->assertEquals($noteText, $this->rfq->admin_note);
        $this->assertEquals($this->admin->id, $this->rfq->admin_note_by);
        $this->assertNotNull($this->rfq->admin_note_updated_at);
        $this->assertEquals('Admin Purchase Test', $this->rfq->adminNoteAuthor?->name);
    }

    public function test_admin_and_leader_can_update_admin_note_directly_on_show_page()
    {
        // RFQ can even be in Approved / Quotation Created status
        $this->rfq->update(['status' => Rfq::STATUS_APPROVED]);

        $updatedNote = "· C9115AXI-X ganti C9115AXI-F ready stok 2 unit.";

        $response = $this->actingAs($this->leader)->post(route('rfq.update_admin_note', $this->rfq), [
            'admin_note' => $updatedNote,
        ]);

        $response->assertSessionHas('success');

        $this->rfq->refresh();
        $this->assertEquals($updatedNote, $this->rfq->admin_note);
        $this->assertEquals($this->leader->id, $this->rfq->admin_note_by);
        $this->assertNotNull($this->rfq->admin_note_updated_at);

        // Sales receives notification
        $notification = Notification::where('user_id', $this->sales->id)->latest()->first();
        $this->assertNotNull($notification);
        $this->assertStringContainsString('Catatan Admin', $notification->title);
        $this->assertStringContainsString($this->rfq->rfq_number, $notification->message);
    }

    public function test_update_admin_note_returns_json_for_ajax_request()
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('rfq.update_admin_note', $this->rfq), [
                'admin_note' => 'Catatan via ajax modal',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'admin_note' => 'Catatan via ajax modal',
            ]);

        $this->rfq->refresh();
        $this->assertEquals('Catatan via ajax modal', $this->rfq->admin_note);
    }

    public function test_sales_cannot_update_admin_note()
    {
        $response = $this->actingAs($this->sales)->post(route('rfq.update_admin_note', $this->rfq), [
            'admin_note' => 'Percobaan edit oleh Sales',
        ]);

        $response->assertForbidden();
    }

    public function test_whatsapp_formatted_note_helper()
    {
        $this->rfq->update([
            'admin_note' => "· C9115AXI-X : EOL ganti type C9115AXI-F\n· AIR-DNA-E : min 3 tahun",
        ]);

        $waText = $this->rfq->getWhatsAppFormattedNote();

        $expected = "PT Joyson Safety Systems, Hardware\nnote:\n· C9115AXI-X : EOL ganti type C9115AXI-F\n· AIR-DNA-E : min 3 tahun\nfile sudah dimasukan ke folder sales";

        $this->assertEquals($expected, $waText);

        // Test non-duplication if 'file sudah dimasukan ke folder sales' already present
        $this->rfq->update([
            'admin_note' => "· C9115AXI-X : EOL ganti type C9115AXI-F\nfile sudah dimasukan ke folder sales",
        ]);

        $waText2 = $this->rfq->getWhatsAppFormattedNote();
        $this->assertEquals(1, substr_count(strtolower($waText2), 'file sudah dimasukan ke folder sales'));
    }

    public function test_admin_note_displayed_on_show_and_index_pages()
    {
        $this->rfq->update([
            'admin_note'            => 'Barang EOL ganti model baru',
            'admin_note_updated_at' => now(),
            'admin_note_by'         => $this->admin->id,
        ]);

        // Show page for Sales
        $responseShow = $this->actingAs($this->sales)->get(route('rfq.show', $this->rfq));
        $responseShow->assertOk();
        $responseShow->assertSee('Catatan Admin ke Sales');
        $responseShow->assertSee('Barang EOL ganti model baru');
        $responseShow->assertSee('Salin Format WA');

        // Index page
        $responseIndex = $this->actingAs($this->sales)->get(route('rfq.index'));
        $responseIndex->assertOk();
        $responseIndex->assertSee('Note');
    }

    public function test_admin_note_does_not_leak_into_quotation_pdf()
    {
        $this->rfq->update([
            'notes'                 => 'Catatan khusus resmi untuk klien',
            'admin_note'            => 'SECRET_INTERNAL_EOL_REPLACEMENT_NOTE_DO_NOT_LEAK',
            'status'                => Rfq::STATUS_APPROVED,
        ]);

        // Preview quotation
        $response = $this->actingAs($this->sales)->get(route('rfq.preview_quotation', $this->rfq));
        $response->assertOk();

        // Ensure notes for client exists, but internal admin_note NEVER appears
        $response->assertSee('Catatan khusus resmi untuk klien');
        $response->assertDontSee('SECRET_INTERNAL_EOL_REPLACEMENT_NOTE_DO_NOT_LEAK');
    }
}
