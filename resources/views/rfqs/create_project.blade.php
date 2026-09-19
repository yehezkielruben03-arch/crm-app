<x-app-layout>

    <div class="flex items-center gap-3 mb-6 animate-in" style="animation-delay: 0.05s;">
        <a href="{{ route('rfq.index') }}"
           class="p-2 rounded-lg transition-colors"
           style="color: var(--text-muted);"
           onmouseenter="this.style.background='var(--bg-secondary)'"
           onmouseleave="this.style.background=''">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">Buat RFQ Projek</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">Buat RFQ projek dengan pricing langsung</p>
        </div>
    </div>

    <form method="POST" action="{{ route('rfq.store_project') }}" id="rfqForm">
        @csrf
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- Kolom Kiri --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Card: Informasi RFQ --}}
                <div class="card p-5 md:p-6 relative z-30">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Informasi RFQ
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Customer --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Customer <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <x-custom-select name="customer_id" id="customer-select" :value="old('customer_id')" placeholder="Pilih Customer" min-width="100%" :options="collect(['' => 'Pilih Customer'])->union($customers->mapWithKeys(fn($c) => [$c->id => $c->company_name . ' (' . $c->company_code . ')']))" />
                            @error('customer_id')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- Customer Detail Box & PIC --}}
                        <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2" id="customer-detail-box"></div>
                            <div>
                                <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                    PIC Tujuan <span style="color: var(--accent-rose);">*</span>
                                </label>
                                <select name="customer_contact_id" id="contact-select" required
                                    style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);">
                                    <option value="">Pilih PIC (Pilih Customer Dulu)</option>
                                </select>
                                @error('customer_contact_id')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        {{-- Tanggal Dibutuhkan --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Tgl. Penawaran (Dibutuhkan)</label>
                            <input type="date" name="need_date" value="{{ old('need_date') }}" required
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary);"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">
                        </div>

                        {{-- Tipe --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Tipe RFQ <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <input type="text" value="Projek" disabled
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-muted); opacity: 0.7;">
                            <input type="hidden" name="type" value="Projek">
                            <p class="text-[10px] mt-1" style="color: var(--text-muted);">RFQ Projek sudah ditetapkan sebagai tipe Projek.</p>
                        </div>

                        {{-- Catatan --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Catatan</label>
                            <textarea name="notes" rows="3" placeholder="Catatan atau keterangan tambahan..."
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); resize: vertical; min-height: 80px;"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Card: Detail Item --}}
                <div class="card p-5 md:p-6 animate-in" style="animation-delay: 0.15s;">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <div>
                            <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Detail Item</h2>
                            <p class="text-xs mt-0.5" style="color: var(--text-muted);">Isi detail produk/jasa beserta harga dan margin</p>
                        </div>
                        <button type="button" onclick="addItem()"
                            class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg transition-all active:scale-95"
                            style="background: rgba(37,99,235,0.1); color: var(--accent-blue);"
                            onmouseenter="this.style.background='rgba(37,99,235,0.18)'"
                            onmouseleave="this.style.background='rgba(37,99,235,0.1)'">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Item
                        </button>
                    </div>

                    <div id="items-container" class="space-y-3">
                        {{-- Item pertama --}}
                        <div class="item-row p-4 rounded-xl" style="background: var(--bg-secondary); border: 1px solid rgba(148,163,184,0.24); box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold" style="color: var(--text-muted);">Item #1</span>
                                <button type="button" onclick="removeItem(this)" disabled
                                    class="text-xs font-medium px-2 py-1 rounded-lg transition-all"
                                    style="color: var(--accent-rose); background: rgba(239,68,68,0.08); opacity: 0.35; cursor: not-allowed;">
                                    <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus
                                </button>
                            </div>

                            {{-- Baris 0: Kategori (default Active Equipment agar tidak jatuh ke 'Tanpa Kategori') --}}
                            <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                                <div class="col-span-2 sm:col-span-12">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Kategori <span style="color:var(--accent-rose);">*</span></label>
                                    <select name="items[0][category]"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                        <option value="Active Equipment" selected>I. Active Equipment</option>
                                        <option value="Passive Equipment">II. Passive Equipment</option>
                                        <option value="Consumables">V. Consumables</option>
                                        <option value="Professional Service">VI. Professional Services</option>
                                        <option value="Training Support">VII. Training &amp; Support</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Baris 1: Nama Item, Qty, Satuan, Masa Berlaku --}}
                            <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                                <div class="col-span-2 sm:col-span-5">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Item <span style="color:var(--accent-rose);">*</span></label>
                                    <input type="text" name="items[0][product_name]" required placeholder="Cth: Server Dell"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty <span style="color:var(--accent-rose);">*</span></label>
                                    <input type="number" name="items[0][qty]" required min="1" step="1" value="1"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                                <div class="col-span-1 sm:col-span-2">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Satuan</label>
                                    <input type="text" name="items[0][unit]" placeholder="Unit"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                                <div class="col-span-2 sm:col-span-4">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Masa Berlaku (hari)</label>
                                    <input type="number" name="items[0][validity_days]" required min="1" value="7"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                            </div>

                            {{-- Baris 2: HPP, Ongkir Pedia, Ongkir Pelanggan, Margin --}}
                            <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">HPP (Rp)</label>
                                    <input type="number" name="items[0][hpp]" required min="0" step="0.01" placeholder="0"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Ongkir Pedia (Rp)</label>
                                    <input type="number" name="items[0][ongkir_pedia]" required min="0" step="0.01" placeholder="0"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Ongkir Pelanggan (Rp)</label>
                                    <input type="number" name="items[0][ongkir_pelanggan]" required min="0" step="0.01" placeholder="0"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                                <div class="sm:col-span-3">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Margin (kali)</label>
                                    <input type="number" name="items[0][margin]" required min="1" step="1" value="25"
                                        style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                            </div>

                            {{-- Baris 3: Ceiling --}}
                            <datalist id="ceiling-options">
                                <option value="1">1 - Tanpa Pembulatan</option>
                                <option value="10">10 - Puluhan</option>
                                <option value="100">100 - Ratusan</option>
                                <option value="1000">1.000 - Ribuan</option>
                                <option value="5000">5.000 - Rp 5.000</option>
                                <option value="10000">10.000 - Puluh Ribuan</option>
                                <option value="50000">50000 - Rp 50.000</option>
                                <option value="100000">100.000 - Rp 100.000</option>
                                <option value="500000">500.000 - Rp 500.000</option>
                            </datalist>
                            <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                                <div class="sm:col-span-5">
                                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Ceiling (Pembulatan)</label>
                                    <div class="flex items-center gap-1 mb-1.5">
                                        <button type="button" onclick="document.getElementById('ceiling_0').value = 1" class="px-2 py-0.5 text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300">1 (Bebas)</button>
                                        <button type="button" onclick="document.getElementById('ceiling_0').value = 1000" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 font-semibold">1k (Ribuan)</button>
                                        <button type="button" onclick="document.getElementById('ceiling_0').value = 10000" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 font-semibold">10k (Puluh Rb)</button>
                                        <button type="button" onclick="document.getElementById('ceiling_0').value = 50000" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 font-semibold">50k</button>
                                    </div>
                                    <input type="number" id="ceiling_0" name="items[0][ceiling]" list="ceiling-options" value="1000" min="1" step="1" placeholder="1000"
                                        style="width:100%;padding:0.65rem 0.95rem;border-radius:0.75rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                        onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                                </div>
                            </div>

                            {{-- Baris 4: Deskripsi --}}
                            <div>
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Deskripsi</label>
                                <textarea name="items[0][description]" rows="3" placeholder="Spesifikasi lengkap"
                                    style="width:100%;padding:0.95rem 1rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);resize:vertical;min-height:6.5rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                                    onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan --}}
            <div class="space-y-5">
                <div class="card p-5 md:p-6 animate-in" style="animation-delay: 0.2s;">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Ringkasan
                    </h2>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl" style="background: rgba(37,99,235,0.06); border: 1px solid rgba(37,99,235,0.12);">
                            <span class="text-sm" style="color: var(--text-muted);">Total Item</span>
                            <span class="text-lg font-bold" style="color: var(--accent-blue);" id="display-qty">1</span>
                        </div>
                        <div class="flex items-start gap-2 p-3 rounded-xl" style="background: rgba(245,158,11,0.06); border: 1px solid rgba(245,158,11,0.12);">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-xs" style="color: var(--text-muted);">Harga akan dihitung otomatis berdasarkan HPP + Ongkir + Margin</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-2 animate-in" style="animation-delay: 0.25s;">
                    <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan RFQ
                    </button>
                    <a href="{{ route('rfq.index') }}"
                       class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-medium rounded-xl transition-all active:scale-95"
                       style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);"
                       onmouseenter="this.style.background='var(--bg-primary)'"
                       onmouseleave="this.style.background='var(--bg-secondary)'">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </form>

    <script>
        let itemIndex = 1;

        function addItem() {
            const container = document.getElementById('items-container');
            const idx = itemIndex++;
            const div = document.createElement('div');
            div.className = 'item-row p-4 rounded-xl';
            div.style.cssText = `background: var(--bg-secondary); border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(15,23,42,0.06);`;
            div.innerHTML = `
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold" style="color: var(--text-muted);">Item #${idx + 1}</span>
                    <button type="button" onclick="removeItem(this)"
                        class="text-xs font-medium px-2 py-1 rounded-lg transition-all"
                        style="color: var(--accent-rose); background: rgba(239,68,68,0.08);"
                        onmouseenter="this.style.background='rgba(239,68,68,0.18)'"
                        onmouseleave="this.style.background='rgba(239,68,68,0.08)'">
                        <svg class="w-3.5 h-3.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus
                    </button>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                    <div class="col-span-2 sm:col-span-12">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Kategori <span style="color:var(--accent-rose);">*</span></label>
                        <select name="items[${idx}][category]"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                            <option value="Active Equipment" selected>I. Active Equipment</option>
                            <option value="Passive Equipment">II. Passive Equipment</option>
                            <option value="Consumables">V. Consumables</option>
                            <option value="Professional Service">VI. Professional Services</option>
                            <option value="Training Support">VII. Training &amp; Support</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                    <div class="col-span-2 sm:col-span-5">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Item <span style="color:var(--accent-rose);">*</span></label>
                        <input type="text" name="items[${idx}][product_name]" required placeholder="Cth: Server Dell"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                    <div class="col-span-1 sm:col-span-2">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty <span style="color:var(--accent-rose);">*</span></label>
                        <input type="number" name="items[${idx}][qty]" required min="1" step="1" value="1"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                    <div class="col-span-1 sm:col-span-2">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Satuan</label>
                        <input type="text" name="items[${idx}][unit]" placeholder="Unit"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                    <div class="col-span-2 sm:col-span-4">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Masa Berlaku (hari)</label>
                        <input type="number" name="items[${idx}][validity_days]" required min="1" value="7"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">HPP (Rp)</label>
                        <input type="number" name="items[${idx}][hpp]" required min="0" step="0.01" placeholder="0"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Ongkir Pedia (Rp)</label>
                        <input type="number" name="items[${idx}][ongkir_pedia]" required min="0" step="0.01" placeholder="0"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Ongkir Pelanggan (Rp)</label>
                        <input type="number" name="items[${idx}][ongkir_pelanggan]" required min="0" step="0.01" placeholder="0"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Margin (kali)</label>
                        <input type="number" name="items[${idx}][margin]" required min="1" step="1" value="25"
                            style="width:100%;padding:0.85rem 0.95rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);height:3.4rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-12 gap-3 mb-3">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Ceiling (Pembulatan)</label>
                        <div class="flex items-center gap-1 mb-1.5">
                            <button type="button" onclick="document.getElementById('ceiling_${idx}').value = 1" class="px-2 py-0.5 text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 rounded border border-slate-300">1 (Bebas)</button>
                            <button type="button" onclick="document.getElementById('ceiling_${idx}').value = 1000" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 font-semibold">1k (Ribuan)</button>
                            <button type="button" onclick="document.getElementById('ceiling_${idx}').value = 10000" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 font-semibold">10k (Puluh Rb)</button>
                            <button type="button" onclick="document.getElementById('ceiling_${idx}').value = 50000" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 text-blue-700 rounded border border-blue-200 font-semibold">50k</button>
                        </div>
                        <input type="number" id="ceiling_${idx}" name="items[${idx}][ceiling]" list="ceiling-options" value="1000" min="1" step="1" placeholder="1000"
                            style="width:100%;padding:0.65rem 0.95rem;border-radius:0.75rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Deskripsi</label>
                    <textarea name="items[${idx}][description]" rows="3" placeholder="Spesifikasi lengkap"
                            style="width:100%;padding:0.95rem 1rem;border-radius:1rem;font-size:0.95rem;outline:none;background:var(--bg-primary);border:1px solid rgba(148,163,184,0.24);color:var(--text-primary);resize:vertical;min-height:6.5rem;box-shadow: inset 0 1px 2px rgba(15,23,42,0.04);transition: border-color 0.2s, box-shadow 0.2s;"
                            onfocus="this.style.borderColor='var(--accent-blue)'" onblur="this.style.borderColor='rgba(148,163,184,0.24)'"></textarea>
                </div>
            `;
            container.appendChild(div);
            updateRemoveButtons();
            updateItemCount();
        }

        function removeItem(btn) {
            const row = btn.closest('.item-row');
            row.style.transition = 'all 0.2s';
            row.style.opacity = '0';
            row.style.transform = 'scale(0.95)';
            setTimeout(() => {
                row.remove();
                updateRemoveButtons();
                updateItemCount();
            }, 150);
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            const btns = document.querySelectorAll('.item-row button[onclick="removeItem(this)"]');
            btns.forEach(btn => {
                if (rows.length <= 1) {
                    btn.disabled = true;
                    btn.style.opacity = '0.35';
                    btn.style.cursor = 'not-allowed';
                } else {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                }
            });
        }

        function updateItemCount() {
            const rows = document.querySelectorAll('.item-row');
            document.getElementById('display-qty').innerText = rows.length;
        }

        function loadCustomer(customerId) {
            const detailBox = document.getElementById('customer-detail-box');

            if (!customerId) {
                detailBox.innerHTML = '';
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

                    const contactSelect = document.getElementById('contact-select');
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
        }

        document.addEventListener('DOMContentLoaded', function () {
            const customerInput = document.querySelector('input[name="customer_id"]');
            if (customerInput) {
                customerInput.addEventListener('change', function () {
                    loadCustomer(this.value);
                });
            }
        });
    </script>
</x-app-layout>