<x-app-layout>
<div class="h-[calc(100vh-5rem)] flex flex-col -m-4 sm:-m-6 lg:-m-8" x-data="crmChatApp({
    activeRoomId: {{ $activeRoom ? $activeRoom->id : 'null' }},
    currentUserId: {{ auth()->id() }},
    lastMessageId: {{ $messages->isNotEmpty() ? $messages->last()->id : 0 }},
    pollUrl: '{{ $activeRoom ? route('chat.poll_messages', $activeRoom) : '' }}',
    sendUrl: '{{ $activeRoom ? route('chat.send_message', $activeRoom) : '' }}',
    markReadUrl: '{{ $activeRoom ? route('chat.mark_read', $activeRoom) : '' }}',
    csrfToken: '{{ csrf_token() }}'
})" x-init="initChat()">

    <!-- Main Container: Teams 2-Column Split -->
    <div class="flex-1 flex overflow-hidden bg-slate-100">

        <!-- ─── Sidebar Channel List (Kiri) ───────────────────────────────── -->
        <aside class="w-80 sm:w-88 flex-shrink-0 bg-white border-r border-slate-200 flex flex-col z-10"
               :class="mobileShowChat ? 'hidden md:flex' : 'flex'">
            
            <!-- Channel Header & Search -->
            <div class="p-4 border-b border-slate-200 bg-slate-50/70">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 tracking-tight">Teams Koordinasi</h2>
                            <p class="text-[11px] text-slate-500">Saluran Sales &bull; Admin &bull; Leader</p>
                        </div>
                    </div>
                </div>

                <!-- Live Search Box -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" 
                           placeholder="Cari saluran sales..." 
                           class="w-full text-xs pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <!-- Room List Scroll Area -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100">
                @forelse($rooms as $room)
                <a href="{{ route('chat.index', ['room_id' => $room->id]) }}" 
                   x-show="matchesSearch('{{ strtolower($room->name) }}')"
                   class="flex items-center gap-3 p-3.5 transition-all hover:bg-slate-50 relative group {{ ($activeRoom && $activeRoom->id === $room->id) ? 'bg-indigo-50/70 border-l-4 border-indigo-600' : 'border-l-4 border-transparent' }}">
                    
                    <!-- Avatar with Role ring -->
                    <div class="relative flex-shrink-0">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm text-white shadow-xs {{ ($activeRoom && $activeRoom->id === $room->id) ? 'bg-indigo-600' : 'bg-slate-600' }}">
                            {{ strtoupper(substr($room->name, 0, 1)) }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full bg-emerald-500 border-2 border-white"></span>
                    </div>

                    <!-- Room Meta -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-0.5">
                            <h3 class="text-xs font-bold truncate {{ ($activeRoom && $activeRoom->id === $room->id) ? 'text-indigo-950 font-extrabold' : 'text-slate-800' }}">
                                {{ $room->name }}
                            </h3>
                            @if($room->latestMessage)
                            <span class="text-[10px] text-slate-400 flex-shrink-0 ml-1">
                                {{ $room->latestMessage->created_at->format('H:i') }}
                            </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-[11px] text-slate-500 truncate pr-2">
                                @if($room->latestMessage)
                                    <span class="font-medium text-slate-600">{{ $room->latestMessage->user?->name ? explode(' ', $room->latestMessage->user->name)[0] . ': ' : '' }}</span>{{ $room->latestMessage->message ?: 'Mengirim kartu penawaran RFQ' }}
                                @else
                                    <span class="italic text-slate-400">Belum ada obrolan</span>
                                @endif
                            </p>
                            @if($room->unread_count > 0)
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-emerald-500 rounded-full shadow-2xs">
                                {{ $room->unread_count }}
                            </span>
                            @endif
                        </div>
                    </div>
                </a>
                @empty
                <div class="p-6 text-center text-xs text-slate-400">
                    Tidak ada saluran koordinasi yang tersedia.
                </div>
                @endforelse
            </div>
        </aside>

        <!-- ─── Main Chat Window (Kanan) ──────────────────────────────────── -->
        <main class="flex-1 flex flex-col bg-slate-50 overflow-hidden"
              :class="!mobileShowChat ? 'hidden md:flex' : 'flex'">
            @if($activeRoom)
                <!-- Chat Window Header -->
                <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between shadow-xs flex-shrink-0 z-10">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Mobile Back to Channel List Button -->
                        <button type="button" @click="mobileShowChat = false" class="md:hidden p-1.5 text-slate-500 hover:text-slate-700 rounded-lg hover:bg-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>

                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow-xs flex-shrink-0">
                            {{ strtoupper(substr($activeRoom->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-sm font-bold text-slate-900 truncate flex items-center gap-2">
                                <span>Saluran {{ $activeRoom->name }}</span>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    Privat Sales
                                </span>
                            </h2>
                            <p class="text-[11px] text-slate-500 truncate">
                                Tim: <strong class="text-slate-700">{{ $activeRoom->name }}</strong> (Sales) &bull; Admin Purchase &bull; Leader
                            </p>
                        </div>
                    </div>

                    <!-- Right Header Actions (Quick Attach RFQ) -->
                    <div class="flex items-center gap-2">
                        @if($rfqOptions->isNotEmpty())
                        <button type="button" @click="openRfqModal = true"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 transition cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span>Bagikan RFQ</span>
                        </button>
                        @endif
                    </div>
                </header>

                <!-- Message Stream Feed -->
                <div class="flex-1 overflow-y-auto p-4 sm:p-6 space-y-4" x-ref="messageBox">
                    <div class="text-center my-2">
                        <span class="px-3 py-1 text-[11px] font-medium text-slate-400 bg-slate-200/60 rounded-full">
                            Awal percakapan saluran koordinasi {{ $activeRoom->name }}
                        </span>
                    </div>

                    @forelse($messages as $msg)
                        @php
                            $isMine = ($msg->user_id === auth()->id());
                            $sender = $msg->user;
                            $roleName = $sender?->isLeader() ? 'Leader' : ($sender?->isAdminOrAbove() ? 'Admin' : 'Sales');
                            $roleBadgeColor = match($roleName) {
                                'Leader' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                'Admin'  => 'bg-amber-50 text-amber-800 border-amber-300',
                                default  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            };
                            $avatarBg = match($roleName) {
                                'Leader' => 'bg-indigo-600',
                                'Admin'  => 'bg-amber-600',
                                default  => 'bg-emerald-600',
                            };
                        @endphp

                        <div class="flex items-start gap-3 {{ $isMine ? 'flex-row-reverse' : '' }} group" data-message-id="{{ $msg->id }}">
                            <!-- Sender Avatar -->
                            <div class="w-8 h-8 rounded-lg {{ $avatarBg }} text-white flex items-center justify-center text-xs font-bold flex-shrink-0 shadow-xs">
                                {{ strtoupper(substr($sender?->name ?? 'U', 0, 1)) }}
                            </div>

                            <!-- Bubble Container -->
                            <div class="flex flex-col max-w-[85%] sm:max-w-xl {{ $isMine ? 'items-end' : 'items-start' }}">
                                <!-- Sender Header -->
                                <div class="flex items-center gap-1.5 mb-1 text-[11px]">
                                    <span class="font-bold text-slate-800">{{ $sender?->name ?? 'User' }}</span>
                                    <span class="px-1.5 py-0.2 text-[9px] font-semibold rounded-md border {{ $roleBadgeColor }}">
                                        {{ $roleName }}
                                    </span>
                                    <span class="text-slate-400 font-normal">&bull; {{ $msg->created_at->format('H:i') }}</span>
                                </div>

                                <!-- Text Message Bubble -->
                                @if(!empty($msg->message))
                                <div class="p-3.5 rounded-2xl text-xs leading-relaxed shadow-xs {{ $isMine ? 'bg-indigo-600 text-white rounded-tr-xs' : 'bg-white text-slate-800 border border-slate-200/80 rounded-tl-xs' }}">
                                    {!! nl2br(e($msg->message)) !!}
                                </div>
                                @endif

                                <!-- RFQ Card Attachment -->
                                @if($msg->isRfqCard() && $msg->rfq)
                                <div class="mt-2 w-full sm:w-84 bg-white border border-indigo-200 rounded-xl shadow-xs overflow-hidden">
                                    <div class="p-3 bg-indigo-50/70 border-b border-indigo-100 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-md bg-indigo-600 text-white flex items-center justify-center">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                            </div>
                                            <span class="text-xs font-bold text-indigo-950">{{ $msg->rfq->rfq_number }}</span>
                                        </div>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-white text-indigo-700 border border-indigo-200">
                                            {{ $msg->rfq->status }}
                                        </span>
                                    </div>
                                    <div class="p-3 text-xs space-y-1">
                                        <p class="font-semibold text-slate-800 truncate">{{ $msg->rfq->customer_name }}</p>
                                        @if($msg->rfq->grand_total)
                                        <p class="text-[11px] text-slate-500">Nilai: <strong class="text-slate-900">Rp {{ number_format($msg->rfq->grand_total, 0, ',', '.') }}</strong></p>
                                        @endif
                                        <div class="pt-2 flex items-center gap-2">
                                            <a href="{{ route('rfq.show', $msg->rfq) }}" target="_blank"
                                               class="flex-1 text-center py-1.5 text-[11px] font-bold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                                Buka RFQ
                                            </a>
                                            <a href="{{ route('rfq.download_quotation', $msg->rfq) }}" target="_blank"
                                               class="px-2.5 py-1.5 text-[11px] font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition" title="Download PDF">
                                                PDF
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400 text-xs">
                            Belum ada pesan di saluran ini. Mulai percakapan sekarang!
                        </div>
                    @endforelse

                    <!-- Dynamic Polled Messages Appended Here via Alpine -->
                    <template x-for="m in polledMessages" :key="m.id">
                        <div class="flex items-start gap-3 group" :class="m.is_mine ? 'flex-row-reverse' : ''">
                            <div class="w-8 h-8 rounded-lg text-white flex items-center justify-center text-xs font-bold flex-shrink-0 shadow-xs"
                                 :class="m.user_role === 'Leader' ? 'bg-indigo-600' : (m.user_role === 'Admin' ? 'bg-amber-600' : 'bg-emerald-600')"
                                 x-text="m.user_initial"></div>
                            
                            <div class="flex flex-col max-w-[85%] sm:max-w-xl" :class="m.is_mine ? 'items-end' : 'items-start'">
                                <div class="flex items-center gap-1.5 mb-1 text-[11px]">
                                    <span class="font-bold text-slate-800" x-text="m.user_name"></span>
                                    <span class="px-1.5 py-0.2 text-[9px] font-semibold rounded-md border" :class="m.badge_class" x-text="m.user_role"></span>
                                    <span class="text-slate-400 font-normal">&bull; <span x-text="m.created_at_time"></span></span>
                                </div>

                                <template x-if="m.message">
                                    <div class="p-3.5 rounded-2xl text-xs leading-relaxed shadow-xs"
                                         :class="m.is_mine ? 'bg-indigo-600 text-white rounded-tr-xs' : 'bg-white text-slate-800 border border-slate-200/80 rounded-tl-xs'"
                                         x-text="m.message"></div>
                                </template>

                                <template x-if="m.message_type === 'rfq_card' && m.rfq">
                                    <div class="mt-2 w-full sm:w-84 bg-white border border-indigo-200 rounded-xl shadow-xs overflow-hidden">
                                        <div class="p-3 bg-indigo-50/70 border-b border-indigo-100 flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <div class="w-6 h-6 rounded-md bg-indigo-600 text-white flex items-center justify-center font-bold text-xs">RFQ</div>
                                                <span class="text-xs font-bold text-indigo-950" x-text="m.rfq.rfq_number"></span>
                                            </div>
                                            <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-white text-indigo-700 border border-indigo-200" x-text="m.rfq.status"></span>
                                        </div>
                                        <div class="p-3 text-xs space-y-1">
                                            <p class="font-semibold text-slate-800 truncate" x-text="m.rfq.customer_name"></p>
                                            <template x-if="m.rfq.grand_total">
                                                <p class="text-[11px] text-slate-500">Nilai: <strong class="text-slate-900" x-text="m.rfq.grand_total"></strong></p>
                                            </template>
                                            <div class="pt-2 flex items-center gap-2">
                                                <a :href="m.rfq.detail_url" target="_blank" class="flex-1 text-center py-1.5 text-[11px] font-bold rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                                                    Buka RFQ
                                                </a>
                                                <a :href="m.rfq.pdf_url" target="_blank" class="px-2.5 py-1.5 text-[11px] font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                                                    PDF
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Chat Input Bar -->
                <footer class="p-3 sm:p-4 bg-white border-t border-slate-200 flex-shrink-0">
                    <form @submit.prevent="sendMessage()" class="flex items-end gap-2 max-w-5xl mx-auto">
                        <div class="flex-1 relative bg-slate-50 border border-slate-300 rounded-2xl focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 transition">
                            <textarea x-model="newMessage"
                                      @keydown.enter.exact.prevent="sendMessage()"
                                      rows="2"
                                      placeholder="Ketik pesan koordinasi (Enter untuk kirim, Shift+Enter untuk baris baru)..."
                                      class="w-full bg-transparent px-4 py-2.5 text-xs text-slate-800 placeholder-slate-400 resize-none focus:outline-none"></textarea>
                        </div>

                        <!-- Send Button -->
                        <button type="submit" 
                                :disabled="isSending || (!newMessage.trim() && !selectedRfqId)"
                                class="px-4 py-2.5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold text-xs flex items-center gap-1.5 shadow-md transition cursor-pointer">
                            <span x-show="!isSending">Kirim</span>
                            <span x-show="isSending">...</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                        </button>
                    </form>
                </footer>

                <!-- RFQ Share Modal -->
                <div x-show="openRfqModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-xs">
                    <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 overflow-hidden" @click.away="openRfqModal = false">
                        <div class="p-4 bg-indigo-900 text-white flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider">Pilih RFQ untuk Dibagikan</h3>
                            <button type="button" @click="openRfqModal = false" class="text-indigo-200 hover:text-white">&times;</button>
                        </div>
                        <div class="p-4 space-y-3">
                            <p class="text-xs text-slate-600">Pilih salah satu penawaran milik sales <strong>{{ $activeRoom->name }}</strong> untuk dibagikan ke obrolan:</p>
                            <div class="max-h-60 overflow-y-auto space-y-2">
                                @foreach($rfqOptions as $opt)
                                <label class="flex items-center justify-between p-2.5 rounded-xl border border-slate-200 hover:bg-indigo-50/50 cursor-pointer transition">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="modal_rfq_pick" value="{{ $opt->id }}" @change="selectedRfqId = {{ $opt->id }}" class="text-indigo-600 focus:ring-indigo-500">
                                        <div>
                                            <p class="text-xs font-bold text-slate-800">{{ $opt->rfq_number }}</p>
                                            <p class="text-[11px] text-slate-500">{{ $opt->customer_name }}</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 text-[9px] font-bold rounded-md bg-slate-100 text-slate-700">
                                        {{ $opt->status }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-700 mb-1">Catatan Tambahan (Opsional):</label>
                                <input type="text" x-model="rfqNote" placeholder="Contoh: Sudah di-ACC Leader, siap kirim." class="w-full text-xs p-2.5 bg-slate-50 border border-slate-300 rounded-xl focus:border-indigo-500 focus:outline-none">
                            </div>
                        </div>
                        <div class="p-3 bg-slate-50 border-t border-slate-200 flex justify-end gap-2">
                            <button type="button" @click="openRfqModal = false" class="px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-200 rounded-lg">Batal</button>
                            <button type="button" @click="sendRfqCard()" :disabled="!selectedRfqId" class="px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-lg">Bagikan ke Chat</button>
                        </div>
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center p-6 text-center text-slate-400">
                    <svg class="w-16 h-16 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                    <h3 class="text-sm font-bold text-slate-700 mb-1">Pilih Saluran Koordinasi</h3>
                    <p class="text-xs text-slate-500 max-w-sm">Pilih salah satu nama sales di sebelah kiri untuk membuka ruang koordinasi bersama tim Admin & Leader.</p>
                </div>
            @endif
        </main>
    </div>
</div>

<script>
function crmChatApp(config) {
    return {
        activeRoomId: config.activeRoomId,
        currentUserId: config.currentUserId,
        lastMessageId: config.lastMessageId || 0,
        pollUrl: config.pollUrl,
        sendUrl: config.sendUrl,
        markReadUrl: config.markReadUrl,
        csrfToken: config.csrfToken,

        searchQuery: '',
        newMessage: '',
        isSending: false,
        mobileShowChat: false,
        polledMessages: [],

        openRfqModal: false,
        selectedRfqId: null,
        rfqNote: '',
        pollTimer: null,

        initChat() {
            if (this.activeRoomId) {
                this.scrollToBottom();
                this.startPolling();
            }
        },

        matchesSearch(name) {
            if (!this.searchQuery) return true;
            return name.includes(this.searchQuery.toLowerCase());
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messageBox;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        startPolling() {
            if (!this.pollUrl) return;
            this.pollTimer = setInterval(() => {
                this.fetchNewMessages();
            }, 3000);
        },

        async fetchNewMessages() {
            if (!this.pollUrl || !this.activeRoomId) return;
            try {
                const res = await fetch(`${this.pollUrl}?last_id=${this.lastMessageId}`);
                if (!res.ok) return;
                const data = await res.json();
                if (data.success && data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        this.polledMessages.push(msg);
                        if (msg.id > this.lastMessageId) {
                            this.lastMessageId = msg.id;
                        }
                    });
                    this.scrollToBottom();
                }
            } catch (err) {
                // Fail silently in network jitter
            }
        },

        async sendMessage() {
            const text = this.newMessage.trim();
            if (!text && !this.selectedRfqId) return;

            this.isSending = true;
            try {
                const res = await fetch(this.sendUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        message: text,
                        rfq_id: this.selectedRfqId || null,
                        message_type: this.selectedRfqId ? 'rfq_card' : 'text'
                    })
                });

                if (res.ok) {
                    const data = await res.json();
                    if (data.success && data.message) {
                        this.polledMessages.push(data.message);
                        if (data.message.id > this.lastMessageId) {
                            this.lastMessageId = data.message.id;
                        }
                        this.newMessage = '';
                        this.selectedRfqId = null;
                        this.scrollToBottom();
                    }
                }
            } catch (err) {
                alert('Gagal mengirim pesan. Silakan coba lagi.');
            } finally {
                this.isSending = false;
            }
        },

        sendRfqCard() {
            if (!this.selectedRfqId) return;
            this.newMessage = this.rfqNote.trim();
            this.openRfqModal = false;
            this.sendMessage();
            this.rfqNote = '';
        }
    };
}
</script>
</x-app-layout>
