<x-app-layout>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-2xl font-bold" style="color: var(--text-primary);">History Penjualan</h1>
            <p class="text-sm mt-1" style="color: var(--text-muted);">Daftar Quotation yang sudah deal (GOAL)</p>
        </div>
    </div>

    <!-- Search / Filter -->
    <div class="card p-4 mb-6 animate-in" style="animation-delay: 0.1s;">
        <form method="GET" action="{{ route('sales.history') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-5 h-5" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Cari No. RFQ atau Customer..."
                       class="w-full pl-10 pr-4 py-2 rounded-xl transition-colors"
                       style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); outline: none;"
                       onfocus="this.style.borderColor='var(--accent-blue)'"
                       onblur="this.style.borderColor='var(--border-color)'">
            </div>
            <button type="submit"
                class="px-5 py-2 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                Cari
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="card p-0 overflow-hidden animate-in" style="animation-delay: 0.2s;">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm" style="color: var(--text-secondary);">
                <thead style="background: var(--bg-secondary); color: var(--text-muted); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">
                    <tr>
                        <th class="px-6 py-4 font-semibold border-b" style="border-color: var(--border-color);">No. Quotation</th>
                        <th class="px-6 py-4 font-semibold border-b" style="border-color: var(--border-color);">Customer</th>
                        <th class="px-6 py-4 font-semibold border-b" style="border-color: var(--border-color);">Tgl. Dibutuhkan</th>
                        <th class="px-6 py-4 font-semibold border-b" style="border-color: var(--border-color);">Total Item</th>
                        <th class="px-6 py-4 font-semibold border-b" style="border-color: var(--border-color);">Nilai Deal</th>
                        <th class="px-6 py-4 font-semibold border-b text-right" style="border-color: var(--border-color);">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--border-color);">
                    @forelse($rfqs as $rfq)
                    <tr class="transition-colors hover:bg-slate-50/50">
                        <td class="px-6 py-4 font-medium" style="color: var(--text-primary);">
                            {{ str_replace('RFQ', 'QUO', $rfq->rfq_number) }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-medium" style="color: var(--text-primary);">{{ $rfq->customer->company_name ?? $rfq->customer_name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            {{ $rfq->need_date ? \Carbon\Carbon::parse($rfq->need_date)->format('d M Y') : '-' }}
                        </td>
                        <td class="px-6 py-4">
                            {{ number_format($rfq->items->sum('qty'), 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 font-semibold" style="color: var(--accent-emerald);">
                            Rp {{ number_format($rfq->items->sum(function($item) { return $item->price_after_margin * $item->qty; }), 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('rfq.show', $rfq) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                               style="background: rgba(37,99,235,0.1); color: var(--accent-blue); border: 1px solid rgba(37,99,235,0.2);">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center" style="color: var(--text-muted);">
                            <div class="flex flex-col items-center justify-center">
                                <svg class="w-12 h-12 mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                </svg>
                                <p>Belum ada history penjualan (GOAL).</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rfqs->hasPages())
        <div class="p-4 border-t" style="border-color: var(--border-color);">
            {{ $rfqs->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
