<x-app-layout>
    <div class="mb-6 animate-in" style="animation-delay: 0.05s;">
        <h1 class="text-xl font-bold" style="color: var(--text-primary);">Approval Center</h1>
        <p class="text-sm mt-0.5" style="color: var(--text-muted);">Pusat persetujuan untuk Klien dan Purchase Orders</p>
    </div>

    <!-- PENDING CUSTOMERS SECTION -->
    <section class="card p-5 animate-in mb-6" style="animation-delay: 0.1s;">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                Klien Menunggu Approval ({{ $pendingCustomers->count() }})
            </h2>
        </div>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="data-table text-sm w-full">
                <thead>
                    <tr>
                        <th class="py-2">Perusahaan</th>
                        <th class="py-2">Kontak</th>
                        <th class="py-2">Sales Marketing</th>
                        <th class="py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingCustomers as $customer)
                    <tr>
                        <td class="py-3">
                            <div class="font-medium" style="color: var(--text-primary);">{{ $customer->company_name }}</div>
                            <div class="text-[11px]" style="color: var(--text-muted);">{{ $customer->email }}</div>
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            {{ $customer->primaryContact()->name ?? 'Belum ada kontak utama' }}<br>
                            <span class="text-[11px]">{{ $customer->primaryContact()->phone ?? $customer->phone ?? 'Belum ada telepon' }}</span>
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            {{ $customer->sales->name ?? 'Belum ditugaskan' }}
                        </td>
                        <td class="py-3 text-right">
                            <form action="{{ route('customers.approve', $customer) }}" method="POST" class="inline-block" onsubmit="return confirm('Setujui pelanggan ini? Status akan berubah menjadi Active dan kode perusahaan akan dibuat otomatis.');">
                                @csrf
                                <button type="submit" class="action-btn text-xs px-3 py-1.5" style="border-color: rgba(5,150,105,0.3); color: var(--accent-emerald);">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Approve
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center py-6" style="color: var(--text-muted);">Tidak ada klien yang menunggu approval.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- PENDING PURCHASE ORDERS SECTION -->
    <section class="card p-5 animate-in" style="animation-delay: 0.2s;">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Purchase Orders Menunggu Review ({{ $pendingPOs->count() }})
            </h2>
        </div>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="data-table text-sm w-full">
                <thead>
                    <tr>
                        <th class="py-2">No. PO</th>
                        <th class="py-2">Klien</th>
                        <th class="py-2">Grand Total</th>
                        <th class="py-2">Sales Marketing</th>
                        <th class="py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingPOs as $po)
                    <tr>
                        <td class="py-3 font-medium" style="color: var(--text-primary);">
                            <a href="{{ route('po.show', $po) }}" class="hover:underline" style="color: var(--accent-blue);">
                                {{ $po->po_number }}
                            </a>
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            {{ $po->customer->company_name ?? '-' }}
                        </td>
                        <td class="py-3 font-semibold" style="color: var(--text-primary);">
                            Rp {{ number_format($po->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            {{ $po->sales->name ?? 'N/A' }}
                        </td>
                        <td class="py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <form action="{{ route('po.approve', $po) }}" method="POST" onsubmit="return confirm('Approve PO ini?');">
                                    @csrf
                                    <button type="submit" class="action-btn text-xs px-3 py-1.5" style="border-color: rgba(5,150,105,0.3); color: var(--accent-emerald);">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Approve
                                    </button>
                                </form>
                                <form action="{{ route('po.reject', $po) }}" method="POST" onsubmit="return confirm('Reject PO ini?');">
                                    @csrf
                                    <button type="submit" class="action-btn text-xs px-3 py-1.5" style="border-color: rgba(225,29,72,0.3); color: var(--accent-rose);">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-rose);">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Reject
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-6" style="color: var(--text-muted);">Tidak ada PO yang menunggu review.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- REVISI PURCHASE ORDERS SECTION --}}
    <section class="card p-5 animate-in mt-6" style="animation-delay: 0.3s;">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                PO Perlu Direvisi ({{ $revisiPOs->count() }})
            </h2>
        </div>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="data-table text-sm w-full">
                <thead>
                    <tr>
                        <th class="py-2">No. PO</th>
                        <th class="py-2">Klien</th>
                        <th class="py-2">Sales Marketing</th>
                        <th class="py-2">Alasan Perubahan</th>
                        <th class="py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($revisiPOs as $po)
                    <tr>
                        <td class="py-3 font-medium" style="color: var(--text-primary);">
                            <a href="{{ route('po.show', $po) }}" class="hover:underline" style="color: var(--accent-blue);">
                                {{ $po->po_number }}
                            </a>
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            {{ $po->customer->company_name ?? '-' }}
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            {{ $po->sales->name ?? 'N/A' }}
                        </td>
                        <td class="py-3" style="color: var(--text-secondary); max-width: 250px;">
                            <p class="truncate text-xs" title="{{ $po->change_request_reason }}">{{ $po->change_request_reason ?? '-' }}</p>
                        </td>
                        <td class="py-3 text-right">
                            <a href="{{ route('po.edit', $po) }}"
                                class="action-btn text-xs px-3 py-1.5 inline-flex items-center gap-1"
                                style="border-color: rgba(37,99,235,0.3); color: var(--accent-blue);">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit PO
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-6" style="color: var(--text-muted);">Tidak ada PO yang perlu direvisi.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

</x-app-layout>
