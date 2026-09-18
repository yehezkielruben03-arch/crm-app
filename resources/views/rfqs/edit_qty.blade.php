<x-app-layout>

    <div class="flex items-center gap-3 mb-6 animate-in" style="animation-delay: 0.05s;">
        <a href="{{ route('rfq.show', $rfq) }}"
           class="p-2 rounded-lg transition-colors"
           style="color: var(--text-muted);"
           onmouseenter="this.style.background='var(--bg-secondary)'"
           onmouseleave="this.style.background=''">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold" style="color: var(--text-primary);">Form Revisi Penawaran ({{ $rfq->rfq_number }})</h1>
            <p class="text-sm mt-0.5" style="color: var(--text-muted);">Revisi komprehensif: nama produk, deskripsi, kuantitas, satuan, tambah/hapus item penawaran (Blueprint 1.3)</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-sm">
            <p class="font-bold mb-1">Terjadi kesalahan input:</p>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('rfq.update_qty', $rfq) }}" id="comprehensiveRevisionForm">
        @csrf
        @method('PUT')

        {{-- Container for Hidden Deleted Items IDs --}}
        <div id="deleted-items-container"></div>

        {{-- Card: Daftar Item Penawaran --}}
        <div class="card p-0 overflow-hidden mb-6 animate-in" style="animation-delay: 0.1s;">
            <div class="p-4 bg-slate-50 border-b flex justify-between items-center" style="border-color: var(--border-color);">
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Daftar Item Penawaran</h2>
                    <p class="text-xs text-slate-500">Anda dapat mengedit data item yang ada atau menambah item baru</p>
                </div>
                <button type="button" onclick="addNewItemRow()" class="flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Item Baru
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm" style="color: var(--text-secondary);">
                    <thead style="background: var(--bg-secondary); color: var(--text-muted); text-transform: uppercase; font-size: 0.7rem; letter-spacing: 0.05em;">
                        <tr>
                            <th class="px-4 py-3 font-semibold w-1/4">Nama Produk / Barang</th>
                            <th class="px-4 py-3 font-semibold w-1/3">Deskripsi / Spesifikasi</th>
                            <th class="px-4 py-3 font-semibold text-center w-28">QTY</th>
                            <th class="px-4 py-3 font-semibold w-28">Satuan</th>
                            <th class="px-4 py-3 font-semibold text-center w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" id="items-table-body" style="border-color: var(--border-color);">
                        @foreach($rfq->items as $idx => $item)
                        <tr class="transition-colors hover:bg-slate-50/50 existing-item-row" id="existing-row-{{ $item->id }}">
                            <input type="hidden" name="items[{{ $idx }}][id]" value="{{ $item->id }}">
                            
                            <td class="px-4 py-3 align-top">
                                <input type="text" name="items[{{ $idx }}][product_name]" value="{{ old('items.'.$idx.'.product_name', $item->product_name) }}" required
                                    class="w-full text-xs font-semibold p-2 rounded-lg"
                                    style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); outline: none;">
                            </td>
                            <td class="px-4 py-3 align-top">
                                <textarea name="items[{{ $idx }}][description]" rows="2"
                                    class="w-full text-xs p-2 rounded-lg resize-none"
                                    style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); outline: none;">{{ old('items.'.$idx.'.description', $item->description) }}</textarea>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input type="number" name="items[{{ $idx }}][qty]" value="{{ old('items.'.$idx.'.qty', (int) $item->qty) }}" required min="0.01" step="any"
                                    class="w-full text-center text-xs p-2 rounded-lg"
                                    style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); outline: none;">
                            </td>
                            <td class="px-4 py-3 align-top">
                                <input type="text" name="items[{{ $idx }}][unit]" value="{{ old('items.'.$idx.'.unit', $item->unit ?? 'Unit') }}" required
                                    class="w-full text-center text-xs p-2 rounded-lg"
                                    style="background: var(--bg-primary); border: 1px solid var(--border-color); color: var(--text-primary); outline: none;">
                            </td>
                            <td class="px-4 py-3 align-top text-center">
                                <button type="button" onclick="markDeleteExisting({{ $item->id }})" title="Hapus item ini"
                                    class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Card: Catatan/Keterangan Revisi (Wajib Sesuai Blueprint 1.3) --}}
        <div class="card p-6 mb-6 animate-in" style="animation-delay: 0.15s;">
            <div class="flex items-center gap-2 mb-2">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                <h3 class="text-sm font-bold text-slate-800">Catatan / Alasan Revisi <span class="text-rose-500">*</span></h3>
            </div>
            <p class="text-xs text-slate-500 mb-3">Dokumentasikan alasan perubahan (contoh: permintaan penambahan unit dari PIC, perubahan spesifikasi, addendum kontrak). Kolom ini wajib diisi.</p>
            <textarea name="revision_notes" rows="3" required placeholder="Contoh: Customer meminta penambahan 1 unit HDMI Extender dan revisi spek switch ke gigabit..."
                class="w-full text-sm p-3 rounded-xl border border-slate-300 focus:border-blue-500 focus:ring focus:ring-blue-100 outline-none transition">{{ old('revision_notes', $rfq->revision_notes) }}</textarea>
            
            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg flex items-start gap-2 text-xs text-blue-800">
                <svg class="w-4 h-4 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><strong>Perhatian:</strong> Setelah form revisi ini diajukan, status RFQ akan <strong>secara otomatis dikembalikan ke Admin Purchase (Pending Admin)</strong> untuk penyesuaian harga HPP, dan notifikasi otomatis akan dikirim ke Admin serta Leader.</span>
            </div>
        </div>

        {{-- Tombol Submit & Batal --}}
        <div class="flex flex-col sm:flex-row gap-3 animate-in" style="animation-delay: 0.2s;">
            <button type="submit"
                class="flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white rounded-xl transition-all active:scale-95 hover:-translate-y-0.5"
                style="background: linear-gradient(135deg, var(--accent-blue), #1d4ed8); box-shadow: 0 4px 14px rgba(37,99,235,0.35);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Ajukan Revisi ke Admin
            </button>
            <a href="{{ route('rfq.show', $rfq) }}"
               class="flex items-center justify-center px-6 py-2.5 text-sm font-medium rounded-xl transition-colors"
               style="background: var(--bg-secondary); border: 1px solid var(--border-color); color: var(--text-secondary);">
                Batal
            </a>
        </div>
    </form>

    <script>
        let newItemIndex = 0;

        function markDeleteExisting(itemId) {
            if (!confirm('Apakah Anda yakin ingin menghapus item ini dari penawaran?')) {
                return;
            }
            
            // Tambahkan hidden input deleted_items[]
            const container = document.getElementById('deleted-items-container');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'deleted_items[]';
            input.value = itemId;
            container.appendChild(input);

            // Sembunyikan baris
            const row = document.getElementById('existing-row-' + itemId);
            if (row) {
                row.remove();
            }
        }

        function addNewItemRow() {
            const tbody = document.getElementById('items-table-body');
            const tr = document.createElement('tr');
            tr.className = 'transition-colors hover:bg-slate-50/50 bg-blue-50/20';
            tr.id = 'new-row-' + newItemIndex;

            tr.innerHTML = `
                <td class="px-4 py-3 align-top">
                    <input type="text" name="new_items[${newItemIndex}][product_name]" placeholder="Nama Produk Baru" required
                        class="w-full text-xs font-semibold p-2 rounded-lg border border-blue-300 focus:border-blue-500 outline-none bg-white">
                </td>
                <td class="px-4 py-3 align-top">
                    <textarea name="new_items[${newItemIndex}][description]" rows="2" placeholder="Deskripsi/Spesifikasi"
                        class="w-full text-xs p-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none resize-none bg-white"></textarea>
                </td>
                <td class="px-4 py-3 align-top">
                    <input type="number" name="new_items[${newItemIndex}][qty]" value="1" required min="0.01" step="any"
                        class="w-full text-center text-xs p-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none bg-white">
                </td>
                <td class="px-4 py-3 align-top">
                    <input type="text" name="new_items[${newItemIndex}][unit]" value="Unit" required
                        class="w-full text-center text-xs p-2 rounded-lg border border-slate-200 focus:border-blue-500 outline-none bg-white">
                </td>
                <td class="px-4 py-3 align-top text-center">
                    <button type="button" onclick="removeNewRow(${newItemIndex})" title="Batalkan item baru ini"
                        class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded-lg transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
            newItemIndex++;
        }

        function removeNewRow(idx) {
            const row = document.getElementById('new-row-' + idx);
            if (row) {
                row.remove();
            }
        }
    </script>

</x-app-layout>
