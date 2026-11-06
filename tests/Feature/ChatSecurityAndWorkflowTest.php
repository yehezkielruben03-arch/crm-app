<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatSecurityAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $leader;
    protected User $sales1;
    protected User $sales2;
    protected Customer $customer1;
    protected Rfq $rfq1;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.enable_crm_chat' => true]);
        if (\App\Models\RolePermission::count() === 0) {
            $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        }

        $this->admin = User::factory()->create([
            'role'   => 'Admin',
            'name'   => 'Admin Purchase Chat',
            'status' => 'Active',
        ]);

        $this->leader = User::factory()->create([
            'role'   => 'Leader',
            'name'   => 'Leader Rian Chat',
            'status' => 'Active',
        ]);

        $this->sales1 = User::factory()->create([
            'role'   => 'Sales',
            'name'   => 'Ade Zulvida',
            'status' => 'Active',
        ]);

        $this->sales2 = User::factory()->create([
            'role'   => 'Sales',
            'name'   => 'Yudi Sales',
            'status' => 'Active',
        ]);

        $this->customer1 = Customer::create([
            'company_name' => 'PT NOF MAS',
            'company_code' => 'CUST-NOF-001',
            'email'        => 'contact@nofmas.com',
            'sales_id'     => $this->sales1->id,
            'status'       => Customer::STATUS_ACTIVE,
        ]);

        $contact = CustomerContact::create([
            'customer_id' => $this->customer1->id,
            'name'        => 'PIC NOF MAS',
            'is_primary'  => true,
        ]);

        $this->rfq1 = Rfq::create([
            'rfq_number'          => 'RFQ-202611-001',
            'customer_id'         => $this->customer1->id,
            'customer_name'       => $this->customer1->company_name,
            'customer_code'       => $this->customer1->company_code,
            'customer_contact_id' => $contact->id,
            'type'                => 'Hardware',
            'sales_id'            => $this->sales1->id,
            'sales_name'          => $this->sales1->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'status'              => Rfq::STATUS_APPROVED,
        ]);
    }

    public function test_rooms_auto_initialize_for_active_sales(): void
    {
        $resp = $this->actingAs($this->admin)->get(route('chat.index'));
        $resp->assertOk();

        // Must have created rooms for sales1 and sales2
        $this->assertDatabaseHas('chat_rooms', ['sales_id' => $this->sales1->id, 'name' => 'Ade Zulvida']);
        $this->assertDatabaseHas('chat_rooms', ['sales_id' => $this->sales2->id, 'name' => 'Yudi Sales']);
    }

    public function test_sidebar_displays_chat_teams_navigation_link_when_enabled(): void
    {
        $dashResp = $this->actingAs($this->admin)->get(route('dashboard'));
        $dashResp->assertOk();
        $dashResp->assertSee('Chat Teams');
        $dashResp->assertSee(route('chat.index'));
    }

    public function test_sales_can_only_access_their_own_room_and_not_other_sales_room(): void
    {
        $room1 = ChatRoom::getOrCreateForSales($this->sales1);
        $room2 = ChatRoom::getOrCreateForSales($this->sales2);

        // Sales 1 opens chat index -> sees room1, does NOT see room2 in sidebar
        $respSales1 = $this->actingAs($this->sales1)->get(route('chat.index'));
        $respSales1->assertOk();
        $respSales1->assertSee('Ade Zulvida');
        $respSales1->assertDontSee('Yudi Sales');

        // Sales 1 tries to access Room 2 directly via poll -> 403 Forbidden
        $pollResp = $this->actingAs($this->sales1)->get(route('chat.poll_messages', $room2));
        $pollResp->assertStatus(403);

        // Sales 1 tries to send message into Room 2 -> 403 Forbidden
        $sendResp = $this->actingAs($this->sales1)->post(route('chat.send_message', $room2), [
            'message' => 'Mencoba mengintip room sales lain',
        ]);
        $sendResp->assertStatus(403);
    }

    public function test_admin_and_leader_can_access_all_sales_rooms(): void
    {
        $room1 = ChatRoom::getOrCreateForSales($this->sales1);
        $room2 = ChatRoom::getOrCreateForSales($this->sales2);

        // Admin can access both rooms
        $respAdmin = $this->actingAs($this->admin)->get(route('chat.index'));
        $respAdmin->assertOk();
        $respAdmin->assertSee('Ade Zulvida');
        $respAdmin->assertSee('Yudi Sales');

        // Leader can access Room 1 and send message
        $leaderSend = $this->actingAs($this->leader)->postJson(route('chat.send_message', $room1), [
            'message' => 'Ade, penawaran NOF MAS margin sudah mantap, langsung kirim ya.',
        ]);
        $leaderSend->assertOk();
        $leaderSend->assertJson(['success' => true]);

        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $room1->id,
            'user_id'      => $this->leader->id,
            'message'      => 'Ade, penawaran NOF MAS margin sudah mantap, langsung kirim ya.',
        ]);
    }

    public function test_share_rfq_card_directly_into_sales_chat_room(): void
    {
        $room1 = ChatRoom::getOrCreateForSales($this->sales1);

        // Admin clicks "Kirim ke Chat" from RFQ Show page
        $shareResp = $this->actingAs($this->admin)->post(route('chat.share_rfq', $this->rfq1), [
            'notes' => 'Margin aman, stok sisa 2 unit segera amankan PO.',
        ]);

        $shareResp->assertRedirect(route('chat.index', ['room_id' => $room1->id]));
        $shareResp->assertSessionHas('success');

        // Verify message was stored as rfq_card
        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $room1->id,
            'user_id'      => $this->admin->id,
            'message_type' => 'rfq_card',
            'rfq_id'       => $this->rfq1->id,
            'message'      => 'Margin aman, stok sisa 2 unit segera amankan PO.',
        ]);

        // Sales 1 polls messages in their room -> receives the card
        $pollResp = $this->actingAs($this->sales1)->getJson(route('chat.poll_messages', $room1));
        $pollResp->assertOk();
        $data = $pollResp->json();
        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['messages']);
        $this->assertEquals('rfq_card', $data['messages'][0]['message_type']);
        $this->assertEquals($this->rfq1->rfq_number, $data['messages'][0]['rfq']['rfq_number']);
        $this->assertEquals($this->rfq1->customer_name, $data['messages'][0]['rfq']['customer_name']);
        $this->assertArrayHasKey('rooms_status', $data);
    }

    public function test_send_message_and_poll_updates_realtime_rooms_status_and_clears_cache(): void
    {
        $room1 = ChatRoom::getOrCreateForSales($this->sales1);

        // Leader sends message to Sales 1 room
        $sendResp = $this->actingAs($this->leader)->postJson(route('chat.send_message', $room1), [
            'message' => 'Halo Ade, tolong follow up penawaran.',
        ]);
        $sendResp->assertOk();
        $sendData = $sendResp->json();
        $this->assertTrue($sendData['success']);
        $this->assertArrayHasKey('rooms_status', $sendData);

        // Sales 1 checks unread count in User model
        $this->assertEquals(1, $this->sales1->unreadChatCount());

        // Sales 1 polls messages -> marks room as read
        $pollResp = $this->actingAs($this->sales1)->getJson(route('chat.poll_messages', $room1));
        $pollResp->assertOk();
        $pollData = $pollResp->json();
        $this->assertTrue($pollData['success']);
        $this->assertCount(1, $pollData['messages']);
        $this->assertArrayHasKey('rooms_status', $pollData);

        // After polling, unread count should be 0
        $this->assertEquals(0, $this->sales1->unreadChatCount());
    }

    public function test_leader_approval_automatically_posts_announcement_to_sales_chat_room(): void
    {
        $room1 = ChatRoom::getOrCreateForSales($this->sales1);

        // Prepare an RFQ with Pending Leader status and an admin note
        $pendingRfq = Rfq::create([
            'rfq_number'          => 'RFQ-PENDING-001',
            'customer_id'         => $this->customer1->id,
            'customer_name'       => $this->customer1->company_name,
            'customer_code'       => $this->customer1->company_code,
            'customer_contact_id' => $this->customer1->contacts()->first()->id,
            'type'                => 'Hardware',
            'sales_id'            => $this->sales1->id,
            'sales_name'          => $this->sales1->name,
            'rfq_date'            => now()->format('Y-m-d'),
            'status'              => Rfq::STATUS_PENDING_LEADER,
            'admin_note'          => 'unit indent sesuai note dari supplier',
        ]);

        // Leader approves the RFQ
        $approveResp = $this->actingAs($this->leader)->post(route('rfq.approve', $pendingRfq));
        $approveResp->assertSessionHas('success');

        // Verify RFQ status is Approved
        $this->assertEquals(Rfq::STATUS_APPROVED, $pendingRfq->fresh()->status);

        // Verify announcement message automatically created in Sales 1 chat room
        $this->assertDatabaseHas('chat_messages', [
            'chat_room_id' => $room1->id,
            'user_id'      => $this->leader->id,
            'message_type' => 'rfq_card',
            'rfq_id'       => $pendingRfq->id,
        ]);

        $latestMessage = $room1->messages()->latest('id')->first();
        $this->assertStringContainsString('Pengumuman:', $latestMessage->message);
        $this->assertStringContainsString('unit indent sesuai note dari supplier', $latestMessage->message);

        // Sales checks chat view
        $chatResp = $this->actingAs($this->sales1)->get(route('chat.index', ['room_id' => $room1->id]));
        $chatResp->assertOk();
        $chatResp->assertSee('unit indent sesuai note dari supplier');
        $chatResp->assertSee('Siap dikirim ke klien');
        // Buttons should not be present
        $chatResp->assertDontSee('Buka RFQ');
    }

    public function test_chat_returns_404_when_feature_flag_disabled(): void
    {
        config(['app.enable_crm_chat' => false]);
        $this->actingAs($this->admin);

        $response = $this->get(route('chat.index'));
        $response->assertNotFound();
    }
}
