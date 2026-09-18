<x-app-layout>
    @php
        /*
         * ALGORITMA KONTROL AKSES (Phase 1)
         * ─────────────────────────────────
         * isOwner  = Sales yang login adalah pemilik customer ini
         * canEdit  = Admin/Super Admin bisa edit semua;
         *            Sales hanya bisa edit miliknya sendiri
         * viewOnly = Sales yang melihat customer milik orang lain
         *            → hanya bisa lihat, tidak ada tombol Edit
         */
        $isOwner  = $customer->sales_id === auth()->id();
        $canEdit  = auth()->user()->isAdminOrAbove() || (auth()->user()->isSales() && $isOwner);
        $viewOnly = auth()->user()->isSales() && !$isOwner;
    @endphp

    <!-- Page Header -->
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('customers.index') }}" class="p-1.5 rounded-lg transition-colors" style="color: var(--text-muted);" onmouseenter="this.style.background='var(--bg-secondary)'; this.style.color='var(--text-primary)'" onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-xl font-bold" style="color: var(--text-primary);">
                    Profil Customer
                </h1>
            </div>
            <p class="mt-1 text-sm ml-10" style="color: var(--text-muted);">Detail informasi dan riwayat transaksi</p>
        </div>

        {{-- Tombol Edit hanya tampil untuk Admin ATAU Sales pemilik customer ini --}}
        @if($canEdit)
        <div class="flex items-center gap-2">
            <a href="{{ route('customers.edit', $customer) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md"
               style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Customer
            </a>
        </div>
        @endif
    </div>

    {{--
        BANNER VIEW-ONLY
        Ditampilkan HANYA jika Sales yang login bukan pemilik customer ini.
        Tujuan: memberi tahu Sales secara sopan kenapa tidak ada tombol Edit,
        daripada membiarkan mereka bingung mencari tombol yang tidak ada.
    --}}
    @if($viewOnly)
    <div class="mb-5 p-4 rounded-2xl flex items-start gap-3 animate-in" style="animation-delay: 0.08s; background: rgba(217,119,6,0.06); border: 1px solid rgba(217,119,6,0.20);">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-amber);">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
        </svg>
        <div>
            <p class="text-sm font-semibold" style="color: var(--accent-amber);">Mode Lihat Saja</p>
            <p class="text-xs mt-0.5" style="color: var(--text-secondary);">
                Customer ini dipegang oleh <span class="font-semibold">{{ $customer->sales->name ?? 'Sales lain' }}</span>.
                Anda dapat melihat profil ini sebagai referensi, namun tidak dapat melakukan perubahan atau membuat transaksi untuk customer ini.
            </p>
        </div>
    </div>
    @endif

    <!-- Overview Card (Kartu Nama) -->
    <div class="card p-6 mb-6 animate-in" style="animation-delay: 0.1s;">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Info Utama -->
            <div class="md:col-span-2 flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <h2 class="text-2xl font-bold" style="color: var(--text-primary);">{{ $customer->company_name }}</h2>
                        @php
                            $badgeClass = match($customer->status) {
                                'Active' => 'status-active',
                                'Prospect' => 'status-pending',
                                'Inactive' => 'status-inactive',
                                'Blacklist' => 'status-rejected',
                                default => 'status-inactive'
                            };
                        @endphp
                        <span class="status-badge {{ $badgeClass }} text-xs">
                            <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                            {{ $customer->status }}
                        </span>
                    </div>
                    <code class="text-sm px-2 py-1 rounded mb-4 inline-block" style="background: var(--bg-secondary); color: var(--text-secondary);">
                        {{ $customer->company_code ?? '-' }}
                    </code>
                    
                    <div class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm mt-2">
                        <div>
                            <p style="color: var(--text-muted);" class="mb-0.5">Bidang Usaha</p>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $customer->brand_name ?? '-' }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-muted);" class="mb-0.5">Tipe Customer</p>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $customer->customer_type ?? '-' }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-muted);" class="mb-0.5">Industry Sector</p>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $customer->industry ?? '-' }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-muted);" class="mb-0.5">NPWP</p>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $customer->npwp ?? '-' }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-muted);" class="mb-0.5">NIB</p>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $customer->nib ?? '-' }}</p>
                        </div>
                        <div>
                            <p style="color: var(--text-muted);" class="mb-0.5">Kawasan</p>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $customer->region ?? '-' }}</p>
                        </div>

                        {{--
                            Alamat lengkap: tampilkan hierarki wilayah secara
                            informatif dari yang paling spesifik ke umum.
                            Jika district/village belum ada (data lama sebelum
                            Phase 1), tampilkan gracefully dengan '-'.
                        --}}
                        <div class="col-span-2">
                            <p style="color: var(--text-muted);" class="mb-1">Alamat Lengkap</p>
                            <div class="font-medium leading-relaxed" style="color: var(--text-primary);">
                                @if($customer->address)
                                    <p>{{ $customer->address }}</p>
                                @endif
                                <p class="text-sm mt-1" style="color: var(--text-secondary);">
                                    @php
                                        // Bangun baris alamat dari komponen yang tersedia
                                        $parts = array_filter([
                                            $customer->village   ? 'Kel. '  . $customer->village   : null,
                                            $customer->district  ? 'Kec. '  . $customer->district  : null,
                                            $customer->city      ?? null,
                                            $customer->province  ?? null,
                                            $customer->postal_code ? $customer->postal_code         : null,
                                        ]);
                                    @endphp
                                    {{ implode(', ', $parts) ?: '-' }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Daftar Kontak Person (1:N) -->
            <div class="p-5 rounded-2xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Daftar Kontak Person</h3>
                    @if($canEdit)
                    <a href="{{ route('customers.edit', $customer) }}#contacts-container" class="text-xs font-semibold hover:underline" style="color: var(--accent-blue);">
                        + Tambah / Kelola Kontak
                    </a>
                    @endif
                </div>
                
                @forelse($customer->contacts as $contact)
                    <div class="mb-4 {{ !$loop->last ? 'pb-4 border-b border-gray-200 dark:border-gray-700' : '' }}">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs" style="background: rgba(37,99,235,0.1); color: var(--accent-blue);">
                                    {{ substr($contact->name ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-sm" style="color: var(--text-primary);">{{ $contact->name ?? '-' }}</p>
                                    <p class="text-xs" style="color: var(--text-muted);">{{ $contact->position ?? '-' }}</p>
                                </div>
                            </div>
                            @if($contact->is_primary)
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full" style="background: rgba(16,185,129,0.1); color: var(--accent-emerald);">Kontak Utama</span>
                            @endif
                        </div>
                        <div class="space-y-1.5 text-xs mt-3 ml-11">
                            @if($contact->email)
                            <div class="flex items-center gap-2" style="color: var(--text-secondary);">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                <span class="truncate">{{ $contact->email }}</span>
                            </div>
                            @endif
                            @if($contact->phone)
                            <div class="flex items-center gap-2" style="color: var(--text-secondary);">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                <span>{{ $contact->phone }}</span>
                            </div>
                            @endif
                            @if($contact->division)
                            <div class="flex items-center gap-2" style="color: var(--text-secondary);">
                                <span class="font-medium" style="color: var(--text-muted);">Divisi:</span>
                                <span>{{ $contact->division }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-center py-2" style="color: var(--text-muted);">Belum ada kontak terdaftar.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Billing & Shipping Addresses -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 animate-in" style="animation-delay: 0.12s;">

        {{-- Billing Addresses --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" style="color: var(--text-secondary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Alamat Penagihan
            </h3>
            @forelse($customer->billingAddresses as $ba)
            <div class="p-3 mb-2 rounded-xl text-sm" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                @if($ba->label)<p class="text-xs font-semibold mb-1" style="color: var(--accent-blue);">{{ $ba->label }}</p>@endif
                <p style="color: var(--text-primary);">{{ $ba->address ?? '-' }}</p>
                <p style="color: var(--text-muted);">{{ $ba->city ?? '-' }}{{ $ba->province ? ', ' . $ba->province : '' }}{{ $ba->postal_code ? ', ' . $ba->postal_code : '' }}</p>
            </div>
            @empty
            <p class="text-xs" style="color: var(--text-muted);">Belum ada alamat penagihan.</p>
            @endforelse
        </div>

        {{-- Shipping Addresses --}}
        <div class="card p-5">
            <h3 class="text-sm font-semibold mb-3 flex items-center gap-2" style="color: var(--text-secondary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10l2-1m4-1l-2 1m2-1l-2 1m7-1V6a1 1 0 011-1h5a1 1 0 011 1v10l-2-1m-4 0l2-1m-4 1l2 1"/></svg>
                Alamat Pengiriman
            </h3>
            @forelse($customer->shippingAddresses as $sa)
            <div class="p-3 mb-2 rounded-xl text-sm" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                @if($sa->label)<p class="text-xs font-semibold mb-1" style="color: var(--accent-blue);">{{ $sa->label }}</p>@endif
                <p style="color: var(--text-primary);">{{ $sa->address ?? '-' }}</p>
                <p style="color: var(--text-muted);">{{ $sa->city ?? '-' }}{{ $sa->province ? ', ' . $sa->province : '' }}{{ $sa->postal_code ? ', ' . $sa->postal_code : '' }}</p>
            </div>
            @empty
            <p class="text-xs" style="color: var(--text-muted);">Belum ada alamat pengiriman.</p>
            @endforelse
        </div>

    </div>

    <!-- Tabs Section (Alpine.js) -->
    <div x-data="{ tab: 'active' }" class="animate-in" style="animation-delay: 0.15s;">
        <!-- Tab Headers -->
        <div class="flex items-center gap-6 border-b mb-6" style="border-color: var(--border-color);">
            <button @click="tab = 'active'"
                :class="{'border-b-2 font-semibold': tab === 'active', 'text-muted hover:text-primary': tab !== 'active'}"
                class="pb-3 text-sm transition-colors"
                style="border-color: var(--accent-blue); color: var(--text-primary);">
                Transaksi Berjalan 
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs" style="background: rgba(37,99,235,0.1); color: var(--accent-blue);">{{ $activePOs->count() + $activeRFQs->count() }}</span>
            </button>
            <button @click="tab = 'history'"
                :class="{'border-b-2 font-semibold': tab === 'history', 'text-muted hover:text-primary': tab !== 'history'}"
                class="pb-3 text-sm transition-colors"
                style="border-color: var(--accent-blue); color: var(--text-primary);">
                Riwayat Transaksi
                <span class="ml-1.5 px-2 py-0.5 rounded-full text-xs" style="background: var(--bg-secondary); color: var(--text-muted);">{{ $historyPOs->count() + $historyRFQs->count() }}</span>
            </button>
        </div>

        <!-- Tab 1: Active Transactions -->
        <div x-show="tab === 'active'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            
            <!-- Active POs -->
            <div class="card mb-6">
                <div class="p-4 border-b flex items-center justify-between" style="border-color: var(--border-color);">
                    <h3 class="font-semibold" style="color: var(--text-primary);">Purchase Orders (Berjalan)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>No. PO</th>
                                <th>Tanggal</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activePOs as $po)
                            <tr>
                                <td>
                                    <a href="{{ route('po.show', $po) }}" class="font-medium transition-colors" style="color: var(--accent-blue); hover:color: #1d4ed8;">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td>{{ $po->po_date->format('d/m/Y') }}</td>
                                <td>{{ $po->due_date ? $po->due_date->format('d/m/Y') : '-' }}</td>
                                <td class="font-medium">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                                <td>
                                    <span class="status-badge status-pending text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                        {{ $po->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-sm" style="color: var(--text-muted);">Tidak ada PO yang sedang berjalan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Active RFQs -->
            <div class="card">
                <div class="p-4 border-b flex items-center justify-between" style="border-color: var(--border-color);">
                    <h3 class="font-semibold" style="color: var(--text-primary);">RFQ (Berjalan)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>No. RFQ</th>
                                <th>Tanggal</th>
                                <th>Sales Marketing</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeRFQs as $rfq)
                            <tr>
                                <td class="font-medium" style="color: var(--text-primary);">{{ $rfq->rfq_number }}</td>
                                <td>{{ $rfq->rfq_date->format('d/m/Y') }}</td>
                                <td>{{ $rfq->sales->name ?? '-' }}</td>
                                <td>
                                    <span class="status-badge status-pending text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                        {{ $rfq->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-sm" style="color: var(--text-muted);">Tidak ada RFQ yang sedang berjalan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>

        <!-- Tab 2: History Transactions -->
        <div x-show="tab === 'history'" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
            
            <!-- History POs -->
            <div class="card mb-6">
                <div class="p-4 border-b flex items-center justify-between" style="border-color: var(--border-color);">
                    <h3 class="font-semibold" style="color: var(--text-primary);">Purchase Orders (Riwayat)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>No. PO</th>
                                <th>Tanggal</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historyPOs as $po)
                            <tr>
                                <td>
                                    <a href="{{ route('po.show', $po) }}" class="font-medium transition-colors" style="color: var(--accent-blue); hover:color: #1d4ed8;">
                                        {{ $po->po_number }}
                                    </a>
                                </td>
                                <td>{{ $po->po_date->format('d/m/Y') }}</td>
                                <td class="font-medium">Rp {{ number_format($po->grand_total, 0, ',', '.') }}</td>
                                <td>
                                    @php
                                        $poBadge = $po->status === 'Goal' ? 'status-active' : 'status-rejected';
                                    @endphp
                                    <span class="status-badge {{ $poBadge }} text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                        {{ $po->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-sm" style="color: var(--text-muted);">Tidak ada riwayat PO.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- History RFQs -->
            <div class="card">
                <div class="p-4 border-b flex items-center justify-between" style="border-color: var(--border-color);">
                    <h3 class="font-semibold" style="color: var(--text-primary);">RFQ (Riwayat)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="data-table w-full">
                        <thead>
                            <tr>
                                <th>No. RFQ</th>
                                <th>Tanggal</th>
                                <th>Sales Marketing</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($historyRFQs as $rfq)
                            <tr>
                                <td class="font-medium" style="color: var(--text-primary);">{{ $rfq->rfq_number }}</td>
                                <td>{{ $rfq->rfq_date->format('d/m/Y') }}</td>
                                <td>{{ $rfq->sales->name ?? '-' }}</td>
                                <td>
                                    @php
                                        $rfqBadge = $rfq->status === 'GOAL' ? 'status-active' : 'status-rejected';
                                    @endphp
                                    <span class="status-badge {{ $rfqBadge }} text-xs">
                                        <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                        {{ $rfq->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-sm" style="color: var(--text-muted);">Tidak ada riwayat RFQ.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
