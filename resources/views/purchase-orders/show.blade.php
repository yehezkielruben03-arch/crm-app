<x-app-layout>

    {{-- Header --}}
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-start sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div class="flex items-center gap-3">
            <a href="{{ route('po.index') }}"
               class="p-2 rounded-lg transition-colors"
               style="color: var(--text-muted);"
               onmouseenter="this.style.background='var(--bg-secondary)'"
               onmouseleave="this.style.background=''">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="text-xl font-bold font-mono" style="color: var(--text-primary);">{{ $po->po_number }}</h1>
                <p class="text-sm mt-0.5" style="color: var(--text-muted);">{{ $po->customer->company_name ?? '-' }}</p>
            </div>
        </div>
        
        <div class="flex gap-2">
            {{-- Download PDF --}}
            @if($po->file_path)
            <a href="{{ route('po.download-pdf', $po) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all hover:-translate-y-0.5"
               style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Download PDF
            </a>
            @endif

            {{-- Sales: Request Perubahan (hanya jika Pending) --}}
            @if(auth()->user()->isSales() && $po->isPending())
                <form action="{{ route('po.request-change', $po) }}" method="POST" class="inline" onsubmit="return confirm('Ajukan permintaan perubahan PO ini?')">
                    @csrf
                    <button type="button"
                        onclick="document.getElementById('change-reason-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-amber), #d97706); box-shadow: 0 4px 12px rgba(217,119,6,0.30);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Request Perubahan
                    </button>
                </form>
            @endif

            {{-- Admin: Edit PO saat Revisi --}}
            @if(auth()->user()->isAdminOrAbove() && $po->isRevisi())
                <a href="{{ route('po.edit', $po) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                   style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit PO
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- Kiri: Detail Utama & Items --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Info PO --}}
            <div class="card p-6 animate-in" style="animation-delay: 0.1s;">
                <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                    Informasi PO
                </h2>
                <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Customer</dt>
                        <dd class="text-sm font-semibold" style="color: var(--text-primary);">{{ $po->customer->company_name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Sales Marketing</dt>
                        <dd class="text-sm font-medium" style="color: var(--text-secondary);">{{ $po->sales->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Tanggal PO</dt>
                        <dd class="text-sm" style="color: var(--text-secondary);">{{ $po->po_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs mb-0.5" style="color: var(--text-muted);">Due Date</dt>
                        <dd class="text-sm" style="color: var(--text-secondary);">{{ $po->due_date?->format('d M Y') ?? '-' }}</dd>
                    </div>
                </dl>

                @if($po->notes)
                <div class="mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                    <dt class="text-xs mb-1" style="color: var(--text-muted);">Catatan</dt>
                    <dd class="text-sm max-w-full break-words whitespace-normal leading-relaxed" style="color: var(--text-secondary);">{{ $po->notes }}</dd>
                </div>
                @endif
            </div>

            {{-- Item PO --}}
            <div class="card p-0 overflow-hidden animate-in" style="animation-delay: 0.15s;">
                <div class="p-6 pb-4">
                    <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Detail Item</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" style="color: var(--text-secondary);">
                        <thead style="background: var(--bg-secondary); color: var(--text-muted); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Nama Item</th>
                                <th class="px-6 py-3 font-semibold text-center">Qty</th>
                                <th class="px-6 py-3 font-semibold text-right">Harga Satuan</th>
                                <th class="px-6 py-3 font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y" style="border-color: var(--border-color);">
                            @forelse($po->items as $item)
                            <tr class="transition-colors hover:bg-slate-50/50">
                                <td class="px-6 py-4 font-medium" style="color: var(--text-primary);">{{ $item->item_name }}</td>
                                <td class="px-6 py-4 text-center">{{ number_format($item->quantity, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-right font-medium" style="color: var(--text-primary);">Rp {{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-muted">Belum ada item</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Kanan: Summary --}}
        <div class="space-y-5">
            <div class="card p-6 animate-in" style="animation-delay: 0.2s;">
                <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                    Ringkasan Biaya & Status
                </h2>
                <div class="space-y-3">
                    @php
                        $badgeClass = match($po->status) {
                            'Goal'               => 'approved',
                            'Pending'            => 'pending',
                            'Revisi'             => 'rejected',
                            'Tidak Goal'         => 'rejected',
                            default              => 'pending',
                        };
                    @endphp
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-sm" style="color: var(--text-muted);">Status</span>
                        <span class="status-badge {{ $badgeClass }}">
                            <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                            {{ $po->status }}
                        </span>
                    </div>

                    @if($po->isRevisi() && $po->change_request_reason)
                    <div class="mt-4 p-4 rounded-xl text-sm" role="alert"
                        style="background: rgba(217,119,6,0.08); border: 1px solid rgba(217,119,6,0.2); color: var(--accent-amber);">
                        <div class="flex items-start gap-2">
                            <svg class="w-5 h-5 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <div>
                                <span class="font-semibold">Alasan Perubahan:</span>
                                <p class="mt-1" style="color: var(--text-secondary);">{{ $po->change_request_reason }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--text-muted);">Subtotal</span>
                        <span style="color: var(--text-secondary);">Rp {{ number_format($po->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span style="color: var(--text-muted);">PPN</span>
                        <span style="color: var(--text-secondary);">Rp {{ number_format($po->tax_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-sm font-bold pt-3" style="border-top: 1px solid var(--border-color);">
                        <span style="color: var(--text-primary);">Grand Total</span>
                        <span style="color: var(--accent-blue);">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Alasan Perubahan --}}
    <div id="change-reason-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-lg mx-4 animate-in" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold mb-1" style="color: var(--text-primary);">Alasan Perubahan</h3>
            <p class="text-sm mb-4" style="color: var(--text-muted);">Jelaskan perubahan apa yang perlu dilakukan pada PO ini.</p>
            <form action="{{ route('po.request-change', $po) }}" method="POST">
                @csrf
                <textarea name="change_request_reason" rows="4" required minlength="10"
                    class="w-full px-4 py-3 text-sm rounded-xl outline-none transition-all resize-none"
                    style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-primary);"
                    placeholder="Contoh: Tolong ubah quantity item A dari 10 menjadi 15..."
                    onfocus="this.style.borderColor='var(--accent-blue)'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.10)'"
                    onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'"></textarea>
                <div class="flex justify-end gap-3 mt-4">
                    <button type="button" onclick="document.getElementById('change-reason-modal').classList.add('hidden')"
                        class="px-4 py-2 text-sm font-medium rounded-xl transition-colors"
                        style="background: var(--bg-secondary); color: var(--text-secondary);">
                        Batal
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-amber), #d97706); box-shadow: 0 4px 12px rgba(217,119,6,0.30);">
                        Kirim Permintaan
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-app-layout>
