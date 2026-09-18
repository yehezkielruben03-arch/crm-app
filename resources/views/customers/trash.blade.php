<x-app-layout>

    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-rose);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Trash Customer
                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full" style="background: var(--bg-secondary); color: var(--text-secondary);">{{ $customers->total() }}</span>
            </h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Data customer yang telah diarsipkan</p>
        </div>
        <div>
            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all hover:-translate-y-0.5"
               style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-secondary);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali
            </a>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr style="background: var(--bg-secondary);">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Kode</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Nama Perusahaan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Sales Marketing</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Diarsipkan</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Aksi</th>
                    </tr>
                </thead>
                <tbody style="border-top: 1px solid var(--border-color);">
                    @forelse($customers as $customer)
                    <tr class="transition-colors" style="border-bottom: 1px solid var(--border-color);" onmouseenter="this.style.background='var(--bg-secondary)'" onmouseleave="this.style.background=''">
                        <td class="px-4 py-3 text-sm font-mono" style="color: var(--text-secondary);">{{ $customer->company_code ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium" style="color: var(--text-primary);">{{ $customer->company_name }}</span>
                        </td>
                        <td class="px-4 py-3 text-sm" style="color: var(--text-secondary);">{{ $customer->sales->name ?? '-' }}</td>
                        <td class="px-4 py-3 text-sm" style="color: var(--text-muted);">{{ $customer->deleted_at->format('d M Y, H:i') }}</td>
                        <td class="px-4 py-3">
                            <form action="{{ route('customers.restore', $customer->id) }}" method="POST" class="inline"
                                onsubmit="return confirm('Pulihkan customer {{ $customer->company_name }}?')">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-all"
                                    style="background: rgba(5,150,105,0.10); color: var(--accent-emerald); border: 1px solid rgba(5,150,105,0.20);"
                                    onmouseenter="this.style.background='rgba(5,150,105,0.18)'"
                                    onmouseleave="this.style.background='rgba(5,150,105,0.10)'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Pulihkan
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--border-color);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <p class="font-medium" style="color: var(--text-muted);">Trash kosong</p>
                            <p class="text-xs mt-1" style="color: var(--text-muted); opacity: 0.7;">Tidak ada customer yang diarsipkan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div class="px-6 py-4" style="border-top: 1px solid var(--border-color);">
            {{ $customers->links() }}
        </div>
        @endif
    </div>

</x-app-layout>
