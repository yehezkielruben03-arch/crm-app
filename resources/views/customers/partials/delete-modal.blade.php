@if(auth()->user()->isAdminOrAbove())
<!-- MODAL KELOLA PENGHAPUSAN CUSTOMER (ARSIP VS HAPUS PERMANEN) -->
<div id="deleteCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 hidden px-4">
    <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-gray-100 animate-in">
        <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
            <h3 class="text-base font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Kelola Penghapusan Customer
            </h3>
            <button type="button" onclick="closeDeleteCustomerModal()" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Info Customer Yang Dipilih -->
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 mb-4">
            <div class="font-bold text-sm text-slate-800" id="delModalCompanyName">-</div>
            <div class="flex items-center gap-3 text-xs text-slate-500 mt-1">
                <span>Kode: <strong class="font-mono text-slate-700" id="delModalCompanyCode">-</strong></span>
                <span>•</span>
                <span>Sales: <span class="font-medium text-slate-700" id="delModalSalesName">-</span></span>
            </div>
            <div class="mt-2 text-xs text-slate-600 flex items-center gap-2" id="delModalRelations">
                <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-700 font-semibold" id="delModalRfqCount">0 RFQ</span>
                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold" id="delModalPoCount">0 PO</span>
            </div>
        </div>

        <!-- Tab Selector: Arsipkan vs Hapus Permanen -->
        <div class="flex rounded-xl bg-slate-100 p-1 mb-4 text-xs font-semibold">
            <button type="button" id="delTabBtnArchive" onclick="switchDeleteTab('archive')"
                    class="flex-1 py-2 rounded-lg text-center transition-all bg-white text-slate-800 shadow-xs">
                📁 Arsipkan (Ke Trash)
            </button>
            <button type="button" id="delTabBtnForce" onclick="switchDeleteTab('force')"
                    class="flex-1 py-2 rounded-lg text-center transition-all text-slate-500 hover:text-slate-800">
                ⚠️ Hapus Permanen (Force Delete)
            </button>
        </div>

        <!-- PANEL 1: ARSIPKAN (SOFT DELETE) -->
        <div id="delPanelArchive" class="space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                Customer akan dipindahkan ke folder <strong>Trash</strong>. Data tidak akan muncul di daftar aktif, namun masih dapat dipulihkan sewaktu-waktu jika dibutuhkan.
            </p>
            <form id="archiveCustomerForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeDeleteCustomerModal()" class="px-4 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 text-xs font-semibold text-white rounded-xl transition shadow-sm bg-amber-600 hover:bg-amber-700">
                        Arsipkan Customer
                    </button>
                </div>
            </form>
        </div>

        <!-- PANEL 2: HAPUS PERMANEN (FORCE DELETE) -->
        <div id="delPanelForce" class="space-y-4 hidden">
            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800">
                <strong>Peringatan Serius:</strong> Tindakan ini akan <strong>menghapus permanen</strong> data customer dari database dan <strong>tidak dapat dibatalkan</strong>.
            </div>

            <form id="forceDeleteCustomerForm" method="POST" action="" onsubmit="return confirmForceDeleteCustomer();">
                @csrf
                @method('DELETE')

                <!-- Opsi Solusi Duplikat (Merger) -->
                <div class="border border-slate-200 rounded-xl p-3 bg-slate-50/50 mb-3">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" id="delMergeCheck" onchange="toggleMergeTarget(this.checked)" class="rounded text-rose-600 focus:ring-rose-500 mt-0.5">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800">Pindahkan data ke Customer lain (Solusi Duplikat)</span>
                            <p class="text-slate-500 text-[11px] mt-0.5">Centang jika ini customer duplikat. Seluruh RFQ, Quotation, PO, dan kontak akan dialihkan ke customer yang benar sebelum customer ini dihapus.</p>
                        </div>
                    </label>

                    <div id="mergeTargetContainer" class="mt-3 hidden">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Cari Customer Tujuan (Customer Utama):</label>
                        <input type="text" id="mergeSearchInput" placeholder="Ketik nama atau kode customer tujuan..."
                               oninput="searchMergeCustomers(this.value)"
                               class="w-full text-xs border-slate-300 rounded-lg focus:ring-rose-500 focus:border-rose-500 p-2">
                        <input type="hidden" name="target_customer_id" id="targetCustomerId">
                        
                        <div id="mergeSearchResults" class="mt-1 max-h-36 overflow-y-auto border border-slate-200 rounded-lg bg-white hidden divide-y divide-slate-100 text-xs">
                            <!-- Hasil pencarian via JS -->
                        </div>
                        <div id="selectedTargetBadge" class="mt-2 text-xs text-emerald-700 font-semibold hidden flex items-center justify-between p-2 bg-emerald-50 rounded-lg border border-emerald-200">
                            <span id="selectedTargetText">-</span>
                            <button type="button" onclick="clearSelectedTarget()" class="text-rose-500 hover:text-rose-700 text-xs">Batal</button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeDeleteCustomerModal()" class="px-4 py-2 text-xs font-medium text-gray-600 hover:bg-gray-100 rounded-xl transition">
                        Batal
                    </button>
                    <button type="submit" id="btnSubmitForceDelete" class="px-4 py-2 text-xs font-semibold text-white rounded-xl transition shadow-sm bg-rose-600 hover:bg-rose-700">
                        Hapus Permanen Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let currentDeleteCustomerId = null;
    let currentDeleteCustomerName = '';
    let currentDeleteRfqCount = 0;
    let searchDebounceTimer = null;

    function openDeleteCustomerModal(id, name, code, sales, rfqCount, poCount) {
        currentDeleteCustomerId = id;
        currentDeleteCustomerName = name;
        currentDeleteRfqCount = rfqCount;

        document.getElementById('delModalCompanyName').textContent = name;
        document.getElementById('delModalCompanyCode').textContent = code || '-';
        document.getElementById('delModalSalesName').textContent = sales || '-';
        document.getElementById('delModalRfqCount').textContent = (rfqCount || 0) + ' RFQ';
        document.getElementById('delModalPoCount').textContent = (poCount || 0) + ' PO';

        document.getElementById('archiveCustomerForm').action = '/customers/' + id;
        document.getElementById('forceDeleteCustomerForm').action = '/customers/' + id + '/force-delete';

        // Reset state
        document.getElementById('delMergeCheck').checked = false;
        toggleMergeTarget(false);
        clearSelectedTarget();
        switchDeleteTab('archive');

        document.getElementById('deleteCustomerModal').classList.remove('hidden');
    }

    function closeDeleteCustomerModal() {
        document.getElementById('deleteCustomerModal').classList.add('hidden');
    }

    function switchDeleteTab(tab) {
        const btnArchive = document.getElementById('delTabBtnArchive');
        const btnForce = document.getElementById('delTabBtnForce');
        const panelArchive = document.getElementById('delPanelArchive');
        const panelForce = document.getElementById('delPanelForce');

        if (tab === 'archive') {
            btnArchive.className = "flex-1 py-2 rounded-lg text-center transition-all bg-white text-slate-800 shadow-xs";
            btnForce.className = "flex-1 py-2 rounded-lg text-center transition-all text-slate-500 hover:text-slate-800";
            panelArchive.classList.remove('hidden');
            panelForce.classList.add('hidden');
        } else {
            btnForce.className = "flex-1 py-2 rounded-lg text-center transition-all bg-rose-600 text-white shadow-xs";
            btnArchive.className = "flex-1 py-2 rounded-lg text-center transition-all text-slate-500 hover:text-slate-800";
            panelForce.classList.remove('hidden');
            panelArchive.classList.add('hidden');
        }
    }

    function toggleMergeTarget(show) {
        const container = document.getElementById('mergeTargetContainer');
        if (show) {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            clearSelectedTarget();
        }
    }

    function searchMergeCustomers(q) {
        clearTimeout(searchDebounceTimer);
        const resultsBox = document.getElementById('mergeSearchResults');
        if (!q || q.trim().length < 2) {
            resultsBox.classList.add('hidden');
            resultsBox.innerHTML = '';
            return;
        }

        searchDebounceTimer = setTimeout(() => {
            fetch('/api/customers/search-target?q=' + encodeURIComponent(q.trim()) + '&exclude_id=' + currentDeleteCustomerId)
                .then(res => res.json())
                .then(items => {
                    resultsBox.innerHTML = '';
                    if (!items.length) {
                        resultsBox.innerHTML = '<div class="p-2 text-slate-400 text-center">Customer tidak ditemukan</div>';
                        resultsBox.classList.remove('hidden');
                        return;
                    }
                    items.forEach(c => {
                        const row = document.createElement('div');
                        row.className = 'p-2 hover:bg-slate-50 cursor-pointer flex justify-between items-center';
                        row.innerHTML = '<div><div class="font-bold text-slate-800">' + c.name + '</div><div class="text-[10px] text-slate-400 font-mono">' + c.code + ' &bull; Sales: ' + c.sales + '</div></div><span class="text-rose-600 font-bold text-xs">Pilih &rarr;</span>';
                        row.onclick = () => selectTargetCustomer(c.id, c.name, c.code);
                        resultsBox.appendChild(row);
                    });
                    resultsBox.classList.remove('hidden');
                })
                .catch(() => {});
        }, 300);
    }

    function selectTargetCustomer(id, name, code) {
        document.getElementById('targetCustomerId').value = id;
        document.getElementById('mergeSearchResults').classList.add('hidden');
        document.getElementById('mergeSearchInput').classList.add('hidden');
        const badge = document.getElementById('selectedTargetBadge');
        document.getElementById('selectedTargetText').textContent = 'Dialihkan ke: ' + name + ' (' + code + ')';
        badge.classList.remove('hidden');
    }

    function clearSelectedTarget() {
        document.getElementById('targetCustomerId').value = '';
        document.getElementById('mergeSearchInput').value = '';
        document.getElementById('mergeSearchInput').classList.remove('hidden');
        document.getElementById('mergeSearchResults').classList.add('hidden');
        document.getElementById('selectedTargetBadge').classList.add('hidden');
    }

    function confirmForceDeleteCustomer() {
        const isMerge = document.getElementById('delMergeCheck').checked;
        const targetId = document.getElementById('targetCustomerId').value;

        if (isMerge && !targetId) {
            alert('Silakan pilih customer tujuan pengalihan data terlebih dahulu.');
            return false;
        }

        if (isMerge) {
            return confirm('Yakin hapus permanen customer "' + currentDeleteCustomerName + '" dan pindahkan seluruh transaksinya ke customer terpilih?');
        }

        if (currentDeleteRfqCount > 0) {
            return confirm('Customer ini memiliki ' + currentDeleteRfqCount + ' RFQ. Menghapus customer ini akan melepaskan relasi customer pada RFQ tersebut. Lanjutkan hapus permanen?');
        }

        return confirm('Yakin ingin menghapus PERMANEN customer "' + currentDeleteCustomerName + '" dari database? Tindakan ini TIDAK DAPAT DIBATALKAN!');
    }

    // Close on backdrop
    document.getElementById('deleteCustomerModal')?.addEventListener('click', function(e) {
        if (e.target === this) closeDeleteCustomerModal();
    });
</script>
@endif
