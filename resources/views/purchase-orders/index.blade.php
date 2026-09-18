<x-app-layout>

    <!-- Page Header -->
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-cyan);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Purchase Orders
                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full" style="background: var(--bg-secondary); color: var(--text-secondary);">{{ $purchaseOrders->total() }}</span>
            </h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola semua Purchase Order dari pelanggan</p>
        </div>
        @if(auth()->user()->hasPermission('CRUD'))
        <a href="{{ route('po.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md"
           style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Buat PO Baru
        </a>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card filter-bar p-4 mb-5">
        <form method="GET" action="{{ route('po.index') }}" class="flex flex-wrap gap-3 items-end">
            <div class="relative flex-1 min-w-[200px]">
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Cari</label>
                <svg class="absolute left-3 top-[38px] -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="No. PO atau nama perusahaan..."
                    class="w-full pl-9 pr-4 py-2.5 text-sm rounded-xl outline-none transition-all"
                    style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-primary);"
                    onmouseenter="this.style.borderColor='rgba(37,99,235,0.3)'"
                    onmouseleave="if(!this.matches(':focus'))this.style.borderColor='var(--border-color)'"
                    onfocus="this.style.borderColor='var(--accent-blue)'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.10)'"
                    onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'">
            </div>
            <div>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Status</label>
                <x-custom-select name="status" :value="request('status')" placeholder="Semua Status" :options="[
                    '' => 'Semua Status',
                    'Pending' => 'Pending',
                    'Goal' => 'Goal',
                    'Revisi' => 'Revisi',
                    'Tidak Goal' => 'Tidak Goal',
                ]" />
            </div>
            <div class="relative">
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Dari Tanggal</label>
                <input type="text" class="datepicker px-4 py-2.5 text-sm rounded-xl outline-none transition-all" name="date_from" value="{{ request('date_from') }}"
                    style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-secondary); color-scheme: light;"
                    onmouseenter="this.style.borderColor='rgba(37,99,235,0.3)'"
                    onmouseleave="if(!this.matches(':focus'))this.style.borderColor='var(--border-color)'"
                    onfocus="this.style.borderColor='var(--accent-blue)'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.10)'"
                    onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'">
            </div>
            <div class="relative">
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Sampai Tanggal</label>
                <input type="text" class="datepicker px-4 py-2.5 text-sm rounded-xl outline-none transition-all" name="date_to" value="{{ request('date_to') }}"
                    style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-secondary); color-scheme: light;"
                    onmouseenter="this.style.borderColor='rgba(37,99,235,0.3)'"
                    onmouseleave="if(!this.matches(':focus'))this.style.borderColor='var(--border-color)'"
                    onfocus="this.style.borderColor='var(--accent-blue)'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.10)'"
                    onblur="this.style.borderColor='var(--border-color)'; this.style.boxShadow='none'">
            </div>
            <button type="submit"
                class="px-5 py-2.5 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                Filter
            </button>
            @if(request()->hasAny(['search','status','date_from','date_to']))
            <a href="{{ route('po.index') }}"
               class="px-4 py-2.5 text-sm rounded-xl transition-colors"
               style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-secondary);">
                Reset
            </a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="card animate-in" style="animation-delay: 0.15s;">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. PO</th>
                        <th>Customer</th>
                        @if(!auth()->user()->isSales())
                        <th>Sales Marketing</th>
                        @endif
                        <th>Grand Total</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchaseOrders as $order)
                    <tr>
                        <td>
                            <code class="text-xs px-2 py-1 rounded font-semibold"
                                style="background: var(--bg-secondary); color: var(--text-secondary);">
                                {{ $order->po_number }}
                            </code>
                        </td>
                        <td>
                            <p class="font-medium" style="color: var(--text-primary);">{{ $order->customer->company_name ?? '-' }}</p>
                            <p class="text-xs" style="color: var(--text-muted);">{{ $order->customer->company_code ?? '' }}</p>
                        </td>
                        @if(!auth()->user()->isSales())
                        <td style="color: var(--text-secondary);">{{ $order->sales->name ?? '-' }}</td>
                        @endif
                        <td>
                            <p class="font-semibold" style="color: var(--text-primary);">Rp {{ number_format($order->grand_total, 0, ',', '.') }}</p>
                            @if($order->tax_amount > 0)
                            <p class="text-xs" style="color: var(--text-muted);">termasuk PPN Rp {{ number_format($order->tax_amount, 0, ',', '.') }}</p>
                            @endif
                        </td>
                        <td>
                            @if($order->due_date)
                                @php $isOverdue = $order->due_date->isPast() && !in_array($order->status, ['Completed','Cancelled','Revisi']); @endphp
                                <span style="color: {{ $isOverdue ? 'var(--accent-rose)' : 'var(--text-secondary)' }}; font-weight: {{ $isOverdue ? '600' : '400' }};">
                                    {{ $order->due_date->format('d M Y') }}
                                    @if($isOverdue) <span class="text-xs">(Overdue!)</span> @endif
                                </span>
                            @else
                                <span style="color: var(--text-muted);">-</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $badgeClass = match($order->status) {
                                    'Goal'       => 'approved',
                                    'Revisi'     => 'rejected',
                                    'Tidak Goal' => 'rejected',
                                    default      => 'pending',
                                };
                            @endphp
                            <span class="status-badge {{ $badgeClass }}">
                                <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>
                            <div class="flex items-center gap-1">
                                <a href="{{ route('po.show', $order) }}" title="Detail"
                                    class="p-1.5 rounded-lg transition-colors"
                                    style="color: var(--text-muted);"
                                    onmouseenter="this.style.background='rgba(37,99,235,0.08)'; this.style.color='var(--accent-blue)'"
                                    onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--border-color);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="font-medium" style="color: var(--text-muted);">Belum ada Purchase Order</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchaseOrders->hasPages())
        <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
            {{ $purchaseOrders->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
