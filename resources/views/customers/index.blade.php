<x-app-layout>

    <!-- Page Header -->
    <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
        <div>
            <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Customers
                <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full" style="background: var(--bg-secondary); color: var(--text-secondary);">{{ $customers->total() }}</span>
            </h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola daftar pelanggan dan prospek bisnis</p>
        </div>

        @if(auth()->user()->hasPermission('CRUD') || auth()->user()->isSales() || auth()->user()->isLeader())
        <div class="flex items-center gap-2">
            <a href="{{ route('customers.export') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all hover:-translate-y-0.5"
                style="background: rgba(37,99,235,0.08); color: var(--accent-blue); border: 1px solid rgba(37,99,235,0.20);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export Excel
            </a>
            @if(auth()->user()->hasPermission('CRUD') || auth()->user()->isLeader())
            <button onclick="document.getElementById('importModal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl transition-all hover:-translate-y-0.5"
                style="background: rgba(5,150,105,0.10); color: var(--accent-emerald); border: 1px solid rgba(5,150,105,0.25);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import Excel
            </button>
            @endif
            <a href="{{ route('customers.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md"
               style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Customer
            </a>
        </div>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card filter-bar p-4 mb-5">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-[38px] -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <label class="block text-xs font-medium mb-1" style="color: var(--text-secondary);">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Nama perusahaan, kode, atau industri..."
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
                    'Prospect' => 'Prospect',
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                    'Blacklist' => 'Blacklist',
                ]" />
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                    class="px-5 py-2.5 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                    style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.25);">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                <a href="{{ route('customers.index') }}"
                   class="px-4 py-2.5 text-sm text-center rounded-xl transition-colors"
                   style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-secondary);">
                    Reset
                </a>
                @endif
                @if(auth()->user()->isAdminOrAbove())
                <a href="{{ route('customers.trash') }}" title="Arsip"
                   class="px-4 py-2.5 text-sm text-center rounded-xl transition-colors"
                   style="background: var(--bg-secondary); border: 1.5px solid var(--border-color); color: var(--text-muted);">
                    <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Trash
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <form id="bulk-approve-form" method="POST" action="{{ route('customers.bulk-approve') }}">
        @csrf

        @if(auth()->user()->isAdminOrAbove() && $customers->whereIn('status', ['Prospect', 'Pending'])->count() > 0)
        <div class="mb-3 flex justify-end">
            <button type="button" onclick="confirmBulkApprove()"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, var(--accent-emerald), #0d9488); box-shadow: 0 4px 12px rgba(5,150,105,0.30);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Approve Terpilih
            </button>
        </div>
        @endif

        <div class="card animate-in" style="animation-delay: 0.15s;">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            @if(auth()->user()->isAdminOrAbove())
                            <th class="w-10">
                                <input type="checkbox" id="selectAll" class="rounded cursor-pointer"
                                    style="accent-color: var(--accent-blue);" title="Pilih Semua Pending">
                            </th>
                            @endif
                            <th>Kode</th>
                            <th>Perusahaan</th>
                            <th>Industri</th>
                            <th>Kawasan</th>
                            <th>Sales Marketing</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                        <tr>
                            @if(auth()->user()->isAdminOrAbove())
                            <td>
                                @if(in_array($customer->status, ['Prospect', 'Pending']))
                                <input type="checkbox" name="customer_ids[]" value="{{ $customer->id }}"
                                    class="customer-checkbox rounded cursor-pointer"
                                    style="accent-color: var(--accent-blue);">
                                @endif
                            </td>
                            @endif

                            <td>
                                <code class="text-xs px-2 py-1 rounded" style="background: var(--bg-secondary); color: var(--text-secondary);">
                                    {{ $customer->company_code ?? '-' }}
                                </code>
                            </td>

                            <td>
                                <div>
                                    <p class="font-medium" style="color: var(--text-primary);">{{ $customer->company_name }}</p>
                                    @if($customer->city)
                                    <p class="text-xs" style="color: var(--text-muted);">{{ $customer->city }}</p>
                                    @endif
                                    {{-- Tampilkan keterangan pemilik untuk Sales yang BUKAN pemiliknya --}}
                                    @if(!auth()->user()->isAdminOrAbove() && $customer->sales_id !== auth()->id())
                                    <p class="text-[11px] mt-0.5 font-medium" style="color: var(--accent-amber);">
                                        <svg class="w-3 h-3 inline-block -mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        {{ $customer->sales->name ?? 'Admin' }}
                                    </p>
                                    @endif
                                </div>
                            </td>

                            <td style="color: var(--text-secondary);">{{ $customer->industry ?? '-' }}</td>
                            <td style="color: var(--text-secondary);">{{ $customer->region ?? '-' }}</td>
                            {{--
                                KOLOM SALES: Tampil untuk semua role
                                - Admin      : melihat nama Sales pemilik
                                - Sales Pemilik : tampil label "Saya" (hijau) agar langsung tahu ini miliknya
                                - Sales Lain    : tampil nama Sales pemilik
                            --}}
                            <td>
                                @if(auth()->user()->isAdminOrAbove())
                                    <span style="color: var(--text-secondary);">{{ $customer->sales->name ?? '-' }}</span>
                                @elseif($customer->sales_id === auth()->id())
                                    {{-- Badge hijau "Saya" untuk customer milik sendiri --}}
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: rgba(5,150,105,0.10); color: var(--accent-emerald); border: 1px solid rgba(5,150,105,0.20);">
                                        <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        Saya
                                    </span>
                                @else
                                    <span style="color: var(--text-muted); font-size: 0.8rem;">{{ $customer->sales->name ?? '-' }}</span>
                                @endif
                            </td>

                            <td>
                                @php
                                    $badgeClass = match($customer->status) {
                                        'Active'   => 'approved',
                                        'Prospect' => 'pending',
                                        default    => 'pending',
                                    };
                                @endphp
                                <span class="status-badge {{ $badgeClass }}">
                                    <span class="w-1.5 h-1.5 rounded-full inline-block" style="background: currentColor;"></span>
                                    {{ $customer->status }}
                                </span>
                            </td>

                            <td>
                                <div class="flex items-center gap-2">
                                    <!-- View -->
                                    <a href="{{ route('customers.show', $customer) }}" title="Detail"
                                        class="p-1.5 rounded-lg transition-colors inline-block"
                                        style="color: var(--text-muted);"
                                        onmouseenter="this.style.background='rgba(37,99,235,0.08)'; this.style.color='var(--accent-blue)'"
                                        onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if(in_array($customer->status, ['Prospect', 'Pending']) && auth()->user()->isAdminOrAbove())
                                    <!-- Approve -->
                                    <form action="{{ route('customers.approve', $customer) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Approve customer ini menjadi Active?')">
                                        @csrf
                                        <button type="submit" title="Approve"
                                            class="p-1.5 rounded-lg transition-colors"
                                            style="color: var(--text-muted);"
                                            onmouseenter="this.style.background='rgba(5,150,105,0.08)'; this.style.color='var(--accent-emerald)'"
                                            onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif

                                    {{-- Edit: hanya tampil untuk Admin ATAU Sales pemilik data ini --}}
                                    @if(auth()->user()->isAdminOrAbove() || (auth()->user()->isSales() && $customer->sales_id === auth()->id()))
                                    <!-- Edit -->
                                    <a href="{{ route('customers.edit', $customer) }}" title="Edit"
                                        class="p-1.5 rounded-lg transition-colors inline-block"
                                        style="color: var(--text-muted);"
                                        onmouseenter="this.style.background='rgba(217,119,6,0.08)'; this.style.color='var(--accent-amber)'"
                                        onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    @endif

                                    @if(auth()->user()->isAdminOrAbove())
                                    <!-- Delete -->
                                    <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Arsipkan customer {{ $customer->company_name }}? Data bisa dipulihkan dari Trash.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Arsipkan"
                                            class="p-1.5 rounded-lg transition-colors"
                                            style="color: var(--text-muted);"
                                            onmouseenter="this.style.background='rgba(225,29,72,0.08)'; this.style.color='var(--accent-rose)'"
                                            onmouseleave="this.style.background=''; this.style.color='var(--text-muted)'">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdminOrAbove() ? 8 : 7 }}" class="px-6 py-16 text-center">
                                <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--border-color);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="font-medium" style="color: var(--text-muted);">Tidak ada customer ditemukan</p>
                                <p class="text-xs mt-1" style="color: var(--text-muted); opacity: 0.7;">Coba ubah filter atau kata kunci pencarian</p>
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
    </form>

    <script>
        document.getElementById('selectAll')?.addEventListener('change', function() {
            document.querySelectorAll('.customer-checkbox').forEach(cb => cb.checked = this.checked);
        });

        function confirmBulkApprove() {
            let checked = document.querySelectorAll('.customer-checkbox:checked');
            if (checked.length === 0) {
                alert('Pilih minimal satu customer berstatus Prospect untuk di-approve.');
                return;
            }
            if (confirm('Approve ' + checked.length + ' customer terpilih menjadi Active? Kode perusahaan akan dibuat otomatis.')) {
                document.getElementById('bulk-approve-form').submit();
            }
        }
    </script>

    {{-- Modal Import Excel --}}
    @if(auth()->user()->hasPermission('CRUD') || auth()->user()->isLeader() || auth()->user()->isSales())
    <div id="importModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(15,23,42,0.5); backdrop-filter: blur(4px);">
        <div class="card p-6 w-full max-w-md mx-4" style="box-shadow: var(--shadow-lg);">
            <div class="flex justify-between items-center mb-5">
                <h3 class="text-base font-semibold" style="color: var(--text-primary);">Import Data Customer</h3>
                <button onclick="document.getElementById('importModal').classList.add('hidden')"
                    class="p-1.5 rounded-lg transition-colors"
                    style="color: var(--text-muted);"
                    onmouseenter="this.style.background='var(--bg-secondary)'"
                    onmouseleave="this.style.background=''">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="mb-4 text-sm p-3 rounded-xl" style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.15);">
                <p class="font-semibold mb-1" style="color: var(--accent-blue);">Panduan Format Kolom Excel (Baris 1):</p>
                <div class="grid grid-cols-2 gap-x-4 text-xs font-mono" style="color: var(--text-secondary);">
                    <ul class="list-disc pl-4 space-y-0.5">
                        <li>nama_perusahaan <span style="color: var(--accent-rose);">*</span></li>
                        <li>brand</li>
                        <li>jenis_pelanggan</li>
                        <li>industri</li>
                        <li>skala_perusahaan</li>
                        <li>kategori_area</li>
                        <li>kawasan</li>
                        <li>nib</li>
                        <li>divisi</li>
                        <li>alamat</li>
                        <li>kota</li>
                        <li>provinsi</li>
                        <li>kode_pos</li>
                    </ul>
                    <ul class="list-disc pl-4 space-y-0.5">
                        <li>negara</li>
                        <li>telp</li>
                        <li>telepon_kantor</li>
                        <li>whatsapp</li>
                        <li>preferred_contact</li>
                        <li>email</li>
                        <li>website</li>
                        <li>npwp</li>
                        <li>cp_nama</li>
                        <li>cp_jabatan</li>
                        <li>cp_email</li>
                        <li>cp_telepon</li>
                        <li>catatan</li>
                    </ul>
                </div>
                <p class="text-xs mt-2" style="color: var(--text-muted);">Kolom <span style="color: var(--accent-rose);">*</span> wajib diisi. Nama kolom harus <strong>tepat</strong> (huruf kecil, underscore). Download template <a href="#" onclick="alert('Silakan buat file Excel dengan header di atas')" style="color: var(--accent-blue); text-decoration: underline;">di sini</a>.</p>
            </div>

            <form method="POST" action="{{ route('customers.import.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-5">
                    <label class="block text-xs font-semibold mb-2" style="color: var(--text-secondary);">Pilih File (.xlsx / .csv)</label>
                    <input type="file" name="file" accept=".xlsx,.csv,.xls" required
                        class="block w-full text-sm rounded-xl cursor-pointer"
                        style="border: 1px solid var(--border-color); padding: 8px; color: var(--text-secondary);">
                </div>

                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 py-2.5 text-sm font-semibold text-white rounded-xl transition-all hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                        Upload &amp; Import
                    </button>
                    <button type="button"
                        onclick="document.getElementById('importModal').classList.add('hidden')"
                        class="flex-1 py-2.5 text-sm font-medium rounded-xl transition-colors"
                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</x-app-layout>
