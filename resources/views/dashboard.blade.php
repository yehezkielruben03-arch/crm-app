<x-app-layout>

{{-- ════════════════════════════════════════
     DASHBOARD SALES MARKETING — role = Sales Marketing
     ════════════════════════════════════════ --}}
@if($isDashboardSales)

    <!-- Stats Row: Sales Marketing Metrics -->
    <section class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4 animate-in mb-6" style="animation-delay: 0.1s;">
        <!-- Total Klien -->
        <div class="card stat-card blue p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Total Klien</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(37,99,235,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalMyCustomers) }}</p>
            <div class="flex items-center gap-1 text-[11px] growth-positive">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                {{ $activeCustomers }} Active
            </div>
        </div>

        <!-- Follow Up Klien -->
        <div class="card stat-card amber p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Follow Up</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($customerHealthStats->followUp ?? 0) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">> 3 bln (Tanpa GOAL)</div>
        </div>

        <!-- Tidak Aktif -->
        <div class="card stat-card rose p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Tidak Aktif</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(225,29,72,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-rose);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($customerHealthStats->inactive ?? 0) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">> 12 bln (Tanpa GOAL)</div>
        </div>

        <!-- Klien Pending -->
        <div class="card stat-card amber p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Klien Pending</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($pendingCustomers) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">Menunggu approval</div>
        </div>

        <!-- Total PO -->
        <div class="card stat-card violet p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Total PO</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124,58,237,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalMyPO) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--accent-violet);">{{ $submittedPO }} Submitted</div>
        </div>

        <!-- Quotation Diterima -->
        <div class="card stat-card emerald p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Quotation Masuk</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(5,150,105,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalMyQuo) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">{{ $sentQuo }} sudah dikirim</div>
        </div>
    </section>

    <!-- Customer Health Tables (Sales) -->
    <div class="grid lg:grid-cols-2 gap-5 mb-6">
        <!-- Pelanggan Follow Up -->
        <section class="card p-5 animate-in" style="animation-delay: 0.55s;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    Perlu Follow Up (>3 bln)
                </h2>
            </div>
            <div class="overflow-x-auto -mx-5 px-5">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="py-2">Perusahaan</th>
                            <th class="py-2">Transaksi Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerHealthStats->followUpList as $fu)
                        <tr>
                            <td class="py-2 font-medium" style="color: var(--text-primary);">{{ $fu->company_name }}</td>
                            <td class="py-2" style="color: var(--text-secondary);">{{ \Carbon\Carbon::parse($fu->last_po)->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-center py-6" style="color: var(--text-muted);">Semua Klien Terkelola Baik</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Pelanggan Tidak Aktif -->
        <section class="card p-5 animate-in" style="animation-delay: 0.6s;">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-rose);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Klien Tidak Aktif (>12 bln)
                </h2>
            </div>
            <div class="overflow-x-auto -mx-5 px-5">
                <table class="data-table text-sm">
                    <thead>
                        <tr>
                            <th class="py-2">Perusahaan</th>
                            <th class="py-2">Transaksi Terakhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customerHealthStats->inactiveList as $ia)
                        <tr>
                            <td class="py-2 font-medium" style="color: var(--text-primary);">{{ $ia->company_name }}</td>
                            <td class="py-2" style="color: var(--text-secondary);">{{ \Carbon\Carbon::parse($ia->last_po)->format('d M Y') }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="2" class="text-center py-6" style="color: var(--text-muted);">Tidak ada pelanggan pasif</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>


    <!-- Gabungan Target & Grafik Atas -->
    <div class="grid lg:grid-cols-3 gap-5 mb-6 animate-in" style="animation-delay: 0.15s;">
        <!-- Target Bulan Ini (Combined) -->
        <section class="card p-5 flex flex-col h-full">
            <h2 class="text-base font-bold mb-5 flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Target Bulan Ini
            </h2>

            <div class="flex-1 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <p class="text-[10px] uppercase tracking-wide font-semibold mb-1" style="color: var(--text-muted);">Lead Baru</p>
                        <p class="text-lg font-bold" style="color: var(--text-primary);">{{ number_format($todayNewCustomers) }}</p>
                    </div>
                    <div class="p-3 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                        <p class="text-[10px] uppercase tracking-wide font-semibold mb-1" style="color: var(--text-muted);">RFQ Baru</p>
                        <p class="text-lg font-bold" style="color: var(--text-primary);">{{ number_format($todayNewRFQ) }}</p>
                    </div>
                </div>

                <!-- Revenue Bar -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs" style="color: var(--text-secondary);">Achievement</span>
                        <span class="text-xs font-semibold" style="color: var(--accent-emerald);">Rp {{ number_format($revenueThisMonth, 0, ',', '.') }}</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill bg-gradient-to-r from-emerald-500 to-teal-400" data-width="{{ $achievementPct }}"></div>
                    </div>
                </div>

                <!-- Progress Circle Block -->
                <div class="p-3.5 rounded-xl flex items-center justify-between" style="background: linear-gradient(135deg, rgba(37,99,235,0.06), rgba(124,58,237,0.06)); border: 1px solid var(--border-color);">
                    <div>
                        <p class="text-[10px] uppercase tracking-wide mb-0.5" style="color: var(--text-muted);">Progress Capaian</p>
                        <p class="text-2xl font-bold" style="color: var(--text-primary);">{{ $achievementPct }}%</p>
                        <p class="text-[10px] mt-1" style="color: var(--accent-emerald);">{{ number_format($goalCountThisMonth) }} GOAL</p>
                    </div>
                    <div class="w-14 h-14 relative">
                        <svg class="w-full h-full -rotate-90" viewBox="0 0 36 36">
                            <circle cx="18" cy="18" r="15" fill="none" stroke="var(--border-color)" stroke-width="2.5"/>
                            <circle cx="18" cy="18" r="15" fill="none" stroke="url(#pgSalesGrad)" stroke-width="2.5"
                                stroke-dasharray="94.25" stroke-dashoffset="{{ 94.25 - (94.25 * min($achievementPct, 100) / 100) }}" stroke-linecap="round"/>
                            <defs>
                                <linearGradient id="pgSalesGrad" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" stop-color="#2563eb"/>
                                    <stop offset="100%" stop-color="#7c3aed"/>
                                </linearGradient>
                            </defs>
                        </svg>
                    </div>
                </div>

                <!-- Target Amount -->
                <div class="flex items-center justify-between p-3.5 rounded-xl" style="background: rgba(217,119,6,0.08); border: 1px solid rgba(217,119,6,0.20);">
                    <div>
                        <p class="text-[10px] uppercase" style="color: var(--text-muted);">Target Rp</p>
                        <p class="text-sm font-bold" style="color: var(--text-primary);">
                            {{ $targetThisMonth > 0 ? 'Rp ' . number_format($targetThisMonth, 0, ',', '.') : 'Belum diset' }}
                        </p>
                    </div>
                    @if($achievementPct >= 100)
                        <span class="text-[10px] font-medium px-2 py-1 rounded-full" style="background: rgba(5,150,105,0.12); color: var(--accent-emerald);">🎉 Goal!</span>
                    @endif
                </div>
            </div>
        </section>

        <!-- Revenue Chart -->
        <section class="lg:col-span-2 card p-5 flex flex-col h-full">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    Grafik Penawaran vs GOAL
                </h2>
            </div>
            <div class="chart-container flex-1 relative" style="min-height: 250px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </section>
    </div>

    <!-- Layout Bawah (Pipeline Kiri, Deal Kanan) -->
    <div class="grid lg:grid-cols-3 gap-5 mb-6 animate-in" style="animation-delay: 0.25s;">
        
        <!-- RFQ Pipeline (Kiri, lebih lebar) -->
        <section class="lg:col-span-2 card p-5 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Pipeline RFQ & Quotation Pending
                </h2>
                <a href="{{ route('rfq.index') }}" class="text-xs font-medium transition-colors" style="color: var(--accent-blue);">Lihat Semua</a>
            </div>

            <!-- Quotation Pending Kecil di atas pipeline -->
            <div class="mb-4 p-3 rounded-xl flex items-center justify-between" style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.20);">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full" style="background: var(--accent-amber);"></div>
                    <span class="text-xs font-semibold" style="color: var(--text-secondary);">Quotation Pending / Approved</span>
                </div>
                <span class="text-sm font-bold" style="color: var(--text-primary);">{{ number_format($pendingQuotationCount) }} RFQ</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 flex-1">
                <div class="p-3 rounded-xl text-center flex flex-col justify-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <p class="text-xl font-bold" style="color: var(--accent-amber);">{{ number_format($rfqPipeline->pending_admin ?? 0) }}</p>
                    <p class="text-[10px]" style="color: var(--text-muted);">Pending Admin</p>
                </div>
                <div class="p-3 rounded-xl text-center flex flex-col justify-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <p class="text-xl font-bold" style="color: var(--accent-rose);">{{ number_format($rfqPipeline->pending_leader ?? 0) }}</p>
                    <p class="text-[10px]" style="color: var(--text-muted);">Pending Leader</p>
                </div>
                <div class="p-3 rounded-xl text-center flex flex-col justify-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <p class="text-xl font-bold" style="color: var(--accent-blue);">{{ number_format($rfqPipeline->approved ?? 0) }}</p>
                    <p class="text-[10px]" style="color: var(--text-muted);">Approved</p>
                </div>
                <div class="p-3 rounded-xl text-center flex flex-col justify-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                    <p class="text-xl font-bold" style="color: var(--accent-violet);">{{ number_format($rfqPipeline->quotation_created ?? 0) }}</p>
                    <p class="text-[10px]" style="color: var(--text-muted);">Quotation</p>
                </div>
                <div class="p-3 rounded-xl text-center flex flex-col justify-center" style="background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.20);">
                    <p class="text-xl font-bold" style="color: var(--accent-emerald);">{{ number_format($rfqPipeline->goal ?? 0) }}</p>
                    <p class="text-[10px]" style="color: var(--text-muted);">GOAL</p>
                </div>
            </div>
        </section>

        <!-- Deal Terbaru (Kanan, lebih sempit) -->
        <section class="card p-4 flex flex-col h-full">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-cyan);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Deal Terbaru
                </h2>
                <a href="{{ route('sales.history') }}" class="text-[10px] font-medium transition-colors" style="color: var(--accent-blue);">Lihat</a>
            </div>
            <div class="flex-1 overflow-y-auto pr-1" style="max-height: 240px;">
                <div class="space-y-3">
                    @forelse($recentOrders as $order)
                        <div class="p-3 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                            <div class="flex justify-between items-start mb-1.5">
                                <a href="{{ route('rfq.show', $order) }}" class="text-xs font-bold hover:underline truncate mr-2" style="color: var(--text-primary); max-width: 140px;">
                                    {{ $order->customer->company_name ?? '-' }}
                                </a>
                                <span class="text-[10px] px-1.5 py-0.5 rounded text-white bg-emerald-500 font-semibold">
                                    GOAL
                                </span>
                            </div>
                            <div class="flex justify-between items-end">
                                <span class="text-[10px]" style="color: var(--text-muted);">{{ str_replace('RFQ', 'QUO', $order->rfq_number) }}</span>
                                <span class="text-xs font-bold" style="color: var(--accent-blue);">Rp{{ number_format($order->grand_total/1000000, 1, ',', '.') }}Jt</span>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <p class="text-xs" style="color: var(--text-muted);">Belum ada Deal (GOAL)</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
        
    </div>

{{-- ════════════════════════════════════════
     DASHBOARD ADMIN — role = Admin / Super Admin
     ════════════════════════════════════════ --}}
@else

    <!-- Row 1: Ringkasan Finansial & Bisnis (4 Cards) -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 animate-in mb-4" style="animation-delay: 0.1s;">
        <!-- Revenue Bulan Ini -->
        <div class="card stat-card emerald p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Revenue</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(5,150,105,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">Rp {{ number_format($revenueThisMonth, 0, ',', '.') }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--accent-emerald);">Bulan Ini (GOAL)</div>
        </div>

        <!-- GOAL & Konversi -->
        <div class="card stat-card blue p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Deal Closing</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(37,99,235,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($goalCountThisMonth) }} GOAL</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">Konversi: <span class="font-semibold text-blue-600">{{ $conversionRate }}%</span></div>
        </div>

        <!-- Total Customers -->
        <div class="card stat-card blue p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Total Klien</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(37,99,235,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalCustomers) }}</p>
            <div class="flex items-center gap-1 text-[11px] growth-positive">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                Semua Mitra Perusahaan
            </div>
        </div>

        <!-- Total RFQ & Request Items -->
        <div class="card stat-card cyan p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Total Permintaan RFQ</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(13,148,136,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #0d9488;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalRFQ) }} RFQ</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">{{ number_format($totalRfqItems) }} total item penawaran</div>
        </div>
    </section>

    <!-- Row 2: Antrean Operasional & Approval (4 Cards Clickable) -->
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-4 animate-in mb-6" style="animation-delay: 0.12s;">
        <!-- Pending RFQ Leader (FOKUS UTAMA AUTH-03) -->
        <a href="{{ route('rfq.index', ['status' => 'Pending Leader']) }}" class="card stat-card rose p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md block relative overflow-hidden group {{ $rfqPendingLeader > 0 ? 'ring-2 ring-rose-400 bg-rose-50/20' : '' }}">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-bold uppercase tracking-wide text-rose-600 flex items-center gap-1">
                    @if($rfqPendingLeader > 0)
                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping inline-block mr-1"></span>
                    @endif
                    RFQ (Leader)
                </span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center {{ $rfqPendingLeader > 0 ? 'bg-rose-500 text-white' : 'bg-rose-100 text-rose-600' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1 text-rose-600">{{ number_format($rfqPendingLeader) }}</p>
            <div class="flex items-center justify-between text-[11px]">
                <span style="color: var(--text-muted);">Approval Margin</span>
                <span class="font-bold text-rose-600 group-hover:underline">Review →</span>
            </div>
        </a>

        <!-- Pending RFQ Admin -->
        <a href="{{ route('rfq.index', ['status' => 'Pending Admin']) }}" class="card stat-card teal p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md block group">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">RFQ (Admin)</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(13,148,136,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #0d9488;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($rfqPendingAdmin) }}</p>
            <div class="flex items-center justify-between text-[11px]">
                <span style="color: var(--text-muted);">Menunggu diisi harga</span>
                <span class="text-teal-600 font-semibold group-hover:underline">Lihat →</span>
            </div>
        </a>

        <!-- Pending Customers -->
        <a href="{{ route('customers.index', ['status' => 'Pending']) }}" class="card stat-card amber p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md block group">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Klien Pending</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($pendingApprovals) }}</p>
            <div class="flex items-center justify-between text-[11px]">
                <span style="color: var(--text-muted);">Menunggu verifikasi</span>
                <span class="text-amber-600 font-semibold group-hover:underline">Review →</span>
            </div>
        </a>

        <!-- Pending PO -->
        <a href="{{ route('po.index') }}" class="card stat-card violet p-4 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md block group">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">PO (Admin)</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124,58,237,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($poPending) }}</p>
            <div class="flex items-center justify-between text-[11px]">
                <span style="color: var(--text-muted);">PO diproses Admin</span>
                <span class="text-violet-600 font-semibold group-hover:underline">Buka →</span>
            </div>
        </a>
    </section>

    <!-- Quick Actions (Horizontal) -->
    <section class="mb-6 animate-in" style="animation-delay: 0.14s;">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <!-- Action 1: Approval RFQ Leader -->
            <a href="{{ route('rfq.index', ['status' => 'Pending Leader']) }}" class="action-btn flex-col text-center justify-center py-4 relative group">
                <svg class="w-6 h-6 mb-1 text-rose-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-xs font-semibold">Approval RFQ (Leader)</span>
                @if($rfqPendingLeader > 0)
                    <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[10px] font-bold rounded-full shadow-sm bg-rose-500 text-white animate-pulse">
                        {{ $rfqPendingLeader }}
                    </span>
                @endif
            </a>

            <!-- Action 2: Review Approval Klien -->
            <a href="{{ route('customers.index', ['status' => 'Pending']) }}" class="action-btn flex-col text-center justify-center py-4 relative group">
                <svg class="w-6 h-6 mb-1 text-amber-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                <span class="text-xs font-semibold">Approval Klien</span>
                @if($pendingApprovals > 0)
                    <span class="absolute top-2 right-2 px-1.5 py-0.5 text-[10px] font-bold rounded-full shadow-sm bg-amber-500 text-white">
                        {{ $pendingApprovals }}
                    </span>
                @endif
            </a>

            <!-- Action 3: Lihat Semua RFQ & Projek -->
            <a href="{{ route('rfq.index') }}" class="action-btn flex-col text-center justify-center py-4 group">
                <svg class="w-6 h-6 mb-1 text-emerald-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-xs font-semibold">Semua RFQ & Projek</span>
            </a>

            <!-- Action 4: Role-specific action -->
            @if($user->isSuperAdmin())
                <a href="{{ route('users.index') }}" class="action-btn flex-col text-center justify-center py-4 group">
                    <svg class="w-6 h-6 mb-1 text-violet-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="text-xs font-semibold">User Management</span>
                </a>
            @else
                <a href="#sales-performance" class="action-btn flex-col text-center justify-center py-4 group">
                    <svg class="w-6 h-6 mb-1 text-blue-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-xs font-semibold">Supervisi Tim Sales</span>
                </a>
            @endif
        </div>
    </section>

    <!-- Leader Action & Priority Center -->
    <div class="grid lg:grid-cols-12 gap-5 mb-6 animate-in" style="animation-delay: 0.16s;">
        <!-- Left: Leader Bottleneck & Action Queue (7 Cols) -->
        <section class="lg:col-span-7 card p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Pusat Kendali & Antrean Tindak Lanjut
                        </h2>
                        <p class="text-xs" style="color: var(--text-secondary);">Item yang membutuhkan perhatian dan keputusan segera</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ ($actionQueueSummary['total'] ?? 0) > 0 ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }}">
                        {{ number_format($actionQueueSummary['total'] ?? 0) }} Butuh Review
                    </span>
                </div>

                <div class="space-y-3">
                    <!-- Item RFQ Pending Leader -->
                    <div class="p-3.5 rounded-xl flex items-center justify-between transition-all hover:bg-slate-50 border {{ $rfqPendingLeader > 0 ? 'border-rose-200 bg-rose-50/40' : 'border-slate-200' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $rfqPendingLeader > 0 ? 'bg-rose-500 text-white' : 'bg-slate-100 text-slate-400' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold" style="color: var(--text-primary);">Approval Margin & Harga RFQ</h4>
                                <p class="text-xs" style="color: var(--text-secondary);">
                                    @if($rfqPendingLeader > 0)
                                        <span class="text-rose-600 font-semibold">{{ $rfqPendingLeader }} penawaran</span> menunggu persetujuan Leader sebelum diterbitkan.
                                    @else
                                        Semua RFQ sudah ditinjau, tidak ada antrean.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('rfq.index', ['status' => 'Pending Leader']) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $rfqPendingLeader > 0 ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $rfqPendingLeader > 0 ? 'Review (' . $rfqPendingLeader . ') →' : 'Buka List' }}
                        </a>
                    </div>

                    <!-- Item Customer Pending -->
                    <div class="p-3.5 rounded-xl flex items-center justify-between transition-all hover:bg-slate-50 border {{ $pendingApprovals > 0 ? 'border-amber-200 bg-amber-50/40' : 'border-slate-200' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $pendingApprovals > 0 ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-400' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold" style="color: var(--text-primary);">Approval Akun Klien Baru</h4>
                                <p class="text-xs" style="color: var(--text-secondary);">
                                    @if($pendingApprovals > 0)
                                        <span class="text-amber-600 font-semibold">{{ $pendingApprovals }} pelanggan</span> menunggu verifikasi data & legalitas.
                                    @else
                                        Tidak ada pelanggan baru yang pending.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('customers.index', ['status' => 'Pending']) }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all {{ $pendingApprovals > 0 ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $pendingApprovals > 0 ? 'Review (' . $pendingApprovals . ') →' : 'Buka List' }}
                        </a>
                    </div>

                    <!-- Item PO Pending -->
                    <div class="p-3.5 rounded-xl flex items-center justify-between transition-all hover:bg-slate-50 border {{ $poPending > 0 ? 'border-violet-200 bg-violet-50/40' : 'border-slate-200' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-lg flex items-center justify-center {{ $poPending > 0 ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-400' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold" style="color: var(--text-primary);">Purchase Order Masuk</h4>
                                <p class="text-xs" style="color: var(--text-secondary);">
                                    @if($poPending > 0)
                                        <span class="text-violet-600 font-semibold">{{ $poPending }} PO</span> sedang diproses verifikasi oleh tim Admin.
                                    @else
                                        Semua PO berjalan normal.
                                    @endif
                                </p>
                            </div>
                        </div>
                        <a href="{{ route('po.index') }}" class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all bg-slate-100 text-slate-600 hover:bg-slate-200">
                            Lihat PO →
                        </a>
                    </div>
                </div>
            </div>

            <!-- Mini Summary Footer -->
            <div class="grid grid-cols-3 gap-3 mt-4 pt-3 border-t border-slate-100 text-center">
                <div class="p-2 rounded-lg bg-slate-50">
                    <p class="text-[10px] text-slate-400 uppercase font-semibold">Klien Pending</p>
                    <p class="text-base font-bold text-slate-700">{{ number_format($actionQueueSummary['pending_customers'] ?? 0) }}</p>
                </div>
                <div class="p-2 rounded-lg bg-slate-50">
                    <p class="text-[10px] text-slate-400 uppercase font-semibold">PO Pending</p>
                    <p class="text-base font-bold text-slate-700">{{ number_format($actionQueueSummary['pending_pos'] ?? 0) }}</p>
                </div>
                <div class="p-2 rounded-lg bg-slate-50">
                    <p class="text-[10px] text-slate-400 uppercase font-semibold">RFQ Pending</p>
                    <p class="text-base font-bold text-slate-700">{{ number_format($actionQueueSummary['pending_rfqs'] ?? 0) }}</p>
                </div>
            </div>
        </section>

        <!-- Right: Rekomendasi Prioritas & Peluang Bernilai Tinggi (5 Cols) -->
        <section class="lg:col-span-5 card p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            Saran Prioritas Strategis
                        </h2>
                        <p class="text-xs" style="color: var(--text-secondary);">Rekomendasi sistem untuk kelancaran closing</p>
                    </div>
                </div>

                <div class="space-y-3">
                    @forelse($priorityRecommendations as $recommendation)
                        <div class="p-3 rounded-lg flex items-start gap-3 bg-slate-50 border border-slate-100">
                            <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ $recommendation['priority'] === 'high' ? 'bg-amber-500' : 'bg-blue-500' }}"></span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ $recommendation['title'] }}</p>
                                    <span class="text-[9px] font-semibold px-2 py-0.5 rounded-full {{ $recommendation['priority'] === 'high' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $recommendation['priority'] === 'high' ? 'Urgensi Tinggi' : 'Menengah' }}
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-500 mt-1 leading-snug">{{ $recommendation['description'] }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-slate-400">Momentum penjualan terjaga dengan baik.</div>
                    @endforelse
                </div>
            </div>

            <!-- Peluang Nilai Tertinggi Compact Block -->
            <div class="mt-4 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Peluang Nilai Tertinggi</span>
                </div>
                @forelse($highValueOpportunities as $opportunity)
                    <div class="p-2.5 rounded-lg bg-emerald-50/50 border border-emerald-100 flex items-center justify-between mb-2">
                        <div class="truncate mr-2">
                            <p class="text-xs font-bold text-slate-800 truncate">{{ $opportunity['company_name'] }}</p>
                            <p class="text-[10px] text-slate-500">{{ $opportunity['label'] }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-bold text-emerald-600">Rp {{ number_format($opportunity['value'], 0, ',', '.') }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-2 rounded-lg bg-slate-50 text-[11px] text-slate-500 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Belum ada PO bernilai besar yang pending review saat ini.</span>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <!-- Today's Activity (Admin) -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-in mb-6" style="animation-delay: 0.17s;">
        <div class="card p-4">
            <p class="text-[10px] uppercase tracking-wide font-semibold mb-1" style="color: var(--text-muted);">Customer Baru Hari Ini</p>
            <p class="text-xl font-bold" style="color: var(--text-primary);">{{ number_format($todayNewCustomers) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] uppercase tracking-wide font-semibold mb-1" style="color: var(--text-muted);">RFQ Baru Hari Ini</p>
            <p class="text-xl font-bold" style="color: var(--text-primary);">{{ number_format($todayNewRFQ) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] uppercase tracking-wide font-semibold mb-1" style="color: var(--text-muted);">GOAL Bulan Ini</p>
            <p class="text-xl font-bold" style="color: var(--accent-emerald);">{{ number_format($goalCountThisMonth) }}</p>
        </div>
        <div class="card p-4">
            <p class="text-[10px] uppercase tracking-wide font-semibold mb-1" style="color: var(--text-muted);">Conversion Rate</p>
            <p class="text-xl font-bold" style="color: var(--accent-blue);">{{ $conversionRate }}%</p>
        </div>
    </section>

    <!-- Quotation Stats Row -->
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 animate-in mb-6" style="animation-delay: 0.18s;">
        <div class="card stat-card emerald p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Total Quotation</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(5,150,105,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #0d9488;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalQuo) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">All Time</div>
        </div>
        <div class="card stat-card blue p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Terkirim</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(37,99,235,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($sentQuo) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">Total sent</div>
        </div>
        <div class="card stat-card violet p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Bulan Ini</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124,58,237,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($sentQuoThisMonth) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">Sent this month</div>
        </div>
        <div class="card stat-card amber p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Draft</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalQuo - $sentQuo) }}</p>
            <div class="flex items-center gap-1 text-[11px]" style="color: var(--text-muted);">Menunggu dikirim</div>
        </div>
    </section>

    <!-- Main Grid: KPI + Revenue Chart -->
    <div class="grid lg:grid-cols-3 gap-5 mb-6">
        <!-- Sales Marketing Performance KPIs -->
        <section class="lg:col-span-1 card p-5 animate-in scroll-mt-6" id="sales-performance" style="animation-delay: 0.3s;">
            <h2 class="text-base font-bold mb-5 flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Sales Marketing Performance
            </h2>

            <div class="space-y-5">
                <!-- Leaderboard Sales Marketing -->
                @forelse($salesPerformance as $s)
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <span class="text-xs font-medium" style="color: var(--text-primary);">{{ $s['name'] }}</span>
                        <span class="text-xs font-semibold @if($s['percentage'] >= 100) text-emerald-500 @elseif($s['percentage'] >= 50) text-amber-500 @else text-rose-500 @endif">{{ $s['percentage'] }}%</span>
                    </div>
                    <div class="progress-bar h-1.5">
                        <div class="progress-fill @if($s['percentage'] >= 100) bg-gradient-to-r from-emerald-500 to-teal-500 @elseif($s['percentage'] >= 50) bg-gradient-to-r from-amber-500 to-orange-500 @else bg-gradient-to-r from-rose-500 to-pink-500 @endif" data-width="{{ min($s['percentage'], 100) }}"></div>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-[10px]" style="color: var(--text-muted);">Rp {{ number_format($s['achieved'], 0, ',', '.') }}</span>
                        <span class="text-[10px]" style="color: var(--text-muted);">/ Rp {{ $s['target'] > 0 ? number_format($s['target'], 0, ',', '.') : 'N/A' }}</span>
                    </div>
                </div>
                @empty
                <div class="py-6 text-center text-xs" style="color: var(--text-muted);">Belum ada data performa Sales Marketing.</div>
                @endforelse

                @if($pendingApprovals > 0)
                <!-- Pending Approval Alert -->
                <div class="flex items-center justify-between p-3.5 rounded-xl mt-4" style="background: rgba(217,119,6,0.08); border: 1px solid rgba(217,119,6,0.20);">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.12);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-[10px]" style="color: var(--text-muted);">Pending Customers</p>
                            <p class="text-sm font-bold" style="color: var(--text-primary);">{{ $pendingApprovals }} butuh approval</p>
                        </div>
                    </div>
                    <a href="{{ route('customers.index', ['status' => 'Pending']) }}" class="text-[10px] font-bold" style="color: var(--accent-amber);">Review →</a>
                </div>
                @endif
            </div>
        </section>

        <!-- Revenue Chart -->
        <section class="lg:col-span-2 card p-5 animate-in" style="animation-delay: 0.4s;">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
                <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                    Revenue Overview
                </h2>
            </div>

            <div class="chart-container" style="min-height: 250px;">
                <canvas id="revenueChart"></canvas>
            </div>

            <div class="flex items-center justify-center gap-6 mt-4 pt-4" style="border-top: 1px solid var(--border-color);">
                <div class="flex items-center gap-2">
                    <div class="w-2.5 h-2.5 rounded-full" style="background: var(--accent-blue);"></div>
                    <span class="text-[10px]" style="color: var(--text-secondary);">Revenue</span>
                </div>
            </div>
        </section>
    </div>

    <!-- RFQ Pipeline (Admin) -->
    <section class="card p-5 animate-in mb-6" style="animation-delay: 0.48s;">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Pipeline RFQ (All Time)
            </h2>
            <a href="{{ route('rfq.index') }}" class="text-xs font-medium" style="color: var(--accent-blue);">View All</a>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-6 gap-3">
            <div class="p-3 rounded-xl text-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                <p class="text-lg font-bold" style="color: var(--accent-amber);">{{ number_format($rfqPipeline->pending_admin ?? 0) }}</p>
                <p class="text-[10px]" style="color: var(--text-muted);">Pending Admin</p>
            </div>
            <div class="p-3 rounded-xl text-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                <p class="text-lg font-bold" style="color: var(--accent-rose);">{{ number_format($rfqPipeline->pending_leader ?? 0) }}</p>
                <p class="text-[10px]" style="color: var(--text-muted);">Pending Leader</p>
            </div>
            <div class="p-3 rounded-xl text-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                <p class="text-lg font-bold" style="color: var(--accent-blue);">{{ number_format($rfqPipeline->approved ?? 0) }}</p>
                <p class="text-[10px]" style="color: var(--text-muted);">Approved</p>
            </div>
            <div class="p-3 rounded-xl text-center" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                <p class="text-lg font-bold" style="color: var(--accent-violet);">{{ number_format($rfqPipeline->quotation_created ?? 0) }}</p>
                <p class="text-[10px]" style="color: var(--text-muted);">Quotation</p>
            </div>
            <div class="p-3 rounded-xl text-center" style="background: rgba(5,150,105,0.08); border: 1px solid rgba(5,150,105,0.20);">
                <p class="text-lg font-bold" style="color: var(--accent-emerald);">{{ number_format($rfqPipeline->goal ?? 0) }}</p>
                <p class="text-[10px]" style="color: var(--text-muted);">GOAL</p>
            </div>
            <div class="p-3 rounded-xl text-center" style="background: rgba(225,29,72,0.06); border: 1px solid rgba(225,29,72,0.15);">
                <p class="text-lg font-bold" style="color: var(--accent-rose);">{{ number_format($rfqPipeline->cancelled ?? 0) }}</p>
                <p class="text-[10px]" style="color: var(--text-muted);">Cancelled</p>
            </div>
        </div>
    </section>

    <!-- Recent Quotations (Admin) -->
    <section class="card p-5 animate-in mt-5" style="animation-delay: 0.55s;">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-teal, #0d9488);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Recent Quotations
            </h2>
            <a href="{{ route('quo.index') }}" class="text-xs font-medium" style="color: var(--accent-blue);">View All</a>
        </div>
        <div class="overflow-x-auto -mx-5 px-5">
            <table class="data-table text-sm">
                <thead>
                    <tr>
                        <th class="py-2">No. Quotation</th>
                        <th class="py-2">Customer</th>
                        <th class="py-2">Sales Marketing</th>
                        <th class="py-2">Revisi</th>
                        <th class="py-2">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentQuotations as $quo)
                    <tr>
                        <td class="py-2 font-medium" style="color: var(--text-primary);">
                            <a href="{{ route('quo.show', $quo) }}" style="color: var(--accent-blue);">{{ $quo->quo_number }}</a>
                        </td>
                        <td class="py-2" style="color: var(--text-secondary);">{{ $quo->customer?->company_name ?? '-' }}</td>
                        <td class="py-2" style="color: var(--text-secondary);">{{ $quo->sales?->name ?? '-' }}</td>
                        <td class="py-2" style="color: var(--text-secondary);">{{ $quo->revision_number > 0 ? 'Rev #' . $quo->revision_number : '-' }}</td>
                        <td class="py-2">
                            @php
                                $badgeClass = $quo->isSent() ? 'approved' : 'pending';
                            @endphp
                            <span class="status-badge {{ $badgeClass }} text-[10px]">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                {{ $quo->status }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-6" style="color: var(--text-muted);">Belum ada Quotation</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

@endif

{{-- ── Chart.js Script ── --}}
@if($isDashboardSales)
<script type="application/json" id="monthlyChartData">
    {!! json_encode($monthlyChart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;
        
        const rawData = JSON.parse(document.getElementById('monthlyChartData').textContent);
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        const labels = rawData.map(d => months[d.month - 1] + ' ' + d.year);
        const valuesGoal = rawData.map(d => parseFloat(d.total_goal));
        const valuesPenawaran = rawData.map(d => parseFloat(d.total_penawaran));

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Penawaran (Rp)',
                        data: valuesPenawaran,
                        backgroundColor: 'rgba(156, 163, 175, 0.4)', // Gray for Penawaran
                        borderColor: 'rgba(156, 163, 175, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 40,
                    },
                    {
                        label: 'GOAL (Rp)',
                        data: valuesGoal,
                        backgroundColor: 'rgba(37, 99, 235, 0.7)', // Blue for GOAL
                        borderColor: 'rgba(37, 99, 235, 1)',
                        borderWidth: 2,
                        borderRadius: 4,
                        maxBarThickness: 40,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000000) return 'Rp ' + (value / 1000000000).toFixed(1) + 'M';
                                if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'Jt';
                                return 'Rp ' + value;
                            }
                        },
                        grid: { borderDash: [4, 4] }
                    },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    label += 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@else
<script type="application/json" id="monthlyRevenueData">
    {!! json_encode($monthlyRevenue, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
<script>
    const rawData = JSON.parse(document.getElementById('monthlyRevenueData').textContent);
    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
    const labels = rawData.map(d => months[d.month - 1] + ' ' + d.year);
    const values = rawData.map(d => parseFloat(d.total));

    const ctx = document.getElementById('revenueChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Revenue (Rp)',
                    data: values,
                    backgroundColor: 'rgba(37,99,235,0.6)',
                    borderColor: 'rgba(37,99,235,1)',
                    borderWidth: 2,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 50,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => 'Rp ' + new Intl.NumberFormat('id-ID').format(ctx.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: val => {
                                if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(1) + 'M';
                                if (val >= 1000) return 'Rp ' + (val / 1000).toFixed(0) + 'K';
                                return 'Rp ' + val;
                            },
                            font: { size: 10 },
                            color: '#94a3b8',
                        },
                        grid: { color: 'rgba(15,23,42,0.04)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#94a3b8', font: { size: 10 } }
                    }
                }
            }
        });
    }
</script>
@endif

</x-app-layout>
