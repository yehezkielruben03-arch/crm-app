<x-app-layout>

    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-teal, #0d9488);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                RFQ
                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full" style="background: var(--bg-secondary); color: var(--text-secondary);">{{ $rfqs->total() }}</span>
            </h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola request quotation dari klien</p>
        </div>
        <div class="flex items-center gap-2">
            @if(in_array(auth()->user()->role, ['Admin', 'Super Admin']))
            <a href="{{ route('rfq.create', ['type' => 'Projek']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md"
               style="background: linear-gradient(135deg, var(--accent-emerald), #059669); box-shadow: 0 4px 12px rgba(16,185,129,0.30);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat RFQ Projek
            </a>
            @endif
            @if(auth()->user()->hasPermission('CRUD') || auth()->user()->isSales())
            <a href="{{ route('rfq.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md"
               style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat RFQ Baru
            </a>
            @endif
        </div>
    </div>

    <div class="card filter-bar p-4 mb-5">
        <form method="GET" action="{{ route('rfq.index') }}" class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari No. RFQ atau nama perusahaan..."
                    class="w-full pl-9 pr-4 py-2 text-sm rounded-xl outline-none transition-all"
                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                    onfocus="this.style.borderColor='var(--accent-blue)'"
                    onblur="this.style.borderColor='var(--border-color)'">
            </div>
            <x-custom-select name="status" :value="request('status')" placeholder="Semua Status" :options="[
                '' => 'Semua Status',
                'Pending Admin' => 'Pending Admin',
                'Pending Leader' => 'Pending Leader',
                'Approved' => 'Approved',
                'Quotation Created' => 'Quotation Created',
                'PO Received (Pending Admin)' => 'PO Received',
                'GOAL' => 'GOAL',
                'Cancelled' => 'Cancelled',
            ]" />
            <button type="submit"
                class="px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8);">
                Filter
            </button>
            @if(request()->hasAny(['search','status']))
            <a href="{{ route('rfq.index') }}"
               class="px-4 py-2 text-sm rounded-xl transition-colors"
               style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                Reset
            </a>
            @endif
        </form>
    </div>

    <div class="card animate-in" style="animation-delay: 0.15s;">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. RFQ</th>
                        <th>Customer</th>
                        <th>Sales Marketing</th>
                        <th>Total Items</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rfqs as $rfq)
                    <tr>
                        <td>
                            <div class="flex flex-col gap-1 items-start">
                                <code class="text-xs px-2 py-1 rounded font-semibold"
                                    style="background: var(--bg-secondary); color: var(--text-secondary);">
                                    {{ $rfq->rfq_number }}
                                </code>
                                @if($rfq->priority === 'Urgent' || $rfq->priority === 'High Priority')
                                    <span class="px-1.5 py-0.5 text-[9px] font-bold rounded" style="background: rgba(239,68,68,0.1); color: var(--accent-rose); border: 1px solid rgba(239,68,68,0.2);">{{ $rfq->priority }}</span>
                                @elseif($rfq->priority)
                                    <span class="px-1.5 py-0.5 text-[9px] font-medium rounded" style="background: rgba(148,163,184,0.1); color: var(--text-secondary); border: 1px solid rgba(148,163,184,0.2);">{{ $rfq->priority }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $rfq->customer_name }}</p>
                        </td>
                        <td style="color: var(--text-secondary);">{{ $rfq->sales_name }}</td>
                        <td style="color: var(--text-secondary);">{{ $rfq->items->count() }} item</td>
                        <td>
                            @php
                                $badgeClass = match($rfq->status) {
                                    'Converted' => 'approved',
                                    'Cancelled' => 'rejected',
                                    default     => 'pending',
                                };
                            @endphp
                            <div class="flex flex-col items-start gap-1">
                                <span class="status-badge {{ $badgeClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                    {{ $rfq->status }}
                                </span>
                                <span class="text-[10px] font-medium" style="color: var(--text-muted);">
                                    {{ $rfq->getNextActionLabel() }}
                                </span>
                            </div>
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('rfq.show', $rfq) }}" title="Detail"
                                    class="p-1.5 rounded-lg transition-colors"
                                    style="color: var(--text-muted);"
                                    onmouseenter="this.style.background='rgba(37,99,235,0.08)'; this.style.color='var(--accent-blue)'"
                                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @if($rfq->canBeEdited() && (auth()->user()->hasPermission('CRUD') || auth()->user()->isSales()))
                                <a href="{{ route('rfq.edit', $rfq) }}" title="Edit"
                                    class="p-1.5 rounded-lg transition-colors"
                                    style="color: var(--text-muted);"
                                    onmouseenter="this.style.background='rgba(217,119,6,0.08)'; this.style.color='var(--accent-amber)'"
                                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>

                                <form action="{{ route('rfq.destroy', $rfq) }}" method="POST" class="inline" onsubmit="return confirm('Arsipkan RFQ {{ $rfq->rfq_number }}? RFQ ini akan disembunyikan tapi tetap tersimpan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Arsipkan"
                                        class="p-1.5 rounded-lg transition-colors"
                                        style="color: var(--text-muted);"
                                        onmouseenter="this.style.background='rgba(225,29,72,0.08)'; this.style.color='var(--accent-rose)'"
                                        onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                        </svg>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--border-color);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="font-medium" style="color: var(--text-muted);">Belum ada RFQ</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rfqs->hasPages())
        <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
            {{ $rfqs->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
