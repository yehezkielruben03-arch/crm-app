<x-app-layout>

    <div class="mb-6">
        <h1 class="text-xl font-bold text-gray-900">Notifikasi</h1>
        <p class="text-sm text-gray-500 mt-0.5">Semua pemberitahuan aktivitas yang ditujukan untuk kamu</p>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        @forelse($notifications as $notif)
        <div class="flex items-start gap-4 px-6 py-4 border-b border-gray-50 hover:bg-gray-50/50 transition-colors {{ $notif->isUnread() ? 'bg-indigo-50/30' : '' }}">

            {{-- Icon berdasarkan tipe --}}
            <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center mt-0.5
                {{ $notif->type === 'po_approved' ? 'bg-emerald-100 text-emerald-600' : 
                   ($notif->type === 'po_rejected' ? 'bg-red-100 text-red-600' : 'bg-indigo-100 text-indigo-600') }}">
                @if($notif->type === 'po_approved')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @elseif($notif->type === 'po_rejected')
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @endif
            </div>

            {{-- Konten --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <p class="text-sm font-semibold text-gray-900">{{ $notif->title }}</p>
                    @if($notif->isUnread())
                    <span class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-0.5">{{ $notif->message }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
            </div>

            {{-- Link kalau ada --}}
            @if($notif->link)
            <a href="{{ $notif->link }}" class="flex-shrink-0 text-xs text-indigo-500 hover:text-indigo-700 hover:underline mt-1">
                Lihat →
            </a>
            @endif
        </div>
        @empty
        <div class="px-6 py-16 text-center">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <p class="text-gray-400 font-medium">Tidak ada notifikasi</p>
        </div>
        @endforelse

        @if($notifications->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">
            {{ $notifications->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
