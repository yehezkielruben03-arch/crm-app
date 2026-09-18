<x-app-layout>
    <div x-data="{
        createModalOpen: false,
        editModalOpen: false,
        editForm: {
            id: null,
            action: '',
            nama_vendor: '',
            kategori: '',
            pic: '',
            kontak: '',
            npwp: '',
            bank: '',
            rekening: '',
            status: 'Active',
            alamat: ''
        },
        openEdit(vendor) {
            this.editForm = {
                id: vendor.id,
                action: '{{ url('vendors') }}/' + vendor.id,
                nama_vendor: vendor.nama_vendor || '',
                kategori: vendor.kategori || '',
                pic: vendor.pic || '',
                kontak: vendor.kontak || '',
                npwp: vendor.npwp || '',
                bank: vendor.bank || '',
                rekening: vendor.rekening || '',
                status: vendor.status || 'Active',
                alamat: vendor.alamat || ''
            };
            this.editModalOpen = true;
        }
    }">
        <!-- Page Header -->
        <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in" style="animation-delay: 0.05s;">
            <div>
                <h1 class="text-xl font-bold flex items-center gap-2" style="color: var(--text-primary);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-blue);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    Master Vendor & Supplier
                    <span class="ml-2 px-2 py-0.5 text-xs font-semibold rounded-full" style="background: var(--bg-secondary); color: var(--text-secondary);">{{ $totalCount }}</span>
                </h1>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola rekanan supplier hardware, IT, CCTV, kabel, material, dan subcon</p>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="createModalOpen = true"
                   class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white rounded-xl transition-all hover:-translate-y-0.5 hover:shadow-md"
                   style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 12px rgba(37,99,235,0.30);">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Tambah Vendor Baru
                </button>
            </div>
        </div>

        @if (session('success'))
            <div class="p-4 mb-5 text-sm font-medium rounded-xl flex items-center gap-3 animate-in" style="background: rgba(16,185,129,0.12); color: var(--accent-emerald); border: 1px solid rgba(16,185,129,0.25);">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="p-4 mb-5 text-sm font-medium rounded-xl animate-in" style="background: rgba(244,63,94,0.12); color: var(--accent-rose); border: 1px solid rgba(244,63,94,0.25);">
                <div class="font-bold mb-1">Perhatian:</div>
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 gap-4 mb-6 sm:grid-cols-3">
            <div class="card p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Total Rekanan</p>
                    <p class="text-2xl font-bold mt-1" style="color: var(--text-primary);">{{ $totalCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: rgba(37,99,235,0.1); color: var(--accent-blue);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="card p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--accent-emerald);">Vendor Aktif</p>
                    <p class="text-2xl font-bold mt-1" style="color: var(--text-primary);">{{ $activeCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: rgba(16,185,129,0.1); color: var(--accent-emerald);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
            </div>
            <div class="card p-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted);">Vendor Inactive</p>
                    <p class="text-2xl font-bold mt-1" style="color: var(--text-primary);">{{ $inactiveCount }}</p>
                </div>
                <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: rgba(100,116,139,0.1); color: var(--text-muted);">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="card filter-bar p-4 mb-6">
            <form method="GET" action="{{ route('vendors.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Cari Vendor</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder="Nama PT, PIC, kontak, rekening, atau NPWP..."
                            class="w-full pl-9 pr-3 py-2 text-sm rounded-xl outline-none"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Kategori Vendor</label>
                    <select name="kategori" class="w-full px-3 py-2 text-sm rounded-xl outline-none"
                        style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                        <option value="">-- Semua Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('kategori') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex-1">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Status</label>
                        <select name="status" class="w-full px-3 py-2 text-sm rounded-xl outline-none"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            <option value="">-- Semua --</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="px-4 py-2 text-sm font-semibold rounded-xl text-white mt-5"
                            style="background: var(--accent-blue);">
                            Filter
                        </button>
                    </div>
                    @if(request()->hasAny(['search', 'kategori', 'status']))
                    <div>
                        <a href="{{ route('vendors.index') }}" class="px-3 py-2 text-sm font-semibold rounded-xl inline-block mt-5 text-center"
                            style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                            Reset
                        </a>
                    </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Vendor Table -->
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-xs font-semibold uppercase tracking-wider" style="background: var(--bg-secondary); color: var(--text-muted); border-bottom: 1px solid var(--border-color);">
                            <th class="px-5 py-3.5">Nama Vendor & Kategori</th>
                            <th class="px-5 py-3.5">PIC & Kontak</th>
                            <th class="px-5 py-3.5">Bank & Rekening</th>
                            <th class="px-5 py-3.5">Alamat Lengkap</th>
                            <th class="px-5 py-3.5 text-center">Status</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color: var(--border-color);">
                        @forelse($vendors as $vendor)
                        <tr class="transition-colors hover:bg-gray-50/5 dark:hover:bg-white/5">
                            <td class="px-5 py-4 align-top">
                                <p class="font-bold text-sm" style="color: var(--text-primary);">{{ $vendor->nama_vendor }}</p>
                                <div class="flex items-center gap-2 mt-1">
                                    @if($vendor->kategori)
                                        <span class="text-[11px] font-medium px-2 py-0.5 rounded-full" style="background: rgba(37,99,235,0.1); color: var(--accent-blue);">
                                            {{ $vendor->kategori }}
                                        </span>
                                    @endif
                                    @if($vendor->npwp)
                                        <span class="text-xs" style="color: var(--text-muted);">NPWP: {{ $vendor->npwp }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-4 align-top text-sm">
                                <p class="font-medium" style="color: var(--text-primary);">{{ $vendor->pic ?? '-' }}</p>
                                <p class="text-xs mt-0.5" style="color: var(--text-muted);">{{ $vendor->kontak ?? '-' }}</p>
                            </td>
                            <td class="px-5 py-4 align-top text-sm">
                                @if($vendor->bank || $vendor->rekening)
                                    <p class="font-medium" style="color: var(--text-primary);">{{ $vendor->bank ?? 'Bank' }}</p>
                                    <p class="text-xs font-mono mt-0.5" style="color: var(--text-secondary);">{{ $vendor->rekening ?? '-' }}</p>
                                @else
                                    <span class="text-xs" style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top text-xs max-w-xs" style="color: var(--text-secondary);">
                                {{ $vendor->alamat ?? '-' }}
                            </td>
                            <td class="px-5 py-4 align-top text-center">
                                @if($vendor->status === 'Active')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background: rgba(16,185,129,0.12); color: var(--accent-emerald);">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background: rgba(100,116,139,0.15); color: var(--text-muted);">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 align-top text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="openEdit({{ $vendor->toJson() }})"
                                        class="px-2.5 py-1 text-xs font-semibold rounded-lg transition-colors"
                                        style="background: rgba(37,99,235,0.1); color: var(--accent-blue);">
                                        Edit
                                    </button>
                                    <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus vendor {{ $vendor->nama_vendor }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2 py-1 text-xs font-semibold rounded-lg transition-colors hover:opacity-80" style="color: var(--accent-rose);">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm" style="color: var(--text-muted);">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                Tidak ada data vendor yang cocok dengan kriteria filter.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL TAMBAH VENDOR -->
        <div x-show="createModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="createModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-2xl p-6 shadow-xl" style="background: var(--bg-primary); border: 1px solid var(--border-color);">
                    <div class="flex items-center justify-between pb-3 mb-4" style="border-bottom: 1px solid var(--border-color);">
                        <h3 class="text-base font-bold" style="color: var(--text-primary);">Tambah Vendor Baru</h3>
                        <button type="button" @click="createModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form action="{{ route('vendors.store') }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Vendor / PT <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_vendor" required placeholder="Contoh: PT Trikomindo Karunia Utama"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Kategori Vendor</label>
                                <input list="category_options" name="kategori" placeholder="Pilih atau ketik kategori..."
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                <datalist id="category_options">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Status</label>
                                <select name="status" class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                    <option value="Active" selected>Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">PIC / Kontak Person</label>
                                <input type="text" name="pic" placeholder="Nama penanggung jawab sales/account"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">No. Telepon / WhatsApp</label>
                                <input type="text" name="kontak" placeholder="021-xxxxxxx / 0812xxxx"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Bank</label>
                                <input type="text" name="bank" placeholder="BCA / Mandiri / BRI / dll"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">No. Rekening</label>
                                <input type="text" name="rekening" placeholder="Nomor rekening transfer"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">NPWP</label>
                                <input type="text" name="npwp" placeholder="00.000.000.0-000.000"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Alamat Lengkap</label>
                                <textarea name="alamat" rows="2" placeholder="Alamat kantor / gudang vendor"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-end gap-3 pt-3" style="border-top: 1px solid var(--border-color);">
                            <button type="button" @click="createModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl" style="color: var(--text-secondary);">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl text-white" style="background: var(--accent-blue);">
                                Simpan Vendor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL EDIT VENDOR -->
        <div x-show="editModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="editModalOpen = false"></div>
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative w-full max-w-2xl rounded-2xl p-6 shadow-xl" style="background: var(--bg-primary); border: 1px solid var(--border-color);">
                    <div class="flex items-center justify-between pb-3 mb-4" style="border-bottom: 1px solid var(--border-color);">
                        <h3 class="text-base font-bold" style="color: var(--text-primary);">Edit Rekanan Vendor</h3>
                        <button type="button" @click="editModalOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form :action="editForm.action" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Vendor / PT <span class="text-red-500">*</span></label>
                                <input type="text" name="nama_vendor" x-model="editForm.nama_vendor" required
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Kategori Vendor</label>
                                <input list="category_options_edit" name="kategori" x-model="editForm.kategori"
                                    placeholder="Pilih atau ketik kategori..."
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                <datalist id="category_options_edit">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}"></option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Status Vendor</label>
                                <select name="status" x-model="editForm.status" class="w-full px-3 py-2 rounded-xl outline-none font-semibold"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                    <option value="Active">Active (Aktif)</option>
                                    <option value="Inactive">Inactive (Nonaktif)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">PIC / Kontak Person</label>
                                <input type="text" name="pic" x-model="editForm.pic"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">No. Telepon / WhatsApp</label>
                                <input type="text" name="kontak" x-model="editForm.kontak"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Bank</label>
                                <input type="text" name="bank" x-model="editForm.bank"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">No. Rekening</label>
                                <input type="text" name="rekening" x-model="editForm.rekening"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">NPWP</label>
                                <input type="text" name="npwp" x-model="editForm.npwp"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Alamat Lengkap</label>
                                <textarea name="alamat" x-model="editForm.alamat" rows="2"
                                    class="w-full px-3 py-2 rounded-xl outline-none"
                                    style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"></textarea>
                            </div>
                        </div>
                        <div class="mt-6 flex items-center justify-end gap-3 pt-3" style="border-top: 1px solid var(--border-color);">
                            <button type="button" @click="editModalOpen = false" class="px-4 py-2 text-xs font-semibold rounded-xl" style="color: var(--text-secondary);">
                                Batal
                            </button>
                            <button type="submit" class="px-5 py-2 text-xs font-semibold rounded-xl text-white" style="background: var(--accent-blue);">
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
