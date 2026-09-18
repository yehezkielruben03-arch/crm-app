<x-app-layout>

    <div class="flex items-center gap-3 mb-6 animate-in" style="animation-delay: 0.05s;">
        <a href="{{ route('quo.index') }}"
           class="p-2 rounded-lg transition-colors"
           style="color: var(--text-muted);"
           onmouseenter="this.style.background='var(--bg-secondary)'"
           onmouseleave="this.style.background=''">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">
                Penawaran {{ $quo->rfq_number }}
            </h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">
                Dibuat {{ $quo->created_at->format('d M Y H:i') }}
            </p>
        </div>
    </div>

    {{-- Action Bar --}}
    <div class="flex flex-wrap gap-2 mb-5 animate-in" style="animation-delay: 0.1s;">
        @if($quo->rfq)
        <a href="{{ route('rfq.download_quotation', $quo->rfq) }}" target="_blank"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all hover:-translate-y-0.5"
            style="background: rgba(239,68,68,0.08); border: 1px solid rgba(239,68,68,0.2); color: #dc2626;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Export PDF
        </a>
        @endif

        @if($quo->status === \App\Models\Quotation::STATUS_SENT && (auth()->user()->isLeader() || auth()->user()->isSuperAdmin()))
        <form action="{{ route('quo.approve', $quo) }}" method="POST" class="inline-block">
            @csrf
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white rounded-xl transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 12px rgba(16,185,129,0.25);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Approve Quotation
            </button>
        </form>
        @endif

        @if($quo->status === \App\Models\Quotation::STATUS_APPROVED && auth()->user()->isSales() && $quo->rfq)
        <button type="button" onclick="document.getElementById('goalModal').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-bold text-white rounded-xl transition-all hover:-translate-y-0.5"
            style="background: linear-gradient(135deg, #2563eb, #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.3);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Terima PO & Proses GOAL
        </button>
        @endif
    </div>

    {{-- GOAL Modal (Untuk Sales) --}}
    @if($quo->status === \App\Models\Rfq::STATUS_APPROVED && auth()->user()->isSales())
    <div id="goalModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 backdrop-blur-sm" onclick="if(event.target===this)this.classList.add('hidden')">
        <div class="card p-6 w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold mb-2" style="color: var(--text-primary);">Proses GOAL & Terima PO</h3>
            <p class="text-xs mb-4" style="color: var(--text-muted);">Pastikan QTY sesuai dengan PO dari customer sebelum disubmit.</p>
            
            <form action="{{ route('rfq.approve_goal', $quo->rfq) }}" method="POST" enctype="multipart/form-data">
                @csrf
                
                {{-- Upload PO --}}
                <div class="mb-5">
                    <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                        File Bukti PO (PDF/Image)
                        <span style="color: var(--accent-rose);">*</span>
                        <span class="ml-1 text-[10px] font-normal px-1.5 py-0.5 rounded-full" style="background: rgba(245,158,11,0.12); color: #d97706;">Maks. 5 MB</span>
                    </label>
                    <input type="file" name="po_file" id="po_file_input" required accept=".pdf,.jpg,.jpeg,.png"
                        class="form-input" style="width: 100%; padding: 0.5rem; border-radius: 0.75rem; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                        onchange="validatePoFileSize(this)">
                    {{-- Pesan validasi real-time (muncul sebelum submit) --}}
                    <div id="po_file_error" class="hidden mt-2 p-3 rounded-lg text-xs" style="background: rgba(225,29,72,0.08); border: 1px solid rgba(225,29,72,0.2); color: #e11d48;">
                        <strong>⚠️ File terlalu besar!</strong> Maksimal 5 MB.<br>
                        Kompres PDF kamu dulu di
                        <a href="https://ilovepdf.com/compress_pdf" target="_blank" style="color: #2563eb; text-decoration: underline; font-weight: 600;">ilovepdf.com</a>
                        (gratis, tidak perlu daftar), lalu upload lagi.
                    </div>
                    <p class="mt-1.5 text-[10px]" style="color: var(--text-muted);">
                        Format: PDF, JPG, JPEG, PNG &bull; Maksimal <strong>5 MB</strong>.
                        File besar? Kompres di
                        <a href="https://ilovepdf.com/compress_pdf" target="_blank" style="color: var(--accent-blue);">ilovepdf.com</a>
                    </p>
                </div>
                {{-- Penyesuaian QTY --}}
                <div class="mb-5">
                    <label class="block text-xs font-semibold mb-2" style="color: var(--text-secondary);">Penyesuaian QTY Final</label>
                    <div class="space-y-3">
                        @foreach($quo->items as $item)
                        <div class="flex items-center gap-3 p-3 rounded-lg" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                            <div class="flex-1">
                                <p class="text-sm font-semibold" style="color: var(--text-primary);">{{ $item->product_name }}</p>
                                <p class="text-xs" style="color: var(--text-muted);">{{ $item->description ?? 'Tanpa deskripsi' }}</p>
                            </div>
                            <div class="w-32">
                                <label class="block text-[10px] uppercase tracking-wider mb-1" style="color: var(--text-muted);">QTY Akhir</label>
                                <input type="number" name="items[{{ $item->id }}][qty]" required min="0" step="0.01" value="{{ (float)$item->qty }}"
                                    class="form-input text-center" style="width: 100%; padding: 0.4rem; border-radius: 0.5rem; background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div class="w-20 text-right">
                                <label class="block text-[10px] uppercase tracking-wider mb-1" style="color: var(--text-muted);">Satuan</label>
                                <p class="text-sm" style="color: var(--text-secondary);">{{ $item->unit }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2 justify-end">
                    <button type="button" onclick="document.getElementById('goalModal').classList.add('hidden')"
                        class="px-4 py-2 text-sm rounded-xl"
                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">Batal</button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-bold text-white rounded-xl transition-transform hover:scale-105"
                        style="background: linear-gradient(135deg, #2563eb, #1d4ed8);">
                        Submit GOAL
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

        {{-- Main Content --}}
        <div class="lg:col-span-3 space-y-5">
            {{-- Info Card --}}
            <div class="card p-6 animate-in" style="animation-delay: 0.15s;">
                <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                    Informasi Penawaran
                </h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-xs" style="color: var(--text-muted);">No. Penawaran</p>
                        <p class="font-semibold" style="color: var(--text-primary);">{{ $quo->rfq_number }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-muted);">Customer</p>
                        <p class="font-medium" style="color: var(--text-primary);">{{ $quo->customer?->company_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-muted);">Sales Marketing</p>
                        <p style="color: var(--text-secondary);">{{ $quo->sales?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs" style="color: var(--text-muted);">Status</p>
                        @php
                            $badgeClass = match($quo->status) {
                                \App\Models\Rfq::STATUS_GOAL            => 'approved',
                                \App\Models\Rfq::STATUS_APPROVED        => 'pending',
                                \App\Models\Rfq::STATUS_QUOTATION_CREATED => 'pending',
                                \App\Models\Rfq::STATUS_PO_PENDING_ADMIN  => 'warning',
                                \App\Models\Rfq::STATUS_PO_PENDING_LEADER => 'warning',
                                default                                 => 'pending',
                            };
                        @endphp
                        <span class="status-badge {{ $badgeClass }}">
                            <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                            {{ $quo->status }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Items Table --}}
            <div class="card p-6 animate-in" style="animation-delay: 0.2s;">
                <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                    Detail Item ({{ $quo->items->count() }})
                </h2>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Produk</th>
                                <th>Deskripsi</th>
                                <th>Qty</th>
                                <th>Satuan</th>
                                <th>Harga Satuan</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotal = 0; @endphp
                            @foreach($quo->items as $idx => $item)
                            @php
                                $totalPrice = $item->qty * $item->price_after_margin;
                                $grandTotal += $totalPrice;
                            @endphp
                            <tr>
                                <td style="color: var(--text-muted);">{{ $idx + 1 }}</td>
                                <td><span class="font-medium" style="color: var(--text-primary);">{{ $item->product_name }}</span></td>
                                <td style="color: var(--text-secondary); max-width: 200px;">{{ $item->description ?? '-' }}</td>
                                <td style="color: var(--text-secondary);">{{ (float)$item->qty }}</td>
                                <td style="color: var(--text-secondary);">{{ $item->unit ?? '-' }}</td>
                                <td style="color: var(--text-secondary);">Rp {{ number_format($item->price_after_margin, 0, ',', '.') }}</td>
                                <td class="font-semibold" style="color: var(--text-primary);">Rp {{ number_format($totalPrice, 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-right font-bold" style="color: var(--text-primary);">Grand Total</td>
                                <td class="font-bold text-lg" style="color: var(--accent-blue);">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

    </div>

</x-app-layout>

@push('scripts')
<script>
/**
 * Validasi ukuran file PO secara real-time di browser.
 *
 * Cara kerja:
 * 1. Sales pilih file → fungsi ini langsung dipanggil (onchange)
 * 2. Kita cek ukuran file dalam bytes, lalu konversi ke KB (bagi 1024)
 * 3. Kalau > 2048 KB (2 MB) → tampilkan pesan error + disable tombol Submit
 * 4. Kalau oke → sembunyikan pesan error + enable tombol Submit lagi
 *
 * Ini hanya validasi sisi browser (client-side) — untuk kenyamanan user.
 * Validasi sesungguhnya (yang tidak bisa dibypass) ada di server (Laravel).
 */
function validatePoFileSize(input) {
    const maxSizeKb  = 5120;           // 5 MB dalam KB
    const errorBox   = document.getElementById('po_file_error');
    const submitBtn  = input.closest('form').querySelector('button[type="submit"]');

    if (!input.files || input.files.length === 0) return;

    const fileSizeKb = input.files[0].size / 1024; // bytes → KB

    if (fileSizeKb > maxSizeKb) {
        // File terlalu besar — tampilkan peringatan, blokir submit
        errorBox.classList.remove('hidden');
        input.style.borderColor = '#e11d48';  // border merah
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.5';
            submitBtn.style.cursor  = 'not-allowed';
        }
    } else {
        // File OK — sembunyikan peringatan, aktifkan kembali submit
        errorBox.classList.add('hidden');
        input.style.borderColor = '';         // kembali ke warna normal
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.style.opacity = '';
            submitBtn.style.cursor  = '';
        }
    }
}
</script>
@endpush
