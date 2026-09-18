<x-app-layout>

    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-teal, #0d9488);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Quotation
                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full" style="background: var(--bg-secondary); color: var(--text-secondary);">{{ $quotations->total() }}</span>
            </h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola penawaran harga untuk klien yang telah disetujui Leader</p>
        </div>
    </div>

    <div class="card filter-bar p-4 mb-5">
        <form method="GET" action="{{ route('quo.index') }}" class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari No. Penawaran atau nama perusahaan..."
                    class="w-full pl-9 pr-4 py-2 text-sm rounded-xl outline-none transition-all"
                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                    onfocus="this.style.borderColor='var(--accent-blue)'"
                    onblur="this.style.borderColor='var(--border-color)'">
            </div>
            <x-custom-select name="status" :value="request('status')" placeholder="Semua Status" :options="[
                '' => 'Semua Status',
                'Draft' => 'Draft',
                'Sent' => 'Sent',
            ]" />
            <button type="submit"
                class="px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8);">
                Filter
            </button>
            @if(request()->hasAny(['search','status']))
            <a href="{{ route('quo.index') }}"
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
                        <th>No. Penawaran</th>
                        <th>Revisi</th>
                        <th>Customer</th>
                        <th>Sales Marketing</th>
                        <th>Total Item</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $quo)
                    <tr>
                        <td>
                            <code class="text-xs px-2 py-1 rounded font-semibold"
                                style="background: var(--bg-secondary); color: var(--text-secondary);">
                                {{ $quo->quo_number }}
                            </code>
                        </td>
                        <td style="color: var(--text-secondary);">{{ $quo->revision_number > 0 ? 'Rev #' . $quo->revision_number : '-' }}</td>
                        <td>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $quo->customer?->company_name ?? '-' }}</p>
                        </td>
                        <td style="color: var(--text-secondary);">{{ $quo->sales?->name ?? '-' }}</td>
                        <td style="color: var(--text-secondary);">{{ $quo->items->count() }} item</td>
                        <td>
                            @php
                                $badgeClass = $quo->isSent() ? 'approved' : 'pending';
                            @endphp
                            <span class="status-badge {{ $badgeClass }}">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                {{ $quo->status }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                {{-- Tombol lihat detail Quotation langsung --}}
                                <a href="{{ route('quo.show', $quo->id) }}" title="Buka Detail Quotation"
                                    class="p-1.5 rounded-lg transition-colors"
                                    style="color: var(--text-muted);"
                                    onmouseenter="this.style.background='rgba(13,148,136,0.08)'; this.style.color='var(--accent-teal, #0d9488)'"
                                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </a>
                                {{-- Tombol lihat RFQ terkait (jika ada) --}}
                                @if($quo->rfq_id)
                                <a href="{{ route('rfq.show', $quo->rfq_id) }}" title="Buka RFQ Terkait"
                                    class="p-1.5 rounded-lg transition-colors"
                                    style="color: var(--text-muted);"
                                    onmouseenter="this.style.background='rgba(37,99,235,0.08)'; this.style.color='var(--accent-blue)'"
                                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-12">
                            <p class="text-sm" style="color: var(--text-muted);">Belum ada quotation. @if(auth()->user()->isAdminOrAbove()) <a href="{{ route('quo.create') }}" style="color: var(--accent-blue);">Buat Quotation pertama</a>@endif</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quotations->hasPages())
        <div class="p-4" style="border-top: 1px solid var(--border-color);">
            {{ $quotations->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
