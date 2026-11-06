<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Rfq;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Ensure all active Sales have an initialized ChatRoom.
     */
    private function autoInitSalesRooms(): void
    {
        $salesUsers = User::whereIn('role', ['Sales', 'Sales Marketing'])
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', 'Active');
            })
            ->get();

        foreach ($salesUsers as $sales) {
            ChatRoom::getOrCreateForSales($sales);
        }
    }

    /**
     * Get real-time status summary of accessible rooms for the current user.
     *
     * @return array<int, array{id: int, unread_count: int, latest_snippet: ?string, latest_sender: ?string, latest_time: ?string}>
     */
    private function getRoomsStatusFor(User $user): array
    {
        return ChatRoom::accessibleFor($user)
            ->with(['latestMessage.user'])
            ->get()
            ->map(function ($r) use ($user) {
                $latest = $r->latestMessage;
                $snippet = null;
                $senderName = null;
                $time = null;

                if ($latest) {
                    $snippet = $latest->message ?: 'Mengirim kartu penawaran RFQ';
                    $senderName = $latest->user ? explode(' ', $latest->user->name)[0] : 'User';
                    $time = $latest->created_at ? $latest->created_at->format('H:i') : null;
                }

                return [
                    'id'             => $r->id,
                    'unread_count'   => $r->unreadCountFor($user),
                    'latest_snippet' => $snippet,
                    'latest_sender'  => $senderName,
                    'latest_time'    => $time,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Check if the authenticated user has access to a specific ChatRoom.
     */
    private function authorizeRoomAccess(User $user, ChatRoom $room): void
    {
        if ($user->isSales() && !$user->isAdminOrAbove() && !$user->isLeader()) {
            abort_if($room->sales_id !== $user->id, 403, 'Anda tidak memiliki akses ke channel koordinasi sales lain.');
        }
    }

    /**
     * Display the main Teams-style chat interface.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $this->autoInitSalesRooms();

        // Retrieve rooms accessible to current user
        $rooms = ChatRoom::accessibleFor($user)
            ->with(['sales', 'latestMessage.user'])
            ->orderBy('name')
            ->get();

        // Calculate unread badge count for each room
        foreach ($rooms as $room) {
            $room->unread_count = $room->unreadCountFor($user);
        }

        // Determine currently active room
        $selectedRoomId = $request->query('room_id');
        $activeRoom = null;

        if ($selectedRoomId) {
            $activeRoom = $rooms->firstWhere('id', (int)$selectedRoomId);
        }

        if (!$activeRoom && $rooms->isNotEmpty()) {
            // If user is sales, pick their own room; otherwise pick the first room with unread messages or the first room
            if ($user->isSales() && !$user->isAdminOrAbove() && !$user->isLeader()) {
                $activeRoom = $rooms->firstWhere('sales_id', $user->id) ?? $rooms->first();
            } else {
                $activeRoom = $rooms->firstWhere('unread_count', '>', 0) ?? $rooms->first();
            }
        }

        $messages = collect();
        if ($activeRoom) {
            $this->authorizeRoomAccess($user, $activeRoom);

            // Fetch recent 60 messages in chronological order
            $messages = $activeRoom->messages()
                ->with(['user', 'rfq'])
                ->orderBy('id', 'asc')
                ->take(60)
                ->get();

            // Mark as read immediately when user views room
            $activeRoom->markAsReadFor($user);
            $activeRoom->unread_count = 0;
            \Illuminate\Support\Facades\Cache::forget('chat:unread:' . $user->id);
        }

        // RFQ options for quick share modal (accessible to this sales room)
        $rfqOptions = collect();
        if ($activeRoom && $activeRoom->sales_id) {
            $rfqOptions = Rfq::where('sales_id', $activeRoom->sales_id)
                ->whereIn('status', [
                    Rfq::STATUS_APPROVED,
                    Rfq::STATUS_QUOTATION_CREATED,
                    Rfq::STATUS_QUOTATION_SENT,
                    Rfq::STATUS_PENDING_ADMIN,
                    Rfq::STATUS_PENDING_LEADER,
                ])
                ->orderByDesc('updated_at')
                ->take(15)
                ->get(['id', 'rfq_number', 'customer_name', 'status', 'grand_total', 'updated_at']);
        }

        $initialRoomsStatus = $this->getRoomsStatusFor($user);

        return view('chat.index', compact('rooms', 'activeRoom', 'messages', 'rfqOptions', 'initialRoomsStatus'));
    }

    /**
     * Send a new message or RFQ card into the active room.
     */
    public function sendMessage(Request $request, ChatRoom $room): JsonResponse|RedirectResponse
    {
        $user = Auth::user();
        $this->authorizeRoomAccess($user, $room);

        $validated = $request->validate([
            'message'      => 'required_without:rfq_id|nullable|string|max:5000',
            'rfq_id'       => 'nullable|exists:rfqs,id',
            'message_type' => 'nullable|string|in:text,rfq_card',
        ]);

        $messageType = $validated['message_type'] ?? (!empty($validated['rfq_id']) ? 'rfq_card' : 'text');

        $chatMessage = $room->messages()->create([
            'user_id'      => $user->id,
            'message'      => $validated['message'] ?? null,
            'message_type' => $messageType,
            'rfq_id'       => $validated['rfq_id'] ?? null,
        ]);

        // Eager load relationships for clean payload
        $chatMessage->load(['user', 'rfq']);

        // Sender automatically has read this message
        $room->markAsReadFor($user);
        \Illuminate\Support\Facades\Cache::forget('chat:unread:' . $user->id);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'message'      => $chatMessage->toPayloadFor($user),
                'rooms_status' => $this->getRoomsStatusFor($user),
            ]);
        }

        return redirect()->route('chat.index', ['room_id' => $room->id]);
    }

    /**
     * Lightweight polling endpoint to retrieve messages newer than last_id.
     */
    public function pollMessages(Request $request, ChatRoom $room): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeRoomAccess($user, $room);

        $lastId = (int)$request->query('last_id', 0);

        $newMessages = $room->messages()
            ->with(['user', 'rfq'])
            ->where('id', '>', $lastId)
            ->orderBy('id', 'asc')
            ->get();

        if ($newMessages->isNotEmpty()) {
            $room->markAsReadFor($user);
            \Illuminate\Support\Facades\Cache::forget('chat:unread:' . $user->id);
        }

        return response()->json([
            'success'      => true,
            'messages'     => $newMessages->map(fn($m) => $m->toPayloadFor($user)),
            'rooms_status' => $this->getRoomsStatusFor($user),
        ]);
    }

    /**
     * Explicit mark as read API endpoint.
     */
    public function markAsRead(ChatRoom $room): JsonResponse
    {
        $user = Auth::user();
        $this->authorizeRoomAccess($user, $room);

        $room->markAsReadFor($user);
        \Illuminate\Support\Facades\Cache::forget('chat:unread:' . $user->id);

        return response()->json([
            'success'      => true,
            'rooms_status' => $this->getRoomsStatusFor($user),
        ]);
    }

    /**
     * Quick action from RFQ Show page to share RFQ card into Sales Chat room.
     */
    public function shareRfq(Request $request, Rfq $rfq): RedirectResponse
    {
        $user = Auth::user();

        // Resolve sales user
        $salesUser = $rfq->sales ?: User::find($rfq->sales_id);
        if (!$salesUser && $rfq->sales_name) {
            $salesUser = User::where('name', $rfq->sales_name)->first();
        }

        if (!$salesUser) {
            return back()->with('error', 'Sales penanggung jawab RFQ ini tidak ditemukan.');
        }

        $room = ChatRoom::getOrCreateForSales($salesUser);

        $note = $request->input('notes');
        $defaultText = "Penawaran {$rfq->rfq_number} ({$rfq->customer_name}) sudah di-ACC Leader dan siap dikirim ke klien.";
        $messageText = !empty($note) ? trim($note) : $defaultText;

        $room->messages()->create([
            'user_id'      => $user->id,
            'message'      => $messageText,
            'message_type' => 'rfq_card',
            'rfq_id'       => $rfq->id,
        ]);

        $room->markAsReadFor($user);

        return redirect()->route('chat.index', ['room_id' => $room->id])
            ->with('success', "Penawaran {$rfq->rfq_number} berhasil dibagikan ke ruang koordinasi Sales {$salesUser->name}.");
    }
}
