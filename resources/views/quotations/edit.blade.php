<x-app-layout>

    <div class="flex items-center gap-3 mb-6 animate-in" style="animation-delay: 0.05s;">
        <a href="{{ route('quo.show', $quotation) }}"
           class="p-2 rounded-lg transition-colors"
           style="color: var(--text-muted);"
           onmouseenter="this.style.background='var(--bg-secondary)'"
           onmouseleave="this.style.background=''">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">Edit Quotation</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">{{ $quotation->quo_number }} @if($quotation->revision_number > 0) — Revisi #{{ $quotation->revision_number }} @endif</p>
        </div>
    </div>

    <form method="POST" action="{{ route('quo.update', $quotation) }}" id="quoForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- Kolom Kiri --}}
            <div class="lg:col-span-2 space-y-5">
                <div class="card p-6 relative z-30">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Informasi Quotation
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Customer <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <x-custom-select name="customer_id" id="customer-select" :value="old('customer_id', $quotation->customer_id)" placeholder="Pilih Customer" min-width="100%" :options="collect(['' => 'Pilih Customer'])->union($customers->mapWithKeys(fn($c) => [$c->id => $c->company_name . ' (' . $c->company_code . ')']))" />
                            @error('customer_id')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2" id="customer-detail-box"></div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Tujuan Sales Marketing <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <x-custom-select name="sales_id" :value="old('sales_id', $quotation->sales_id)" placeholder="Pilih Sales Marketing" min-width="100%" :options="collect(['' => 'Pilih Sales Marketing'])->union($salesList->mapWithKeys(fn($u) => [$u->id => $u->name]))" />
                            @error('sales_id')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Catatan</label>
                            <textarea name="notes" rows="3" placeholder="Catatan atau keterangan tambahan..."
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); resize: none;"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">{{ old('notes', $quotation->notes) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Upload PDF --}}
                <div class="card p-6 animate-in" style="animation-delay: 0.12s;">
                    <div class="flex items-center justify-between mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Upload PDF</h2>
                        <span class="text-[10px] px-2 py-0.5 rounded-full" style="background: rgba(37,99,235,0.08); color: var(--accent-blue);">Opsional</span>
                    </div>

                    @if($quotation->file_path)
                    <div class="flex items-center justify-between p-3 mb-3 rounded-xl" style="background: rgba(239,68,68,0.06); border: 1px solid rgba(239,68,68,0.15);">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #dc2626;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span class="text-sm" style="color: var(--text-secondary);">File saat ini: {{ basename($quotation->file_path) }}</span>
                        </div>
                        <a href="{{ route('quo.download-pdf', $quotation) }}" target="_blank" class="text-xs font-medium" style="color: var(--accent-blue);">Download</a>
                    </div>
                    @endif

                    <div class="border-2 border-dashed rounded-xl p-6 text-center transition-colors"
                         style="border-color: var(--border-color); background: var(--bg-secondary);"
                         id="dropzone"
                         ondragover="event.preventDefault(); this.style.borderColor='var(--accent-blue)'; this.style.background='rgba(37,99,235,0.05)'"
                         ondragleave="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-secondary)'"
                         ondrop="event.preventDefault(); handleFile(event.dataTransfer.files[0])">
                        <input type="file" name="file" id="pdfFile" accept=".pdf" class="hidden" onchange="handleFile(this.files[0])">
                        <svg class="w-10 h-10 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--text-muted);">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm font-medium" style="color: var(--text-secondary);" id="dropzone-text">
                            {{ $quotation->file_path ? 'Klik atau tarik untuk ganti file PDF' : 'Klik atau tarik file PDF ke sini' }}
                        </p>
                        <p class="text-xs mt-1" style="color: var(--text-muted);">Max 5 MB. Upload ulang hanya jika ingin mengganti file lama.</p>
                    </div>
                    <div id="parse-status" class="hidden mt-3"></div>
                    <div id="file-info" class="hidden mt-3 flex items-center justify-between p-3 rounded-xl" style="background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.2);">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--accent-green, #10b981);">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-sm font-medium" style="color: var(--text-primary);" id="file-name"></span>
                        </div>
                        <button type="button" onclick="removeFile()" class="text-xs px-2 py-1 rounded-lg" style="color: var(--accent-rose);">Hapus</button>
                    </div>
                </div>

                {{-- Detail Item --}}
                <div class="card p-6 animate-in" style="animation-delay: 0.15s;">
                    <div class="flex justify-between items-center mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Detail Item</h2>
                        <button type="button" onclick="addItem()" class="text-xs font-medium px-3 py-1.5 rounded-lg" style="background: rgba(37,99,235,0.1); color: var(--accent-blue);">+ Tambah Item</button>
                    </div>

                    <div id="items-container" class="space-y-4">
                        @foreach($quotation->items as $idx => $item)
                        <div class="item-row grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                            <div class="sm:col-span-4">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Produk</label>
                                <input type="text" name="items[{{ $idx }}][product_name]" required value="{{ $item->product_name }}" placeholder="Cth: Jasa Konsultasi" class="item-name form-input">
                            </div>
                            <div class="sm:col-span-1">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty</label>
                                <input type="number" name="items[{{ $idx }}][qty]" required min="0.01" step="0.01" value="{{ $item->qty }}" class="item-qty form-input" oninput="calcRow(this)">
                            </div>
                            <div class="sm:col-span-1">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Satuan</label>
                                <input type="text" name="items[{{ $idx }}][unit]" value="{{ $item->unit }}" placeholder="Pcs" class="item-unit form-input">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Harga Satuan</label>
                                <input type="number" name="items[{{ $idx }}][unit_price]" required min="0" step="1" value="{{ $item->unit_price }}" class="item-price form-input" oninput="calcRow(this)">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Total</label>
                                <input type="text" readonly class="item-total form-input" value="{{ number_format($item->total_price, 0, ',', '.') }}" style="background: transparent; border-color: transparent; font-weight: 600;">
                            </div>
                            <div class="sm:col-span-1 text-right">
                                <button type="button" onclick="removeItem(this)" class="text-rose-500 hover:text-rose-700 p-2" @if($loop->first) disabled @endif>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="mt-4 pt-3 text-right" style="border-top: 1px solid var(--border-color);">
                        <span class="text-sm" style="color: var(--text-muted);">Grand Total: </span>
                        <span class="text-lg font-bold" style="color: var(--text-primary);" id="grand-total">Rp {{ number_format($quotation->items->sum('total_price'), 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan --}}
            <div class="space-y-5">
                <div class="card p-6 animate-in" style="animation-delay: 0.2s;">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">Ringkasan</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--text-muted);">Total Item</span>
                            <span class="font-medium" style="color: var(--text-primary);" id="display-qty">{{ $quotation->items->count() }}</span>
                        </div>
                        <div class="pt-3" style="border-top: 1px solid var(--border-color);">
                            <p class="text-xs" style="color: var(--text-muted);">Upload ulang PDF untuk mengganti file.</p>
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
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('quo.show', $quotation) }}"
                       class="w-full flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-xl transition-colors"
                       style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </form>

    <script>
        let itemIndex = {{ $quotation->items->count() }};

        function handleFile(file) {
            if (!file) return;
            if (file.type !== 'application/pdf') {
                alert('Hanya file PDF yang diizinkan.');
                return;
            }
            document.getElementById('file-name').textContent = file.name;
            document.getElementById('file-info').classList.remove('hidden');
            document.getElementById('dropzone-text').textContent = 'Memproses file...';
            document.getElementById('dropzone-text').style.color = 'var(--accent-amber)';
            document.getElementById('parse-status').classList.remove('hidden');
            document.getElementById('parse-status').innerHTML = `
                <div class="flex items-center gap-2 text-sm" style="color: var(--accent-amber);">
                    <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Membaca isi PDF...
                </div>
            `;

            const formData = new FormData();
            formData.append('file', file);

            fetch('{{ route("quo.parse-pdf") }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: formData
            })
            .then(res => res.json().then(data => ({ ok: res.ok, data })))
            .then(({ ok, data }) => {
                if (!ok) {
                    throw new Error(data.error || 'Gagal membaca PDF');
                }
                if (!data.items || data.items.length === 0) {
                    document.getElementById('parse-status').innerHTML = `
                        <div class="flex items-center gap-2 text-sm" style="color: var(--accent-rose);">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Tidak ada item yang terdeteksi di PDF. Isi manual.
                        </div>
                    `;
                    document.getElementById('dropzone-text').textContent = 'File siap (item tidak terdeteksi)';
                    document.getElementById('dropzone-text').style.color = 'var(--accent-rose)';
                    return;
                }
                document.getElementById('parse-status').innerHTML = `
                    <div class="flex items-center gap-2 text-sm" style="color: var(--accent-green, #10b981);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ${data.items.length} item berhasil dibaca dari PDF
                    </div>
                `;
                document.getElementById('dropzone-text').textContent = 'File siap — item sudah terisi';
                document.getElementById('dropzone-text').style.color = 'var(--accent-green, #10b981)';
                populateItems(data.items);
            })
            .catch(err => {
                document.getElementById('parse-status').innerHTML = `
                    <div class="flex items-center gap-2 text-sm" style="color: var(--accent-rose);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ${err.message}
                    </div>
                `;
                document.getElementById('dropzone-text').textContent = 'File siap (gagal parse)';
                document.getElementById('dropzone-text').style.color = 'var(--accent-rose)';
            });
        }

        function populateItems(items) {
            const container = document.getElementById('items-container');
            container.innerHTML = '';
            itemIndex = 0;

            items.forEach((item, i) => {
                const row = document.createElement('div');
                row.className = 'item-row grid grid-cols-1 sm:grid-cols-12 gap-3 items-end' + (i > 0 ? ' pt-3' : '');
                if (i > 0) row.style.borderTop = '1px dashed var(--border-color)';
                row.innerHTML = `
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Produk</label>
                        <input type="text" name="items[${i}][product_name]" required value="${escapeHtml(item.product_name)}" placeholder="Cth: Jasa Konsultasi" class="item-name form-input">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty</label>
                        <input type="number" name="items[${i}][qty]" required min="0.01" step="0.01" value="${item.qty}" class="item-qty form-input" oninput="calcRow(this)">
                    </div>
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Satuan</label>
                        <input type="text" name="items[${i}][unit]" value="${escapeHtml(item.unit)}" placeholder="Pcs" class="item-unit form-input">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Harga Satuan</label>
                        <input type="number" name="items[${i}][unit_price]" required min="0" step="1" value="${item.unit_price}" class="item-price form-input" oninput="calcRow(this)">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Total</label>
                        <input type="text" readonly class="item-total form-input" value="${(item.qty * item.unit_price).toLocaleString('id-ID')}" style="background: transparent; border-color: transparent; font-weight: 600;">
                    </div>
                    <div class="sm:col-span-1 text-right">
                        <button type="button" onclick="removeItem(this)" class="text-rose-500 hover:text-rose-700 p-2" ${items.length === 1 ? 'disabled' : ''}>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                `;
                container.appendChild(row);
                itemIndex = i + 1;
            });

            updateRemoveButtons();
            calcGrandTotal();
            updateItemCount();
        }

        function escapeHtml(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function removeFile() {
            document.getElementById('pdfFile').value = '';
            document.getElementById('file-info').classList.add('hidden');
            document.getElementById('parse-status').classList.add('hidden');
            document.getElementById('parse-status').innerHTML = '';
            document.getElementById('dropzone-text').textContent = 'Klik atau tarik untuk ganti file PDF';
            document.getElementById('dropzone-text').style.color = '';
        }

        function calcRow(el) {
            const row = el.closest('.item-row');
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const price = parseFloat(row.querySelector('.item-price').value) || 0;
            const total = qty * price;
            row.querySelector('.item-total').value = total > 0 ? total.toLocaleString('id-ID') : '0';
            calcGrandTotal();
            updateItemCount();
        }

        function calcGrandTotal() {
            let grand = 0;
            document.querySelectorAll('.item-total').forEach(el => {
                const val = parseFloat(el.value.replace(/\./g, '')) || 0;
                grand += val;
            });
            document.getElementById('grand-total').textContent = 'Rp ' + grand.toLocaleString('id-ID');
        }

        function addItem() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row grid grid-cols-1 sm:grid-cols-12 gap-3 items-end pt-3';
            row.style.borderTop = '1px dashed var(--border-color)';
            row.innerHTML = `
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Produk</label>
                    <input type="text" name="items[${itemIndex}][product_name]" required placeholder="Cth: Jasa Konsultasi" class="item-name form-input">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty</label>
                    <input type="number" name="items[${itemIndex}][qty]" required min="0.01" step="0.01" value="1" class="item-qty form-input" oninput="calcRow(this)">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Satuan</label>
                    <input type="text" name="items[${itemIndex}][unit]" placeholder="Pcs" class="item-unit form-input">
                </div>
                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Harga Satuan</label>
                    <input type="number" name="items[${itemIndex}][unit_price]" required min="0" step="1" value="0" class="item-price form-input" oninput="calcRow(this)">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Total</label>
                    <input type="text" readonly class="item-total form-input" value="0" style="background: transparent; border-color: transparent; font-weight: 600;">
                </div>
                <div class="sm:col-span-1 text-right">
                    <button type="button" onclick="removeItem(this)" class="text-rose-500 hover:text-rose-700 p-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            `;
            container.appendChild(row);
            itemIndex++;
            updateRemoveButtons();
            updateItemCount();
        }

        function removeItem(btn) {
            btn.closest('.item-row').remove();
            updateRemoveButtons();
            calcGrandTotal();
            updateItemCount();
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            const btns = document.querySelectorAll('.item-row button[onclick="removeItem(this)"]');
            btns.forEach((btn, i) => {
                if (rows.length === 1) {
                    btn.disabled = true;
                    btn.style.opacity = '0.3';
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

        document.addEventListener('DOMContentLoaded', function () {
            const customerSelect = document.querySelector('select[name="customer_id"]');
            if (customerSelect) {
                customerSelect.addEventListener('change', function () {
                    const customerId = this.value;
                    const detailBox = document.getElementById('customer-detail-box');

                    if (!customerId) {
                        detailBox.innerHTML = '';
                        return;
                    }

                    fetch('/rfqs/customer/' + customerId)
                        .then(response => response.json())
                        .then(data => {
                            detailBox.innerHTML = `
                                <div class="p-4 rounded-xl" style="background: var(--bg-secondary); border: 1px solid var(--border-color);">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="font-bold text-sm" style="color: var(--text-primary);">${data.company_name || '-'}</p>
                                        <code class="text-[10px] px-1.5 py-0.5 rounded" style="background: rgba(37,99,235,0.08); color: var(--accent-blue);">${data.company_code || '-'}</code>
                                    </div>
                                    <p class="text-xs" style="color: var(--text-secondary);">${data.address ? data.address + ', ' : ''}${data.city ? data.city : ''}${data.province ? ', ' + data.province : ''}</p>
                                    <div class="mt-2 pt-2 grid grid-cols-2 gap-2" style="border-top: 1px solid var(--border-color);">
                                        <div>
                                            <p class="text-[10px]" style="color: var(--text-muted);">PIC</p>
                                            <p class="text-xs font-medium" style="color: var(--text-primary);">${data.cp_name || '-'}</p>
                                            <p class="text-[10px]" style="color: var(--text-muted);">${data.cp_position || ''}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px]" style="color: var(--text-muted);">Kontak</p>
                                            <p class="text-xs" style="color: var(--text-secondary);">${data.cp_email || '-'}</p>
                                            <p class="text-xs" style="color: var(--text-secondary);">${data.cp_phone || '-'}</p>
                                        </div>
                                    </div>
                                </div>
                            `;
                        })
                        .catch(() => {
                            detailBox.innerHTML = '<p class="text-xs text-rose-500">Gagal memuat detail customer</p>';
                        });
                });

                if (customerSelect.value) {
                    customerSelect.dispatchEvent(new Event('change'));
                }
            }

            document.querySelectorAll('.item-qty, .item-price').forEach(el => calcRow(el));
        });
    </script>

</x-app-layout>
