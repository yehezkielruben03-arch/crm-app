<x-app-layout>

    <div class="flex items-center gap-3 mb-6 animate-in" style="animation-delay: 0.05s;">
        <a href="{{ route('po.index') }}"
           class="p-2 rounded-lg transition-colors"
           style="color: var(--text-muted);"
           onmouseenter="this.style.background='var(--bg-secondary)'"
           onmouseleave="this.style.background=''">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">Buat Purchase Order</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">Isi detail PO dari klien beserta itemnya</p>
        </div>
    </div>

    <form method="POST" action="{{ route('po.store') }}" id="poForm" enctype="multipart/form-data">
        @csrf
        @if($rfq)
        <input type="hidden" name="rfq_id" value="{{ $rfq->id }}">
        @endif
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

            {{-- Kolom Kiri --}}
            <div class="lg:col-span-2 space-y-5">
                {{-- Upload PDF --}}
                <div class="card p-6 animate-in" style="animation-delay: 0.1s;">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Upload PO (PDF)
                    </h2>
                    <div id="po-dropzone"
                        class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors"
                        style="border-color: var(--border-color); background: var(--bg-secondary);"
                        ondragover="event.preventDefault(); this.style.borderColor='var(--accent-blue)'; this.style.background='rgba(37,99,235,0.05)';"
                        ondragleave="this.style.borderColor='var(--border-color)'; this.style.background='var(--bg-secondary)';"
                        ondrop="event.preventDefault(); handlePdfDrop(event.dataTransfer.files[0])"
                        onclick="document.getElementById('pdfInput').click()">
                        <svg class="w-10 h-10 mx-auto mb-3" style="color: var(--text-muted);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm font-medium" style="color: var(--text-muted);">
                            <span style="color: var(--accent-blue);">Klik untuk upload</span> atau drag & drop PDF
                        </p>
                        <p class="text-xs mt-1" style="color: var(--text-muted);">Hasil parse akan mengisi item otomatis</p>
                        <div id="po-filename" class="mt-3 text-xs hidden" style="color: var(--accent-green);"></div>
                        <div id="po-parsing" class="mt-3 text-xs hidden" style="color: var(--text-muted);">
                            <span class="animate-pulse">Memproses PDF...</span>
                        </div>
                    </div>
                    <input type="file" id="pdfInput" name="file" accept=".pdf" class="hidden" onchange="handlePdfDrop(this.files[0])">
                </div>

                <div class="card p-6 relative z-30">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">
                        Informasi Umum
                    </h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        {{-- Customer --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Customer <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <x-custom-select name="customer_id" :value="old('customer_id', $rfq?->customer_id)" placeholder="Pilih Customer" min-width="100%" :options="collect(['' => 'Pilih Customer'])->union($customers->mapWithKeys(fn($c) => [$c->id => $c->company_name . ' (' . $c->company_code . ')']))" />
                            @error('customer_id')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- Tanggal PO --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">
                                Tanggal PO <span style="color: var(--accent-rose);">*</span>
                            </label>
                            <input type="text" class="datepicker form-input" name="po_date" value="{{ old('po_date', date('Y-m-d')) }}" required
                                >
                            @error('po_date')<p class="mt-1 text-xs" style="color: var(--accent-rose);">{{ $message }}</p>@enderror
                        </div>

                        {{-- Due Date --}}
                        <div>
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Due Date Pembayaran</label>
                            <input type="text" class="datepicker form-input" name="due_date" value="{{ old('due_date') }}"
                                >
                        </div>

                        {{-- Catatan --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold mb-1.5" style="color: var(--text-secondary);">Catatan</label>
                            <textarea name="notes" rows="3" placeholder="Catatan atau keterangan tambahan..."
                                style="width: 100%; padding: 0.6rem 0.75rem; border-radius: 0.75rem; font-size: 0.875rem; outline: none; background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-primary); resize: none;"
                                onfocus="this.style.borderColor='var(--accent-blue)'"
                                onblur="this.style.borderColor='var(--border-color)'">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                @if($rfq)
                <div class="card p-5" style="border-left: 3px solid var(--accent-blue);">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 mt-0.5 shrink-0" style="color: var(--accent-blue);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold" style="color: var(--text-primary);">Konversi dari RFQ</p>
                            <p class="text-xs mt-1" style="color: var(--text-muted);">
                                PO ini dibuat berdasarkan <strong>{{ $rfq->rfq_number }}</strong> — <strong>{{ $rfq->customer_name }}</strong>.
                                Silakan isi harga satuan untuk setiap item.
                            </p>
                            <div class="mt-3 text-xs space-y-1">
                                <p class="font-semibold" style="color: var(--text-secondary);">Item dari RFQ:</p>
                                <ul class="list-disc list-inside space-y-0.5" style="color: var(--text-muted);">
                                    @foreach($rfq->items as $item)
                                    <li>{{ $item->product_name }} — {{ $item->qty }} {{ $item->unit }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Detail Item --}}
                <div class="card p-6 animate-in" style="animation-delay: 0.15s;">
                    <div class="flex justify-between items-center mb-4 pb-3" style="border-bottom: 1px solid var(--border-color);">
                        <h2 class="text-sm font-semibold" style="color: var(--text-secondary);">Detail Item</h2>
                        <button type="button" onclick="addItem()" class="text-xs font-medium px-3 py-1.5 rounded-lg" style="background: rgba(37,99,235,0.1); color: var(--accent-blue);">+ Tambah Item</button>
                    </div>

                    <div id="items-container" class="space-y-4">
                        <!-- Item Row 1 -->
                        <div class="item-row grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                            <div class="sm:col-span-5">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Item</label>
                                <input type="text" name="items[0][item_name]" required placeholder="Cth: Laptop Dell XPS"
                                    class="item-name form-input">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty</label>
                                <input type="number" name="items[0][quantity]" required min="1" value="1" oninput="calculateTotal()"
                                    class="item-qty form-input">
                            </div>
                            <div class="sm:col-span-4">
                                <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Harga Satuan (Rp)</label>
                                <input type="number" name="items[0][unit_price]" required min="0" step="1000" oninput="calculateTotal()" placeholder="0"
                                    class="item-price form-input">
                            </div>
                            <div class="sm:col-span-1 text-right">
                                <button type="button" onclick="removeItem(this)" class="text-rose-500 hover:text-rose-700 p-2" disabled>
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan --}}
            <div class="space-y-5">
                <div class="card p-6 animate-in" style="animation-delay: 0.2s;">
                    <h2 class="text-sm font-semibold mb-4 pb-3" style="color: var(--text-secondary); border-bottom: 1px solid var(--border-color);">Ringkasan Biaya</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--text-muted);">Subtotal</span>
                            <span class="font-medium" style="color: var(--text-primary);" id="display-subtotal">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span style="color: var(--text-muted);">PPN (11%)</span>
                            <span class="font-medium" style="color: var(--text-primary);" id="display-tax">Rp 0</span>
                        </div>
                        <div class="flex justify-between text-base font-bold pt-3" style="border-top: 1px solid var(--border-color);">
                            <span style="color: var(--text-primary);">Total Akhir</span>
                            <span style="color: var(--accent-blue);" id="display-total">Rp 0</span>
                        </div>
                        
                        <div class="pt-4 text-xs leading-relaxed" style="border-top: 1px solid var(--border-color); color: var(--text-muted);">
                            Status PO akan otomatis diset sebagai <strong style="color: var(--text-primary);">Pending</strong>.
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
                        Simpan PO
                    </button>
                    <a href="{{ route('po.index') }}"
                       class="w-full flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-xl transition-colors"
                       style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                        Batal
                    </a>
                </div>
            </div>
        </div>
    </form>

    <script>
        let itemIndex = 1;

        function handlePdfDrop(file) {
            if (!file) return;
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'pdf') {
                alert('Hanya file PDF yang diizinkan.');
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert('File maksimal 10MB.');
                return;
            }

            document.getElementById('po-filename').classList.add('hidden');
            document.getElementById('po-parsing').classList.remove('hidden');

            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("po.parse-pdf") }}', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                document.getElementById('po-parsing').classList.add('hidden');
                if (data.success && data.items.length > 0) {
                    document.getElementById('po-filename').textContent = '✓ ' + file.name;
                    document.getElementById('po-filename').classList.remove('hidden');
                    populateItems(data.items);
                } else {
                    document.getElementById('po-filename').textContent = '⚠ PDF terupload (item tidak terdeteksi, isi manual)';
                    document.getElementById('po-filename').classList.remove('hidden');
                }
            })
            .catch(() => {
                document.getElementById('po-parsing').classList.add('hidden');
                alert('Gagal memproses PDF. Silakan isi item manual.');
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
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Item</label>
                        <input type="text" name="items[${i}][item_name]" required placeholder="Cth: Laptop Dell XPS"
                            class="item-name form-input" value="${escapeHtml(item.item_name)}">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty</label>
                        <input type="number" name="items[${i}][quantity]" required min="1" value="${item.quantity}" oninput="calculateTotal()"
                            class="item-qty form-input">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Harga Satuan (Rp)</label>
                        <input type="number" name="items[${i}][unit_price]" required min="0" step="1000" oninput="calculateTotal()" placeholder="0"
                            class="item-price form-input" value="${item.unit_price || 0}">
                    </div>
                    <div class="sm:col-span-1 text-right">
                        <button type="button" onclick="removeItem(this)" class="text-rose-500 hover:text-rose-700 p-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                `;
                container.appendChild(row);
                itemIndex = i + 1;
            });
            calculateTotal();
            updateRemoveButtons();
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function addItem() {
            const container = document.getElementById('items-container');
            const row = document.createElement('div');
            row.className = 'item-row grid grid-cols-1 sm:grid-cols-12 gap-3 items-end pt-3';
            row.style.borderTop = '1px dashed var(--border-color)';
            row.innerHTML = `
                <div class="sm:col-span-5">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Nama Item</label>
                    <input type="text" name="items[${itemIndex}][item_name]" required placeholder="Cth: Laptop Dell XPS"
                        class="item-name form-input">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Qty</label>
                    <input type="number" name="items[${itemIndex}][quantity]" required min="1" value="1" oninput="calculateTotal()"
                        class="item-qty form-input">
                </div>
                <div class="sm:col-span-4">
                    <label class="block text-xs font-semibold mb-1" style="color: var(--text-secondary);">Harga Satuan (Rp)</label>
                    <input type="number" name="items[${itemIndex}][unit_price]" required min="0" step="1000" oninput="calculateTotal()" placeholder="0"
                        class="item-price form-input">
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
        }

        function removeItem(btn) {
            btn.closest('.item-row').remove();
            calculateTotal();
            updateRemoveButtons();
        }

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            const btns = document.querySelectorAll('.item-row button[onclick="removeItem(this)"]');
            if (rows.length === 1) {
                btns[0].disabled = true;
                btns[0].style.opacity = '0.3';
                btns[0].style.cursor = 'not-allowed';
            } else {
                btns.forEach(btn => {
                    btn.disabled = false;
                    btn.style.opacity = '1';
                    btn.style.cursor = 'pointer';
                });
            }
        }

        function calculateTotal() {
            let subtotal = 0;
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
                const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
                const price = parseFloat(row.querySelector('.item-price').value) || 0;
                subtotal += (qty * price);
            });

            const tax = subtotal * 0.11;
            const total = subtotal + tax;

            document.getElementById('display-subtotal').innerText = formatRupiah(subtotal);
            document.getElementById('display-tax').innerText = formatRupiah(tax);
            document.getElementById('display-total').innerText = formatRupiah(total);
        }

        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(number);
        }
    </script>
</x-app-layout>
