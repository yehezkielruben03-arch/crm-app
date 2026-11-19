<div
    x-data="{
        open: false,
        downloading: false,
        startDownload() {
            this.downloading = true;
            setTimeout(() => {
                this.downloading = false;
                this.open = false;
            }, 1200);
        }
    }"
    @keydown.escape.window="if (open) open = false"
    class="inline-flex"
>
    <!-- Trigger Button -->
    <button
        type="button"
        @click="open = true"
        class="inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/40 cursor-pointer"
        style="background: rgba(5,150,105,0.10); color: #047857; border: 1px solid rgba(5,150,105,0.28);"
        aria-haspopup="dialog"
        :aria-expanded="open.toString()"
    >
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <span>{{ $buttonLabel }}</span>
    </button>

    <!-- Export Modal (Teleported to body so card transforms/overflow never clip it) -->
    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[1000] flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="{{ $modalId }}-title"
        >
            <!-- Backdrop -->
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="open = false"
                class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"
            ></div>

            <!-- Modal Panel -->
            <div
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                class="relative w-full max-w-md rounded-2xl shadow-2xl overflow-hidden"
                style="background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0);"
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color: var(--border-color, #e2e8f0); background: var(--bg-secondary, #f8fafc);">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background: rgba(5,150,105,0.12); color: #059669;">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 id="{{ $modalId }}-title" class="text-base font-bold leading-tight" style="color: var(--text-primary, #0f172a);">Export Laporan PO Bulanan</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted, #64748b);">Unduh rekap PO GOAL dalam format Excel (.xlsx)</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="p-1.5 rounded-lg transition-colors hover:bg-slate-200/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                        style="color: var(--text-muted, #64748b);"
                        aria-label="Tutup modal export"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form method="GET" action="{{ route('po.export') }}" @submit="startDownload()" class="p-5 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <!-- Pilih Bulan -->
                        <div>
                            <label for="{{ $modalId }}-month" class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary, #334155);">
                                Bulan
                            </label>
                            <select
                                id="{{ $modalId }}-month"
                                name="month"
                                required
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl outline-none transition-all focus:ring-2 focus:ring-emerald-500/25"
                                style="background: var(--bg-secondary, #f8fafc); border: 1.5px solid var(--border-color, #cbd5e1); color: var(--text-primary, #0f172a);"
                            >
                                @foreach($months as $num => $label)
                                    <option value="{{ $num }}" @selected($num === $defaultMonth)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Pilih Tahun -->
                        <div>
                            <label for="{{ $modalId }}-year" class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary, #334155);">
                                Tahun
                            </label>
                            <select
                                id="{{ $modalId }}-year"
                                name="year"
                                required
                                class="w-full px-3.5 py-2.5 text-sm rounded-xl outline-none transition-all focus:ring-2 focus:ring-emerald-500/25"
                                style="background: var(--bg-secondary, #f8fafc); border: 1.5px solid var(--border-color, #cbd5e1); color: var(--text-primary, #0f172a);"
                            >
                                @foreach($years as $yr)
                                    <option value="{{ $yr }}" @selected($yr === $defaultYear)>{{ $yr }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($canFilterSales)
                    <!-- Filter Sales (Khusus Admin, Admin Purchase, Leader, Super Admin) -->
                    <div>
                        <label for="{{ $modalId }}-sales" class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary, #334155);">
                            Sales Marketing
                        </label>
                        <select
                            id="{{ $modalId }}-sales"
                            name="sales_id"
                            class="w-full px-3.5 py-2.5 text-sm rounded-xl outline-none transition-all focus:ring-2 focus:ring-emerald-500/25"
                            style="background: var(--bg-secondary, #f8fafc); border: 1.5px solid var(--border-color, #cbd5e1); color: var(--text-primary, #0f172a);"
                        >
                            <option value="">Semua Sales (Multi-Sheet: Rekap + Per Sales)</option>
                            @foreach($salesList as $sales)
                                <option value="{{ $sales->id }}">{{ $sales->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <!-- Info Format Output -->
                    <div class="rounded-xl p-3 text-xs leading-relaxed" style="background: rgba(5,150,105,0.06); border: 1px solid rgba(5,150,105,0.18); color: #065f46;">
                        <p class="font-semibold mb-1">Format Kolom Excel:</p>
                        <p><strong>Tanggal</strong> &bull; <strong>Nama PT</strong> &bull; <strong>Nama Item</strong> (1 PO = 1 baris) &bull; <strong>Harga Exclude</strong> (DPP sebelum PPN).</p>
                        @if($canFilterSales)
                        <p class="mt-1 opacity-90">Pilih <strong>Semua Sales</strong> untuk membuat Multi-Sheet otomatis (Sheet Rekap + sheet terpisah per Sales).</p>
                        @endif
                    </div>

                    <!-- Footer Actions -->
                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="open = false"
                            class="px-4 py-2.5 text-sm font-medium rounded-xl transition-colors hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400/40"
                            style="background: var(--bg-secondary, #f8fafc); border: 1px solid var(--border-color, #cbd5e1); color: var(--text-secondary, #334155);"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="downloading"
                            class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md disabled:opacity-60 disabled:cursor-wait focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/50 cursor-pointer"
                            style="background: linear-gradient(135deg, #059669, #047857); box-shadow: 0 4px 12px rgba(5,150,105,0.28);"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <span x-text="downloading ? 'Mengunduh...' : 'Download Excel (.xlsx)'">Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
