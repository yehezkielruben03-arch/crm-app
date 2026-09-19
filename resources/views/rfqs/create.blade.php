<x-app-layout>

    <div class="flex items-center gap-3 mb-6 animate-in" style="animation-delay: 0.05s;">
        <a href="{{ route('rfq.index') }}"
           class="p-2 rounded-lg transition-colors"
           style="color: var(--text-muted);"
           onmouseenter="this.style.background='#f8fafc'"
           onmouseleave="this.style.background=''">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">{{ request('type') === 'Projek' ? 'Buat RFQ Projek' : 'Buat RFQ Baru' }}</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">{{ request('type') === 'Projek' ? 'Buat RFQ projek dengan 3 blok kategori dan pricing langsung' : 'Buat request quotation untuk klien' }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('rfq.store') }}" id="rfqForm" x-data="rfqForm()">
        @csrf

        {{-- Baris Atas: Info RFQ + Ringkasan --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3 mb-5">

            {{-- Kolom Kiri: Informasi RFQ --}}
            <div class="lg:col-span-2">

                {{-- Card: Informasi RFQ --}}
                <div class="card p-5 md:p-6 relative z-30" style="background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 24px 40px -26px rgba(15,23,42,0.12);">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid #e2e8f0;">
                        Informasi RFQ
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Customer --}}
                        <div class="sm:col-span-2" x-data="customerSelect()">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Customer <span style="color: var(--accent-rose);">*</span>
                            </label>
                            
                            <div class="relative" @click.outside="open = false">
                                <button type="button" @click="open = !open"
                                    class="w-full flex items-center justify-between gap-2 px-4 py-2.5 text-sm font-medium rounded-xl outline-none transition-all cursor-pointer text-left"
                                    style="background: #f8fafc; border: 1.5px solid #e2e8f0; color: var(--text-primary);"
                                    :style="open ? 'border-color: var(--accent-blue); box-shadow: 0 0 0 3px rgba(37,99,235,0.10);' : ''">
                                    <span x-text="selectedLabel || 'Pilih Customer'" :style="!selectedId ? 'color: var(--text-muted);' : ''"></span>
                                    <svg class="w-5 h-5 flex-shrink-0 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                </button>
                                
                                <div x-show="open" x-cloak
                                    class="absolute left-0 mt-1.5 rounded-xl overflow-hidden w-full"
                                    style="z-index: 50; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 8px 32px rgba(15,23,42,0.18);">
                                    <div class="p-2 sticky top-0" style="background: #ffffff; border-bottom: 1px solid #e2e8f0;">
                                        <input type="text" x-model="search" placeholder="Cari nama atau kode PTI..."
                                            class="w-full px-3 py-2 text-sm rounded-lg outline-none"
                                            style="background: #f8fafc; border: 1px solid #e2e8f0; color: var(--text-primary);">
                                    </div>
                                    <div class="py-1 max-h-64 overflow-y-auto divide-y divide-slate-100">
                                        <template x-for="c in filteredCustomers" :key="c.id">
                                            <button type="button" @click="selectCustomer(c)"
                                                class="w-full flex flex-col items-start gap-1 px-4 py-2.5 text-sm text-left transition-all duration-100 hover:bg-slate-50"
                                                :style="selectedId == c.id ? 'background: rgba(37,99,235,0.08); color: var(--accent-blue); font-weight:600;' : 'color: var(--text-primary);'">
                                                <div class="w-full flex items-center justify-between gap-2">
                                                    <span class="font-medium" x-text="c.company_name"></span>
                                                    <code class="text-[10px] px-1.5 py-0.5 rounded font-mono" style="background: rgba(148,163,184,0.15);" x-text="c.company_code"></code>
                                                </div>
                                                <div x-show="c.address" class="text-xs text-slate-500 line-clamp-1 flex items-center gap-1">
                                                    <svg class="w-3 h-3 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                                    <span x-text="c.address"></span>
                                                </div>
                                            </button>
                                        </template>
                                        <div x-show="filteredCustomers.length === 0" class="px-4 py-3 text-xs text-center" style="color: var(--text-muted);">
                                            Customer tidak ditemukan.
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <input type="hidden" name="customer_id" x-model="selectedId">
                            @error('customer_id')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- Customer Detail Box & PIC --}}
                        <div class="sm:col-span-2 grid grid-cols-1 gap-4">
                            <div id="customer-detail-box"></div>
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-semibold" style="color: var(--text-secondary);">
                                        PIC Tujuan <span style="color: var(--accent-rose);">*</span>
                                    </label>
                                    <button type="button" onclick="openInlinePicModal()" id="btn-add-inline-pic"
                                        class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        + Tambah Kontak Baru
                                    </button>
                                </div>
                                <select name="customer_contact_id" id="contact-select" required
                                    style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: #f8fafc; border: 1px solid #e2e8f0; color: var(--text-primary);">
                                    <option value="">Pilih PIC (Pilih Customer Dulu)</option>
                                </select>
                                @error('customer_contact_id')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Tanggal Dibutuhkan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Tgl. Penawaran (Dibutuhkan)</label>
                            <input type="date" name="need_date" value="{{ old('need_date') }}" required
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: #f8fafc; border: 1px solid #e2e8f0; color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='#e2e8f0'">
                        </div>

                        {{-- Priority --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Prioritas <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <x-custom-select name="priority" :value="old('priority', 'Normal')" placeholder="Pilih Priority" :options="['Normal' => 'Normal', 'Urgent' => 'Urgent', 'High Priority' => 'High Priority']" />
                        </div>

                        {{-- Tipe RFQ --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-2" style="color: var(--text-secondary);">
                                Tipe RFQ <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <div class="flex flex-wrap gap-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="type" value="Non Projek" x-model="type" class="w-4 h-4" style="accent-color: var(--accent-blue);">
                                    <span class="text-sm font-medium" style="color: var(--text-primary);">📦 Non Projek</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="type" value="Projek" x-model="type" class="w-4 h-4" style="accent-color: var(--accent-blue);">
                                    <span class="text-sm font-medium" style="color: var(--text-primary);">🏢 Projek</span>
                                </label>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Catatan</label>
                            <textarea name="notes" rows="3" placeholder="Catatan atau keterangan tambahan..."
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: #f8fafc; border: 1px solid #e2e8f0; color: var(--text-primary); resize: vertical; min-height: 80px;"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='#e2e8f0'">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
            {{-- /Kolom Kiri --}}

            {{-- Kolom Kanan: Ringkasan --}}
            <div class="space-y-5 self-start">
                <div class="card p-5" style="background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 24px 40px -26px rgba(15,23,42,0.12);">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid #e2e8f0;">
                        Ringkasan
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl" style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.12);">
                            <span class="text-sm" style="color: var(--text-muted);">Total Item</span>
                            <span class="text-lg font-bold" style="color: var(--accent-blue);" x-text="totalItems()"></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-3 mt-4" style="width:100%; box-sizing:border-box;">
                        <a href="{{ route('rfq.index') }}"
                           class="inline-flex items-center justify-center gap-2 text-sm font-medium rounded-xl transition-all active:scale-95"
                           style="flex: 1; min-width: 0; padding: 0.75rem 1rem; background: #ffffff; border: 1px solid #e2e8f0; color: var(--text-secondary); white-space: nowrap;">
                            Batal
                        </a>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 text-sm font-semibold text-white rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
                            style="flex: 2; min-width: 0; padding: 0.75rem 1rem; background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35); white-space: nowrap;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan RFQ
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Baris Bawah: Detail Item FULL WIDTH --}}
        <div class="card p-5 md:p-6 animate-in" style="animation-delay: 0.15s; background: #ffffff; border: 1px solid #e2e8f0; box-shadow: 0 24px 40px -26px rgba(15,23,42,0.12);">

                    {{-- Judul Detail Item --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3" style="border-bottom: 1px solid #e2e8f0;">
                        <div>
                            <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Detail Item</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">
                                <span x-show="type === 'Non Projek'">Tambah produk atau jasa yang diminta</span>
                                <span x-show="type === 'Projek'">Tambah item berdasarkan 3 blok kategori (Hardware, Jasa, Material)</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button x-show="type === 'Non Projek'" type="button" @click="addNonProjekItem()"
                                class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg transition-all active:scale-95"
                                style="background: rgba(37,99,235,0.1); border: 1px solid rgba(37,99,235,0.18); color: var(--accent-blue);">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Item
                            </button>
                            <div x-show="type === 'Projek'" class="flex items-center gap-1.5">
                                <span class="text-xs text-slate-400 mr-1 hidden md:inline">Tambah Item:</span>
                                <button type="button" @click="addProjekItem('Hardware')"
                                    class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1.5 rounded-lg bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 transition active:scale-95">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    + Hardware
                                </button>
                                <button type="button" @click="addProjekItem('Jasa Pemasangan')"
                                    class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition active:scale-95">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    + Jasa
                                </button>
                                <button type="button" @click="addProjekItem('Material Support')"
                                    class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition active:scale-95">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    + Material
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Container NON PROJEK --}}
                    <div x-show="type === 'Non Projek'" class="overflow-x-auto">
                        <table class="w-full text-sm" style="min-width: 600px; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8fafc;">
                                    <th class="text-left px-3 py-2.5 text-xs font-semibold w-8" style="color: var(--text-muted); border-bottom: 1px solid #e2e8f0;">#</th>
                                    <th class="text-left px-3 py-2.5 text-xs font-semibold" style="color: var(--text-muted); border-bottom: 1px solid #e2e8f0;">Nama Item / Jasa *</th>
                                    <th class="text-left px-3 py-2.5 text-xs font-semibold w-24" style="color: var(--text-muted); border-bottom: 1px solid #e2e8f0;">Qty *</th>
                                    <th class="text-left px-3 py-2.5 text-xs font-semibold w-24" style="color: var(--text-muted); border-bottom: 1px solid #e2e8f0;">Satuan</th>
                                    <th class="text-left px-3 py-2.5 text-xs font-semibold" style="color: var(--text-muted); border-bottom: 1px solid #e2e8f0;">Deskripsi</th>
                                    <th class="text-center px-3 py-2.5 text-xs font-semibold w-16" style="color: var(--text-muted); border-bottom: 1px solid #e2e8f0;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, index) in nonProjekItems" :key="item.id">
                                    <tr class="group" style="border-bottom: 1px solid #e2e8f0;"
                                        :style="index % 2 === 1 ? 'background: #f8fafc;' : ''">
                                        <td class="px-3 py-2.5 text-xs font-mono text-center" style="color: var(--text-muted); vertical-align: middle;" x-text="index + 1"></td>
                                        <td class="px-3 py-2" style="vertical-align: middle;">
                                            <input type="text" :name="`items[${item.id}][product_name]`"
                                                x-model="item.product_name"
                                                placeholder="Nama barang / jasa..."
                                                style="width:100%; padding: 0.45rem 0.6rem; border-radius: 0.5rem; font-size: 0.8rem; outline: none; background: #ffffff; border: 1px solid #e2e8f0; color: var(--text-primary);"
                                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                                onblur="this.style.borderColor='#e2e8f0'">
                                        </td>
                                        <td class="px-3 py-2" style="vertical-align: middle;">
                                            <input type="number" :name="`items[${item.id}][qty]`" step="0.01"
                                                x-model="item.qty"
                                                placeholder="0"
                                                style="width:100%; padding: 0.45rem 0.6rem; border-radius: 0.5rem; font-size: 0.8rem; outline: none; background: #ffffff; border: 1px solid #e2e8f0; color: var(--text-primary); text-align: center;"
                                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                                onblur="this.style.borderColor='#e2e8f0'">
                                        </td>
                                        <td class="px-3 py-2" style="vertical-align: middle;">
                                            <input type="text" list="unit-options" :name="`items[${item.id}][unit]`"
                                                x-model="item.unit"
                                                placeholder="pcs, unit..."
                                                style="width:100%; padding: 0.45rem 0.6rem; border-radius: 0.5rem; font-size: 0.8rem; outline: none; background: #ffffff; border: 1px solid #e2e8f0; color: var(--text-primary);"
                                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                                onblur="this.style.borderColor='#e2e8f0'">
                                        </td>
                                        <td class="px-3 py-2" style="vertical-align: middle;">
                                            <input type="text" :name="`items[${item.id}][description]`"
                                                x-model="item.description"
                                                placeholder="Spesifikasi, keterangan..."
                                                style="width:100%; padding: 0.45rem 0.6rem; border-radius: 0.5rem; font-size: 0.8rem; outline: none; background: #ffffff; border: 1px solid #e2e8f0; color: var(--text-primary);"
                                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                                onblur="this.style.borderColor='#e2e8f0'">
                                        </td>
                                        <td class="px-3 py-2 text-center" style="vertical-align: middle;">
                                            <button type="button" @click="removeNonProjekItem(item.id)" :disabled="nonProjekItems.length <= 1"
                                                class="p-1.5 rounded-lg transition-all"
                                                :style="nonProjekItems.length <= 1 ? 'opacity: 0.3; cursor: not-allowed; color: var(--accent-rose);' : 'color: var(--accent-rose); background: rgba(239,68,68,0.08);'"
                                                title="Hapus item">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="px-3 py-2.5">
                                        <button type="button" @click="addNonProjekItem()"
                                            class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg transition-all active:scale-95"
                                            style="background: rgba(37,99,235,0.08); border: 1px solid rgba(37,99,235,0.15); color: var(--accent-blue);">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Tambah Baris Item
                                        </button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Container PROJEK --}}
                    <datalist id="unit-options">
                        <option value="Unit"></option>
                        <option value="Set"></option>
                        <option value="Lot"></option>
                        <option value="Pcs"></option>
                        <option value="Box"></option>
                        <option value="Paket"></option>
                        <option value="Meter"></option>
                        <option value="Roll"></option>
                        <option value="Buah"></option>
                    </datalist>

                    <datalist id="ceiling-options">
                        <option value="1">1 - Tanpa Pembulatan</option>
                        <option value="10">10 - Puluhan</option>
                        <option value="100">100 - Ratusan</option>
                        <option value="1000">1.000 - Ribuan</option>
                        <option value="5000">5.000 - Rp 5.000</option>
                        <option value="10000">10.000 - Puluh Ribuan</option>
                        <option value="50000">50.000 - Rp 50.000</option>
                        <option value="100000">100.000 - Rp 100.000</option>
                        <option value="500000">500.000 - Rp 500.000</option>
                    </datalist>

                    <div x-show="type === 'Projek'" x-cloak>
                        <div class="overflow-x-auto">
                            <div class="pmx-grid pmx-grid--sales">
                                {{-- HEADER -- blueprint grid sama persis dengan baris data --}}
                                <div class="pmx-row pmx-row--head">
                                    <span class="pmx-cell pmx-cell--head text-center">#</span>
                                    <span class="pmx-cell pmx-cell--head">Item Descriptions</span>
                                    <span class="pmx-cell pmx-cell--head text-center">Qty</span>
                                    <span class="pmx-cell pmx-cell--head text-center">Units</span>
                                    <span class="pmx-cell pmx-cell--head"></span>
                                </div>

                                <template x-for="category in categories" :key="category.name">
                                    <div>
                                        {{-- Sub-Header Kategori: soft gray full-bleed, urutan romawi statis --}}
                                        <div class="pmx-row pmx-row--group">
                                            <span class="pmx-cell pmx-cell--group">
                                                <span class="pmx-group-label" x-text="category.label"></span>
                                                <span class="pmx-group-count" x-text="category.items.length + ' item'"></span>
                                                <button type="button" @click="addProjekItem(category.name)" class="pmx-group-add">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Tambah
                                                </button>
                                            </span>
                                        </div>

                                        <template x-for="(item, index) in category.items" :key="item.id">
                                            <div class="pmx-row pmx-row--data">
                                                <input type="hidden" :name="`items[${item.id}][category]`" :value="category.name">

                                            {{-- Baris Utama: No | Item Descriptions (stacked) | Qty | Units | Hapus --}}
                                            <span class="pmx-cell pmx-cell--num text-center" x-text="itemNumber(category, index)"></span>
                                            <span class="pmx-cell pmx-cell--desc">
                                                <input type="text" :name="`items[${item.id}][product_name]`" x-model="item.product_name" placeholder="Nama Item *" class="pmx-input pmx-input--name">
                                                <textarea rows="1" :name="`items[${item.id}][detail_item]`" x-model="item.detail_item" class="pmx-input pmx-input--spec auto-grow" placeholder="Spesifikasi / Detail Item"></textarea>
                                                <textarea rows="1" :name="`items[${item.id}][description]`" x-model="item.description" class="pmx-input pmx-input--spec auto-grow" placeholder="Deskripsi Singkat"></textarea>
                                            </span>
                                            <span class="pmx-cell pmx-cell--center">
                                                <input type="number" :name="`items[${item.id}][qty]`" step="0.01" x-model="item.qty" placeholder="Qty *" class="pmx-input pmx-input--center">
                                            </span>
                                            <span class="pmx-cell pmx-cell--center">
                                                <input type="text" list="unit-options" :name="`items[${item.id}][unit]`" x-model="item.unit" placeholder="Unit" class="pmx-input pmx-input--center">
                                            </span>
                                            <span class="pmx-cell pmx-cell--center">
                                                <button type="button" @click="removeProjekItem(category.name, item.id)" class="pmx-del" title="Hapus item">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </span>

                                            @if(auth()->user()->isAdminOrAbove())
                                            {{-- Band harga admin: HPP | Ongkir Pedia | Ongkir Pelanggan | Margin | Ceiling | Validity --}}
                                            <span class="pmx-cell pmx-cell--band">
                                                <div class="pmx-band">
                                                    <div>
                                                        <label class="pmx-band-label">HPP (Rp)</label>
                                                        <input type="number" :name="`items[${item.id}][hpp]`" min="0" step="0.01" x-model="item.hpp" placeholder="0" class="pmx-input">
                                                    </div>
                                                    <div>
                                                        <label class="pmx-band-label">Ongkir Pedia (Rp)</label>
                                                        <input type="number" :name="`items[${item.id}][ongkir_pedia]`" min="0" step="0.01" x-model="item.ongkir_pedia" placeholder="0" class="pmx-input">
                                                    </div>
                                                    <div>
                                                        <label class="pmx-band-label">Ongkir Pelanggan (Rp)</label>
                                                        <input type="number" :name="`items[${item.id}][ongkir_pelanggan]`" min="0" step="0.01" x-model="item.ongkir_pelanggan" placeholder="0" class="pmx-input">
                                                    </div>
                                                    <div>
                                                        <label class="pmx-band-label">Margin (%)</label>
                                                        <input type="number" :name="`items[${item.id}][margin]`" min="0" step="1" x-model="item.margin" placeholder="25" class="pmx-input pmx-input--center">
                                                    </div>
                                                    <div>
                                                        <label class="pmx-band-label">Ceiling (Pembulatan)</label>
                                                        <input type="number" list="ceiling-options" :name="`items[${item.id}][ceiling]`" x-model="item.ceiling" min="1" step="1" placeholder="10000" class="pmx-input pmx-input--center">
                                                        <div class="flex items-center gap-1 mt-1">
                                                            <button type="button" @click="item.ceiling = '1'" class="flex-1 text-[9px] py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200" title="Tanpa Pembulatan">1</button>
                                                            <button type="button" @click="item.ceiling = '1000'" class="flex-1 text-[9px] py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200" title="Ribuan">1k</button>
                                                            <button type="button" @click="item.ceiling = '10000'" class="flex-1 text-[9px] py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200" title="Puluh Ribuan">10k</button>
                                                            <button type="button" @click="item.ceiling = '50000'" class="flex-1 text-[9px] py-0.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200" title="Rp 50.000">50k</button>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <label class="pmx-band-label">Masa Berlaku (hari)</label>
                                                        <input type="number" :name="`items[${item.id}][validity_days]`" min="1" step="1" x-model="item.validity_days" placeholder="7" class="pmx-input pmx-input--center">
                                                    </div>
                                                </div>
                                            </span>
                                            @endif
                                            </div>
                                        </template>

                                        <div x-show="category.items.length === 0" class="pmx-empty">Belum ada item di kategori ini.</div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

    </form>

    <script>
        const RAW_CUSTOMERS = @json($customers);
        const OLD_ITEMS = @json([]);
        
        function customerSelect() {
            return {
                open: false,
                search: '',
                selectedId: '{{ old("customer_id") }}',
                customers: RAW_CUSTOMERS,
                
                get filteredCustomers() {
                    if (this.search === '') return this.customers;
                    const query = this.search.toLowerCase();
                    return this.customers.filter(c => 
                        (c.company_name && c.company_name.toLowerCase().includes(query)) ||
                        (c.company_code && c.company_code.toLowerCase().includes(query)) ||
                        (c.address && c.address.toLowerCase().includes(query))
                    );
                },
                
                get selectedLabel() {
                    const found = this.customers.find(c => c.id == this.selectedId);
                    if(found) return found.company_name + ' (' + found.company_code + ')';
                    return null;
                },
                
                selectCustomer(c) {
                    this.selectedId = c.id;
                    this.open = false;
                    this.search = '';
                    
                    setTimeout(() => {
                        document.querySelector('input[name="customer_id"]').dispatchEvent(new Event('change'));
                    }, 50);
                },

                init() {
                    if (this.selectedId) {
                        setTimeout(() => {
                            document.querySelector('input[name="customer_id"]').dispatchEvent(new Event('change'));
                        }, 50);
                    }
                }
            }
        }
        
        function rfqForm() {
            let initialType = '{{ old("type", request("type", "Non Projek")) }}';
            let nonProj = [];
            let catItems = {
                'Hardware': [],
                'Jasa Pemasangan': [],
                'Material Support': []
            };

            let globId = 1;

            // Initialize default items based on type
            if (initialType === 'Projek') {
                catItems['Hardware'].push({
                    id: globId++,
                    product_name: '',
                    qty: 1,
                    unit: '',
                    detail_item: '',
                    description: '',
                    hpp: '',
                    ongkir_pedia: '',
                    ongkir_pelanggan: '',
                    margin: 25,
                    ceiling: '10000',
                    validity_days: 7
                });
            } else {
                nonProj.push({ id: globId++, product_name: '', qty: 1, unit: '', description: '' });
            }

            return {
                type: initialType,
                globalId: globId,
                
                nonProjekItems: nonProj,
                
                categories: [
                    { name: 'Hardware', label: 'I. Hardware', items: catItems['Hardware'] },
                    { name: 'Jasa Pemasangan', label: 'II. Jasa Pemasangan', items: catItems['Jasa Pemasangan'] },
                    { name: 'Material Support', label: 'III. Material Support', items: catItems['Material Support'] }
                ],
                
                init() {
                    this.$watch('type', val => {
                        if (val === 'Projek') {
                            const total = this.categories.reduce((acc, c) => acc + c.items.length, 0);
                            if (total === 0) {
                                this.addProjekItem('Hardware');
                            }
                        }
                    });
                },
                
                addNonProjekItem() {
                    this.globalId++;
                    this.nonProjekItems.push({
                        id: this.globalId, product_name: '', qty: 1, unit: '', description: ''
                    });
                },
                
                removeNonProjekItem(id) {
                    if (this.nonProjekItems.length > 1) {
                        this.nonProjekItems = this.nonProjekItems.filter(i => i.id !== id);
                    }
                },
                
                addProjekItem(catName) {
                    this.globalId++;
                    const cat = this.categories.find(c => c.name === catName);
                    if(cat) {
                        cat.items.push({
                            id: this.globalId,
                            product_name: '',
                            qty: 1,
                            unit: '',
                            detail_item: '',
                            description: '',
                            hpp: '',
                            ongkir_pedia: '',
                            ongkir_pelanggan: '',
                            margin: 25,
                            ceiling: '10000',
                            validity_days: 7
                        });
                    }
                },
                
                removeProjekItem(catName, itemId) {
                    const cat = this.categories.find(c => c.name === catName);
                    if(cat) {
                        cat.items = cat.items.filter(i => i.id !== itemId);
                    }
                },

                itemNumber(category, index) {
                    let offset = 0;
                    for (const c of this.categories) {
                        if (c === category) break;
                        offset += c.items.length;
                    }
                    return offset + index + 1;
                },
                
                totalItems() {
                    if (this.type === 'Non Projek') {
                        return this.nonProjekItems.length;
                    } else {
                        return this.categories.reduce((total, cat) => total + cat.items.length, 0);
                    }
                },

                init() {
                    // Watch perubahan tipe RFQ untuk mencegah data bocor lintas container.
                    // Masalah: x-show hanya menyembunyikan elemen via CSS (display:none),
                    // elemen tetap ada di DOM dan input-nya ikut ter-submit oleh FormData.
                    // Solusi: saat type berubah, kosongkan items dari container yang tidak aktif.
                    this.$watch('type', (newType) => {
                        if (newType === 'Non Projek') {
                            // Masuk ke Non Projek → kosongkan semua item Projek
                            this.categories.forEach(cat => { cat.items = []; });
                            // Pastikan minimal 1 baris Non Projek tersedia
                            if (this.nonProjekItems.length === 0) {
                                this.addNonProjekItem();
                            }
                        } else {
                            // Masuk ke Projek → kosongkan items Non Projek agar tidak bocor
                            this.nonProjekItems = [];
                        }
                    });
                }
            }
        }
        
        function attachRfqDebug() {
            const form = document.getElementById('rfqForm');
            if (!form) return;

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                console.group('RFQ Form Debug');
                console.log('submit event triggered');
                console.log('customer_id:', form.querySelector('[name="customer_id"]')?.value);
                console.log('customer_contact_id:', form.querySelector('[name="customer_contact_id"]')?.value);
                console.log('type:', form.querySelector('[name="type"]:checked')?.value);
                console.groupEnd();

                const itemError = validateItems();
                if (itemError) {
                    showFormError(itemError);
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.6';
                }

                const rawEntries = [...new FormData(form).entries()];
                const cleanForm = new FormData();
                const rowsWithName = new Set();

                for (const [key, value] of rawEntries) {
                    const m = key.match(/^items\[(\d+)\]\[product_name\]$/);
                    if (m && String(value).trim()) rowsWithName.add(m[1]);
                }

                for (const [key, value] of rawEntries) {
                    const m = key.match(/^items\[(\d+)\]\[/);
                    if (m && !rowsWithName.has(m[1])) continue;
                    cleanForm.append(key, value);
                }

                if (rowsWithName.size === 0) {
                    showFormError('Minimal satu baris item wajib diisi (Nama Item + Qty).');
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '';
                    }
                    return;
                }

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: cleanForm,
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    let data = null;
                    const contentType = response.headers.get('content-type') || '';
                    if (contentType.includes('application/json')) {
                        data = await response.json().catch(() => null);
                    }

                    if (!response.ok) {
                        let message = 'Server menolak permintaan (HTTP ' + response.status + ')';
                        if (response.status === 419) {
                            message = 'Sesi login berakhir (419). Muat ulang halaman lalu coba lagi.';
                        } else if (data && data.errors) {
                            const firstField = Object.keys(data.errors)[0];
                            message = data.errors[firstField] ? data.errors[firstField].join(', ') : message;
                        } else if (data && data.message) {
                            message = data.message;
                        }
                        throw new Error(message);
                    }

                    if (!data || !data.success) {
                        throw new Error('Respons server tidak valid.');
                    }

                    window.location.href = data.redirect || '/rfqs';
                } catch (error) {
                    console.error('Detail Error Simpan:', error);
                    alert('Gagal menyimpan data: ' + error.message);
                    showFormError('Gagal menyimpan data: ' + error.message);
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '';
                    }
                }
            });

            form.addEventListener('invalid', function (event) {
                if (!form._invalidShown) {
                    form._invalidShown = true;
                    showFormError('Form belum lengkap. Periksa kolom: ' + fieldLabel(event.target.name));
                }
                console.warn('RFQ form invalid field:', event.target.name, event.target.validationMessage, event.target);
            }, true);

            form.addEventListener('input', () => { form._invalidShown = false; }, true);
            form.addEventListener('change', () => { form._invalidShown = false; }, true);
        }

        function fieldLabel(name) {
            if (!name) return 'Form';
            if (name.startsWith('items')) {
                const field = name.split('][').pop().replace(']', '');
                const map = {
                    product_name: 'Nama Item',
                    qty: 'Qty',
                    unit: 'Satuan',
                    detail_item: 'Detail / Spesifikasi Item',
                    description: 'Deskripsi',
                    category: 'Kategori'
                };
                return map[field] || 'Item Detail';
            }
            const labels = {
                customer_id: 'Customer',
                customer_contact_id: 'PIC Tujuan',
                need_date: 'Tgl. Penawaran',
                priority: 'Prioritas',
                type: 'Tipe RFQ'
            };
            return labels[name] || name;
        }

        function validateItems() {
            const form = document.getElementById('rfqForm');
            if (!form) return null;
            const state = (window.Alpine && Alpine.$data) ? Alpine.$data(form) : (form._x_dataStack && form._x_dataStack[0]);
            if (!state) return null;

            const error = { message: '', name: '' };

            if (state.type === 'Non Projek') {
                state.nonProjekItems.forEach(item => {
                    if (error.name) return;
                    if (!item.product_name || !String(item.product_name).trim()) {
                        error.message = 'Nama Item wajib diisi!';
                        error.name = `items[${item.id}][product_name]`;
                    } else if (!(parseFloat(item.qty) > 0)) {
                        error.message = 'Qty wajib diisi!';
                        error.name = `items[${item.id}][qty]`;
                    }
                });
            } else {
                state.categories.forEach(cat => {
                    if (error.name) return;
                    if (cat.items && cat.items.length > 0) {
                        cat.items.forEach(item => {
                            if (error.name) return;
                            if (!item.product_name || !String(item.product_name).trim()) {
                                error.message = `Nama Item pada ${cat.name} wajib diisi!`;
                                error.name = `items[${item.id}][product_name]`;
                            } else if (!(parseFloat(item.qty) > 0)) {
                                error.message = `Qty pada ${cat.name} wajib diisi!`;
                                error.name = `items[${item.id}][qty]`;
                            }
                        });
                    }
                });
            }

            if (error.name) {
                const field = form.querySelector(`[name="${error.name}"]`);
                if (field) field.focus();
                return error.message;
            }
            return null;
        }

        function showFormError(message) {
            let toast = document.getElementById('rfq-form-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'rfq-form-toast';
                toast.style.cssText = 'position:fixed;top:1rem;left:50%;transform:translateX(-50%);z-index:9999;background:#fff1f2;border:1px solid #fecdd3;color:#be123c;padding:0.75rem 1.25rem;border-radius:0.75rem;font-size:0.85rem;font-weight:600;box-shadow:0 12px 32px rgba(15,23,42,0.18);max-width:90%;text-align:center;transition:opacity .3s;';
                document.body.appendChild(toast);
            }
            toast.textContent = message;
            toast.style.opacity = '1';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => { toast.style.opacity = '0'; }, 4000);
        }

        document.addEventListener('input', function (e) {
            if (e.target && e.target.matches && e.target.matches('textarea.auto-grow') && !(window.CSS && CSS.supports('field-sizing', 'content'))) {
                e.target.style.height = 'auto';
                e.target.style.height = e.target.scrollHeight + 'px';
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            attachRfqDebug();
            const customerInput = document.querySelector('input[name="customer_id"]');
            if (customerInput) {
                customerInput.addEventListener('change', function () {
                    const customerId = this.value;
                    const detailBox = document.getElementById('customer-detail-box');
                    const contactSelect = document.getElementById('contact-select');
                    
                    if (!customerId) {
                        detailBox.innerHTML = '';
                        contactSelect.innerHTML = '<option value="">Pilih PIC (Pilih Customer Dulu)</option>';
                        return;
                    }

                    fetch('/rfqs/customer/' + customerId)
                        .then(response => response.json())
                        .then(data => {
                            detailBox.innerHTML = `
                                <div class="p-3 rounded-xl text-sm" style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.12);">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-semibold" style="color: var(--text-primary);">${data.company_name || '-'}</span>
                                        <code class="text-[10px] px-1.5 py-0.5 rounded" style="background: rgba(37,99,235,0.08); color: var(--accent-blue);">${data.company_code || '-'}</code>
                                    </div>
                                    <p class="text-xs" style="color: var(--text-muted);">${data.address || ''}</p>
                                </div>
                            `;

                            contactSelect.innerHTML = '<option value="">Pilih PIC Tujuan</option>';
                            if(data.contacts && data.contacts.length > 0) {
                                data.contacts.forEach(contact => {
                                    const option = document.createElement('option');
                                    option.value = contact.id;
                                    option.text = contact.name + ' (' + (contact.position || '-') + ')';
                                    if(contact.is_primary) option.selected = true;
                                    contactSelect.appendChild(option);
                                });
                            } else {
                                contactSelect.innerHTML = '<option value="">Tidak ada PIC (Silakan tambah di menu Customer)</option>';
                            }
                        })
                        .catch(() => {
                            detailBox.innerHTML = '<p class="text-xs text-rose-500">Gagal memuat detail customer</p>';
                        });
                });
            }
        });

        function openInlinePicModal() {
            const customerId = document.querySelector('input[name="customer_id"]')?.value;
            if (!customerId) {
                alert('Silakan pilih Customer terlebih dahulu sebelum menambahkan PIC!');
                return;
            }
            const modal = document.getElementById('inline-pic-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('inline_pic_name').focus();
        }

        function closeInlinePicModal() {
            const modal = document.getElementById('inline-pic-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('inline-pic-form').reset();
        }

        function submitInlinePic(e) {
            e.preventDefault();
            const customerId = document.querySelector('input[name="customer_id"]')?.value;
            if (!customerId) return;

            const btn = document.getElementById('btn-save-inline-pic');
            btn.disabled = true;
            btn.innerText = 'Menyimpan...';

            const payload = {
                name: document.getElementById('inline_pic_name').value,
                position: document.getElementById('inline_pic_position').value,
                phone: document.getElementById('inline_pic_phone').value,
                email: document.getElementById('inline_pic_email').value,
                _token: '{{ csrf_token() }}'
            };

            fetch(`/api/customers/${customerId}/contacts`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerText = 'Simpan PIC';
                if (data.status === 'success') {
                    const select = document.getElementById('contact-select');
                    const option = document.createElement('option');
                    option.value = data.contact.id;
                    option.text = data.contact.name + (data.contact.position ? ` (${data.contact.position})` : '');
                    option.selected = true;
                    select.appendChild(option);
                    select.value = data.contact.id;
                    select.dispatchEvent(new Event('change'));
                    closeInlinePicModal();
                } else {
                    alert('Gagal menyimpan kontak PIC: ' + (data.message || 'Terjadi kesalahan'));
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerText = 'Simpan PIC';
                alert('Gagal menyimpan kontak PIC');
            });
        }
    </script>

    {{-- Modal Tambah PIC Instan (Blueprint 1.2) --}}
    <div id="inline-pic-modal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    Tambah PIC Instan
                </h3>
                <button type="button" onclick="closeInlinePicModal()" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <form id="inline-pic-form" onsubmit="submitInlinePic(event)" class="space-y-3 text-xs">
                <div>
                    <label class="block font-semibold mb-1 text-slate-700">Nama PIC <span class="text-rose-500">*</span></label>
                    <input type="text" id="inline_pic_name" required placeholder="Contoh: Bpk. Bambang"
                        class="w-full p-2.5 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-xs">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-slate-700">Jabatan / Posisi</label>
                    <input type="text" id="inline_pic_position" placeholder="Contoh: Manager IT"
                        class="w-full p-2.5 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-xs">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-slate-700">No. WhatsApp / HP</label>
                    <input type="text" id="inline_pic_phone" placeholder="0812xxxxxxxx"
                        class="w-full p-2.5 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-xs">
                </div>
                <div>
                    <label class="block font-semibold mb-1 text-slate-700">Email</label>
                    <input type="email" id="inline_pic_email" placeholder="pic@perusahaan.com"
                        class="w-full p-2.5 rounded-xl border border-slate-200 focus:border-blue-500 outline-none text-xs">
                </div>

                <div class="pt-3 flex justify-end gap-2">
                    <button type="button" onclick="closeInlinePicModal()" class="px-4 py-2 rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 font-medium">Batal</button>
                    <button type="submit" id="btn-save-inline-pic" class="px-4 py-2 rounded-xl text-white bg-blue-600 hover:bg-blue-700 font-semibold shadow">Simpan PIC</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .auto-grow {
            field-sizing: content;
            resize: none;
            overflow: hidden;
            min-height: 2.9rem;
        }

        .pmx-grid {
            display: flex;
            flex-direction: column;
            min-width: 780px;
        }

        .pmx-grid--sales .pmx-row--head,
        .pmx-grid--sales .pmx-row--data {
            grid-template-columns: 40px minmax(250px, 3fr) 90px 110px 44px;
        }

        .pmx-row {
            display: grid;
            align-items: start;
            gap: 10px;
        }

        .pmx-cell {
            padding: 9px 12px;
            font-size: 0.8125rem;
            color: var(--text-secondary);
            border-bottom: 1px solid #f1f5f9;
            min-width: 0;
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .pmx-row--data:hover .pmx-cell,
        .pmx-row--data:hover .pmx-cell--band {
            background: rgba(37, 99, 235, 0.03);
        }

        .pmx-row--data:last-child .pmx-cell,
        .pmx-row--data:last-child .pmx-cell--band {
            border-bottom: none;
        }

        .pmx-cell--head {
            padding: 10px 12px;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid #e2e8f0;
        }

        .pmx-cell--num {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-weight: 600;
            font-size: 0.8125rem;
            color: var(--text-muted);
            padding-top: 14px;
        }

        .pmx-cell--desc {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .pmx-cell--center {
            text-align: center;
        }

        .pmx-input {
            width: 100%;
            padding: 0.55rem 0.7rem;
            border-radius: 0.6rem;
            font-size: 0.8125rem;
            outline: none;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: var(--text-primary);
            box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .pmx-input:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.10);
        }

        .pmx-input--name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .pmx-input--spec {
            font-size: 0.85rem;
            color: var(--text-muted);
            word-break: break-word;
            overflow-wrap: break-word;
        }

        .pmx-input--center {
            text-align: center;
        }

        .pmx-del {
            padding: 6px;
            border-radius: 0.5rem;
            color: var(--accent-rose);
            background: rgba(239, 68, 68, 0.08);
            transition: background-color 0.15s ease;
        }

        .pmx-del:hover {
            background: rgba(239, 68, 68, 0.18);
        }

        .pmx-row--group {
            grid-template-columns: 1fr;
        }

        .pmx-row--group .pmx-cell {
            grid-column: 1 / -1;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: #f1f5f9;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .pmx-group-label {
            font-size: 0.6875rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-secondary);
        }

        .pmx-group-count {
            font-size: 0.6875rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .pmx-group-add {
            margin-left: auto;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 0.5rem;
            background: rgba(37, 99, 235, 0.10);
            color: var(--accent-blue);
            transition: background-color 0.15s ease;
        }

        .pmx-group-add:hover {
            background: rgba(37, 99, 235, 0.18);
        }

        .pmx-empty {
            padding: 18px 16px;
            font-size: 0.75rem;
            text-align: center;
            font-style: italic;
            color: var(--text-muted);
            border-bottom: 1px solid #f1f5f9;
        }

        .pmx-cell--band {
            grid-column: 1 / -1;
            padding: 12px 16px;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }

        .pmx-band {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
        }

        .pmx-band-label {
            display: block;
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        @media (max-width: 1023px) {
            .pmx-band {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 767px) {
            .pmx-row--head {
                display: none;
            }

            .pmx-row--data {
                grid-template-columns: 40px minmax(0, 1fr) 80px 100px 44px;
                gap: 6px;
            }

            .pmx-cell {
                padding: 8px 8px;
            }

            .pmx-cell--band {
                grid-column: 1 / -1;
            }

            .pmx-band {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</x-app-layout>