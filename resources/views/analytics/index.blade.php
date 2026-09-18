<x-app-layout>

    <div class="mb-6 animate-in" style="animation-delay: 0.05s;">
        <h1 class="text-xl font-bold" style="color: var(--text-primary);">Analytics & Laporan</h1>
        <p class="text-sm mt-0.5" style="color: var(--text-muted);">Ringkasan performa bisnis dan pencapaian tim Sales Marketing</p>
    </div>

    {{-- ── ROW 1: Stat Cards ── --}}
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 animate-in" style="animation-delay: 0.1s;">
        {{-- Total Revenue --}}
        <div class="card stat-card emerald p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Total Revenue</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(5,150,105,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            <div class="text-[11px]" style="color: var(--text-muted);">All Time (Completed)</div>
        </div>

        {{-- Revenue Bulan Ini --}}
        <div class="card stat-card blue p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Bulan Ini</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(37,99,235,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">Rp {{ number_format($revenueThisMonth, 0, ',', '.') }}</p>
            <div class="flex items-center gap-1 text-[11px] {{ $revenueGrowth >= 0 ? 'growth-positive' : 'growth-negative' }}">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if($revenueGrowth >= 0)
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    @endif
                </svg>
                {{ abs($revenueGrowth) }}% vs bulan lalu
            </div>
        </div>

        {{-- Total PO Selesai --}}
        <div class="card stat-card violet p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">PO Completed</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124,58,237,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalCompletedPO) }}</p>
            <div class="text-[11px]" style="color: var(--text-muted);">All Time</div>
        </div>

        {{-- AOV --}}
        <div class="card stat-card amber p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Avg. Order Value</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">Rp {{ number_format($aov, 0, ',', '.') }}</p>
            <div class="text-[11px]" style="color: var(--text-muted);">Per PO Completed</div>
        </div>
    </section>

    {{-- ── ROW 1b: Customer + Monthly Revenue Metrics ── --}}
    <section class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 animate-in" style="animation-delay: 0.13s;">
        <div class="card stat-card blue p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Total Customers</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(37,99,235,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h5m-5 4h10M5 6h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($totalCustomers) }}</p>
            <div class="text-[11px]" style="color: var(--text-muted);">Semua pelanggan terdaftar</div>
        </div>
        <div class="card stat-card emerald p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Active Customers</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(5,150,105,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-emerald);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ number_format($activeCustomers) }}</p>
            <div class="text-[11px]" style="color: var(--text-muted);">Status Active</div>
        </div>
        <div class="card stat-card amber p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Avg. Monthly Revenue</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm6 0v-4a2 2 0 00-2-2h-2a2 2 0 00-2 2v4m6 0v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">Rp {{ number_format($avgMonthlyRevenue, 0, ',', '.') }}</p>
            <div class="text-[11px]" style="color: var(--text-muted);">Rata-rata 12 bulan</div>
        </div>
        <div class="card stat-card violet p-4">
            <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Peak Month</span>
                <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background: rgba(124,58,237,0.10);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v8m0 0l-3-3m3 3l3-3M4 6h16"/>
                    </svg>
                </div>
            </div>
            <p class="text-xl font-bold mb-1" style="color: var(--text-primary);">{{ $peakMonthLabel }}</p>
            <div class="text-[11px]" style="color: var(--text-muted);">Bulan dengan revenue tertinggi</div>
        </div>
    </section>

    <section class="card p-4 mb-6 animate-in" style="animation-delay: 0.14s;">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide" style="color: var(--text-muted);">Menunggu Review</p>
                <p class="text-2xl font-bold mt-1" style="color: var(--text-primary);">{{ number_format($pendingReviewCount) }}</p>
                <p class="text-xs mt-1" style="color: var(--text-secondary);">Prospek pelanggan dan PO yang masih butuh peninjauan admin</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: rgba(217,119,6,0.10);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </section>

    {{-- ── ROW 2: Revenue Chart + Sales Marketing Performance ── --}}
    <div class="grid lg:grid-cols-3 gap-5 mb-6">

        {{-- Revenue Chart 12 Bulan --}}
        <section class="lg:col-span-2 card p-5 animate-in" style="animation-delay: 0.15s;">
            <h2 class="text-base font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                </svg>
                Revenue 12 Bulan Terakhir
            </h2>
            <div style="min-height: 260px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </section>

        {{-- Sales Performance Bulan Ini --}}
        <section class="card p-5 animate-in" style="animation-delay: 0.2s;">
            <h2 class="text-base font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                Performa Sales Marketing Bulan Ini
            </h2>
            <div class="space-y-4">
                @forelse($salesPerformance as $s)
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium" style="color: var(--text-primary);">{{ $s['name'] }}</span>
                        <span class="text-xs font-bold @if($s['percentage'] >= 100) text-emerald-500 @elseif($s['percentage'] >= 50) text-amber-500 @else text-rose-500 @endif">{{ $s['percentage'] }}%</span>
                    </div>
                    <div class="progress-bar h-1.5">
                        <div class="progress-fill @if($s['percentage'] >= 100) bg-gradient-to-r from-emerald-500 to-teal-500 @elseif($s['percentage'] >= 50) bg-gradient-to-r from-amber-500 to-orange-500 @else bg-gradient-to-r from-rose-500 to-pink-500 @endif" data-width="{{ min($s['percentage'], 100) }}"></div>
                    </div>
                    <div class="flex justify-between mt-0.5">
                        <span class="text-[10px]" style="color: var(--text-muted);">Rp {{ number_format($s['achieved'], 0, ',', '.') }}</span>
                        <span class="text-[10px]" style="color: var(--text-muted);">Target: Rp {{ $s['target'] > 0 ? number_format($s['target'], 0, ',', '.') : 'N/A' }}</span>
                    </div>
                </div>
                @empty
                <div class="py-4 text-center text-xs" style="color: var(--text-muted);">Tidak ada data Sales Marketing aktif.</div>
                @endforelse
            </div>
        </section>
    </div>

    {{-- ── ROW 3: Top Sales Marketing + Top Customers ── --}}
    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Top 5 Sales Marketing All-Time --}}
        <section class="card p-5 animate-in" style="animation-delay: 0.25s;">
            <h2 class="text-base font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-violet);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Top 5 Sales Marketing (All Time)
            </h2>
            <div class="overflow-x-auto -mx-5 px-5">
                <table class="data-table text-sm w-full">
                    <thead>
                        <tr>
                            <th class="py-2">#</th>
                            <th class="py-2">Nama</th>
                            <th class="py-2">Total PO</th>
                            <th class="py-2">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topSales as $i => $s)
                        <tr>
                            <td class="py-2 text-center">
                                <span class="text-xs font-bold" style="color: var(--text-muted);">{{ $i + 1 }}</span>
                            </td>
                            <td class="py-2 font-medium" style="color: var(--text-primary);">{{ $s['name'] }}</td>
                            <td class="py-2 text-center" style="color: var(--text-secondary);">{{ $s['po_count'] }}</td>
                            <td class="py-2 font-semibold" style="color: var(--accent-emerald);">Rp {{ number_format($s['achieved'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-6" style="color: var(--text-muted);">Belum ada data.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        {{-- Top 5 Customers --}}
        <section class="card p-5 animate-in" style="animation-delay: 0.3s;">
            <h2 class="text-base font-bold mb-4 flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-cyan);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Top 5 Klien (Nilai Tertinggi)
            </h2>
            <div class="overflow-x-auto -mx-5 px-5">
                <table class="data-table text-sm w-full">
                    <thead>
                        <tr>
                            <th class="py-2">#</th>
                            <th class="py-2">Perusahaan</th>
                            <th class="py-2">Total PO</th>
                            <th class="py-2">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCustomers as $i => $c)
                        <tr>
                            <td class="py-2 text-center">
                                <span class="text-xs font-bold" style="color: var(--text-muted);">{{ $i + 1 }}</span>
                            </td>
                            <td class="py-2 font-medium" style="color: var(--text-primary);">{{ $c['name'] }}</td>
                            <td class="py-2 text-center" style="color: var(--text-secondary);">{{ $c['po_count'] }}</td>
                            <td class="py-2 font-semibold" style="color: var(--accent-blue);">Rp {{ number_format($c['total'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-6" style="color: var(--text-muted);">Belum ada data.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- Chart.js Script --}}
    <script type="application/json" id="monthlyRevenueData">
        {!! json_encode($monthlyRevenue, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
    </script>
    <script>
        const rawData = JSON.parse(document.getElementById('monthlyRevenueData').textContent);
        const monthNames = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agt','Sep','Okt','Nov','Des'];
        const labels = rawData.map(d => monthNames[d.month - 1] + ' ' + String(d.year).slice(2));
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
                        backgroundColor: 'rgba(37,99,235,0.12)',
                        borderColor: 'rgba(37,99,235,0.8)',
                        borderWidth: 2,
                        borderRadius: 6,
                        borderSkipped: false,
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
                                    if (val >= 1000000000) return 'Rp ' + (val / 1000000000).toFixed(1) + 'B';
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

</x-app-layout>
