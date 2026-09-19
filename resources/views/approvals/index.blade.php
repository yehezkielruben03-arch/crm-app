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

    <!-- RFQ MENUNGGU APPROVAL LEADER SECTION -->
    <section class="card p-5 animate-in mb-6" style="animation-delay: 0.15s;">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                </svg>
                RFQ Menunggu Approval Leader ({{ $pendingRfqs->count() }})
            </h2>
        </div>

        <div class="overflow-x-auto -mx-5 px-5">
            <table class="data-table text-sm w-full">
                <thead>
                    <tr>
                        <th class="py-2">No. RFQ</th>
                        <th class="py-2">Klien</th>
                        <th class="py-2">Tipe & Urgensi</th>
                        <th class="py-2">Sales Marketing</th>
                        <th class="py-2">Estimasi Total</th>
                        <th class="py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingRfqs as $rfq)
                    <tr>
                        <td class="py-3 font-medium">
                            <a href="{{ route('rfq.show', $rfq) }}" class="font-mono hover:underline" style="color: var(--accent-blue);">
                                {{ $rfq->rfq_number }}
                            </a>
                            <div class="text-[11px]" style="color: var(--text-muted);">{{ $rfq->created_at->format('d M Y H:i') }}</div>
                        </td>
                        <td class="py-3">
                            <div class="font-medium" style="color: var(--text-primary);">{{ $rfq->customer_name }}</div>
                            @if($rfq->customer_code)
                            <code class="text-[10px] px-1 py-0.5 rounded" style="background: rgba(37,99,235,0.06); color: var(--accent-blue);">{{ $rfq->customer_code }}</code>
                            @endif
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            <span class="inline-block px-2 py-0.5 text-xs rounded bg-slate-100 font-medium text-slate-700">{{ $rfq->type ?? 'Non Projek' }}</span>
                            @if($rfq->priority === 'Urgent' || $rfq->priority === 'High Priority')
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded text-rose-600 bg-rose-50 border border-rose-200">{{ $rfq->priority }}</span>
                            @endif
                        </td>
                        <td class="py-3" style="color: var(--text-secondary);">
                            {{ $rfq->sales_name }}
                        </td>
                        <td class="py-3 font-bold" style="color: var(--accent-blue);">
                            Rp {{ number_format($rfq->grand_total, 0, ',', '.') }}
                        </td>
                        <td class="py-3 text-right">
                            <div class="flex justify-end gap-2 items-center">
                                <a href="{{ route('rfq.price_form', $rfq) }}" class="action-btn text-xs px-2.5 py-1.5 inline-flex items-center gap-1" style="border-color: rgba(2,132,199,0.3); color: #0284c7;" title="Edit / Sesuaikan HPP">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    Edit HPP
                                </a>
                                <a href="{{ route('rfq.show', $rfq) }}" class="action-btn text-xs px-2.5 py-1.5 inline-flex items-center gap-1" style="border-color: rgba(37,99,235,0.3); color: var(--accent-blue);" title="Review Detail">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    Review
                                </a>
                                <form action="{{ route('rfq.approve', $rfq) }}" method="POST" class="inline-block" onsubmit="return confirm('Approve HPP untuk RFQ {{ $rfq->rfq_number }}? Status akan menjadi Approved.');">
                                    @csrf
                                    <button type="submit" class="action-btn text-xs px-2.5 py-1.5 inline-flex items-center gap-1" style="border-color: rgba(5,150,105,0.3); color: var(--accent-emerald);">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Approve
                                    </button>
                                </form>
                                <button type="button" onclick="openRejectModal('{{ $rfq->id }}', '{{ $rfq->rfq_number }}')" class="action-btn text-xs px-2.5 py-1.5 inline-flex items-center gap-1" style="border-color: rgba(225,29,72,0.3); color: var(--accent-rose);">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-6" style="color: var(--text-muted);">Tidak ada RFQ yang menunggu approval Leader.</td>
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

    <!-- MODAL REJECT RFQ -->
    <div id="rejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl border border-gray-100 animate-in">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Tolak & Minta Revisi HPP
                </h3>
                <button type="button" onclick="closeRejectModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-gray-600 mb-4" id="rejectModalSubtitle">Masukkan catatan revisi untuk Admin Purchase:</p>
            <form id="rejectForm" method="POST" action="">
                @csrf
                <div class="mb-4">
                    <label for="modalRevisionNotes" class="block text-xs font-semibold text-gray-700 mb-1">Alasan / Catatan Revisi:</label>
                    <textarea id="modalRevisionNotes" name="revision_notes" rows="4" required class="w-full text-sm border-gray-300 rounded-xl focus:ring-rose-500 focus:border-rose-500" placeholder="Contoh: Margin item hardware terlalu tipis, tolong nego vendor turun 5%"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closeRejectModal()" class="px-4 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-xl transition">Batal</button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-sm">Kirim & Minta Revisi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openRejectModal(rfqId, rfqNumber) {
            const modal = document.getElementById('rejectModal');
            const form = document.getElementById('rejectForm');
            const subtitle = document.getElementById('rejectModalSubtitle');
            const textarea = document.getElementById('modalRevisionNotes');

            form.action = '/rfqs/' + rfqId + '/reject';
            subtitle.textContent = 'Masukkan catatan revisi untuk Admin Purchase terkait RFQ ' + rfqNumber + ':';
            textarea.value = '';
            modal.classList.remove('hidden');
            setTimeout(() => textarea.focus(), 100);
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // Close on backdrop click
        document.getElementById('rejectModal')?.addEventListener('click', function(e) {
            if (e.target === this) closeRejectModal();
        });
    </script>

</x-app-layout>
