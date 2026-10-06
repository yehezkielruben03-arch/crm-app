<x-app-layout>
    <div class="space-y-6">

        {{-- Header Breadcrumb & Actions --}}
        <div class="flex flex-col gap-4 mb-6 sm:flex-row sm:items-center sm:justify-between animate-in">
            <div class="flex items-center gap-3">
                <a href="{{ route('rfq.show', $rfq) }}"
                   class="p-2.5 rounded-xl border border-slate-200 bg-white shadow-xs hover:bg-slate-50 transition-colors text-slate-500 hover:text-slate-800"
                   title="Kembali ke Detail RFQ">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <div class="flex flex-wrap items-center gap-2.5">
                        <h1 class="text-xl font-bold font-mono text-slate-900 tracking-tight">Kalkulasi HPP &amp; Penawaran: {{ $rfq->rfq_number }}</h1>
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                            {{ $rfq->type }}
                        </span>
                        <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                            {{ $rfq->status }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                        <span>Customer: <strong class="text-slate-700">{{ $rfq->customer_name }}</strong></span>
                        <span class="text-slate-300">&bull;</span>
                        <span>Sales: <strong class="text-slate-700">{{ $rfq->sales_name }}</strong></span>
                        @if(($rfq->customer?->ongkir_pedia ?? 0) > 0)
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded text-[11px] font-medium border border-blue-100 flex items-center gap-1">
                                🚚 Ongkir Default Customer: <b>Rp {{ number_format($rfq->customer->ongkir_pedia, 0, ',', '.') }}</b>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-medium text-slate-500">Total Item: <strong class="text-slate-800" id="header-total-items">{{ $rfq->items->count() }}</strong></span>
                <a href="{{ route('rfq.show', $rfq) }}" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-xs transition">
                    Batal
                </a>
            </div>
        </div>

        <form action="{{ route('rfq.submit_price', $rfq) }}" method="POST" id="price-calculation-form" onsubmit="return validatePriceFormSubmit(event)">
            @csrf
            
            {{-- Alert Catatan dari Sales (jika ada) --}}
            @if($rfq->notes)
            <div class="mb-6 p-4 rounded-2xl bg-amber-50/90 border border-amber-200 text-amber-900 shadow-xs animate-in">
                <div class="flex items-center gap-2 font-bold text-sm text-amber-800 mb-1">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Catatan dari Sales ({{ $rfq->sales_name }}):</span>
                    @if($rfq->items->count() === 0)
                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-700 border border-rose-200">Item belum dirinci oleh Sales</span>
                    @endif
                </div>
                <p class="text-xs text-amber-900 leading-relaxed font-medium pl-7 whitespace-pre-line">{{ $rfq->notes }}</p>
            </div>
            @endif

            {{-- Alert Catatan Revisi dari Leader (jika ada) --}}
            @if($rfq->revision_notes)
            <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-900 shadow-xs animate-in">
                <div class="flex items-center gap-2 font-bold text-sm text-amber-800 mb-1">
                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Catatan Arahan Revisi dari Leader:
                </div>
                <p class="text-xs text-amber-900 leading-relaxed font-medium pl-7">{{ $rfq->revision_notes }}</p>
            </div>
            @endif
            
            @php
                $blocks = [];
                $topLevelItems = $rfq->items->filter(fn($it) => empty($it->parent_id));
                if ($rfq->type === 'Non Projek') {
                    $blocks[] = [
                        'key' => 'non_projek',
                        'title' => 'Daftar Item Permintaan Non-Projek',
                        'items' => $topLevelItems,
                        'categoryName' => null,
                    ];
                } else {
                    foreach (['Hardware', 'Jasa Pemasangan', 'Material Support'] as $cat) {
                        $catItems = $topLevelItems->filter(function($it) use ($cat) {
                            return \App\Models\RfqItem::normalizeCategory($it->category) === $cat;
                        });
                        $blocks[] = [
                            'key' => \Illuminate\Support\Str::slug($cat, '_'),
                            'title' => 'Blok ' . $cat,
                            'items' => $catItems,
                            'categoryName' => $cat,
                        ];
                    }
                    $uncat = $topLevelItems->filter(function($it) {
                        return empty($it->category);
                    });
                    if ($uncat->count() > 0) {
                        $blocks[] = [
                            'key' => 'uncategorized',
                            'title' => 'Item Lainnya',
                            'items' => $uncat,
                            'categoryName' => null,
                        ];
                    }
                }
                $globalItemIndex = 1;
            @endphp

            @foreach($blocks as $block)
            @php 
                $categoryName = $block['categoryName'];
                $categoryItems = $block['items'];
                $blockKey = $block['key'];
            @endphp
            
            <div class="mb-8" id="block-section-{{ $blockKey }}">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        {{ $block['title'] }}
                    </h2>
                    <div class="flex items-center gap-2">
                        @if($categoryName === 'Material Support')
                        <button type="button" onclick="calculateAndApplyMiscellaneousMaterial('{{ $blockKey }}')"
                            class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-amber-800 bg-amber-50 hover:bg-amber-100 border border-amber-300 rounded-lg transition shadow-2xs"
                            title="Hitung otomatis 15% dari total harga jual barang material support">
                            <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span>⚡ Hitung Miscelanious Material (15%)</span>
                        </button>
                        @endif
                        <span class="text-xs bg-slate-100 text-slate-700 font-semibold px-2.5 py-1 rounded-full border border-slate-200" id="block-count-badge-{{ $blockKey }}">
                            {{ $categoryItems->count() }} Item
                        </span>
                        @if(!$categoryName || $categoryName === 'Hardware')
                        <div class="inline-flex rounded-lg shadow-2xs border border-indigo-200 bg-white p-0.5">
                            <button type="button" onclick="addNewPcRakitanItem('{{ $categoryName }}', '{{ $blockKey }}', 'standar')"
                                class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold text-indigo-700 hover:bg-indigo-50 rounded-md transition"
                                title="Tambah item Paket PC Rakitan Standar">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                <span>+ PC Rakitan</span>
                            </button>
                            <button type="button" onclick="addNewPcRakitanItem('{{ $categoryName }}', '{{ $blockKey }}', 'tinggi')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-purple-700 hover:bg-purple-50 rounded-md transition border-l border-indigo-100"
                                title="Tambah item Paket PC Rakitan Spek Tinggi">
                                <span>+ Spek Tinggi</span>
                            </button>
                        </div>
                        @endif
                        <button type="button" onclick="addNewPriceItem('{{ $categoryName }}', '{{ $blockKey }}')"
                            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition shadow-2xs">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>+ Tambah Item</span>
                        </button>
                    </div>
                </div>

                @if($categoryName === 'Jasa Pemasangan')
                <div class="mb-5 bg-gradient-to-r from-amber-50/80 via-white to-amber-50/40 rounded-2xl border border-amber-200 p-4 lg:p-5 shadow-xs">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3 pb-3 border-b border-amber-200/80">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center font-bold shadow-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-amber-950 uppercase tracking-wider">Kalkulator Teknisi (MP) &amp; Akomodasi Pedia</h3>
                                <p class="text-[11px] text-amber-800">Tentukan durasi pengerjaan dan tarif per hari, lalu terapkan otomatis untuk kalkulasi HPP internal (otomatis disembunyikan &amp; dilebur ke jasa utama di PDF penawaran klien).</p>
                            </div>
                        </div>
                        <span class="text-[11px] bg-amber-100 text-amber-800 font-semibold px-2.5 py-0.5 rounded-full border border-amber-300">
                            Internal HPP • Dilebur di PDF
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-end">
                        <div>
                            <label class="text-[11px] font-bold text-slate-700 mb-1 block">
                                Durasi Pengerjaan <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-xl shadow-2xs">
                                <input type="number" id="calc-mp-days" value="1" min="1" step="1"
                                       class="w-full text-xs font-bold text-center border-slate-300 rounded-xl focus:border-amber-500 focus:ring-amber-500"
                                       oninput="updateMpCalcSummary()">
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-xs font-semibold text-slate-500">Hari</span>
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-bold text-slate-700 block">Tarif MP Pedia / Hari</label>
                                <span class="text-[10px] text-slate-500 font-normal">(bisa diedit)</span>
                            </div>
                            <div class="relative rounded-xl shadow-2xs">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" inputmode="numeric" id="calc-mp-rate"
                                       value="{{ number_format($mpPediaRate, 0, ',', '.') }}"
                                       class="w-full pl-9 pr-3 text-xs font-bold text-slate-800 border-slate-300 rounded-xl rupiah-input focus:border-amber-500 focus:ring-amber-500"
                                       oninput="updateMpCalcSummary()">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-bold text-slate-700 block">Akomodasi &amp; Transport / Hari</label>
                                <span class="text-[10px] text-slate-500 font-normal">(bensin, tol, makan)</span>
                            </div>
                            <div class="relative rounded-xl shadow-2xs">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" inputmode="numeric" id="calc-transport-rate"
                                       value="207.900"
                                       class="w-full pl-9 pr-3 text-xs font-bold text-slate-800 border-slate-300 rounded-xl rupiah-input focus:border-amber-500 focus:ring-amber-500"
                                       oninput="updateMpCalcSummary()">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[11px] font-bold text-slate-700 block">Mobdemob (Mobilisasi Logistik)</label>
                                <span class="text-[10px] text-slate-500 font-normal">(antar alat &amp; tim)</span>
                            </div>
                            <div class="relative rounded-xl shadow-2xs">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">Rp</span>
                                <input type="text" inputmode="numeric" id="calc-mobdemob-rate"
                                       value="0"
                                       class="w-full pl-9 pr-3 text-xs font-bold text-slate-800 border-slate-300 rounded-xl rupiah-input focus:border-amber-500 focus:ring-amber-500"
                                       oninput="updateMpCalcSummary()" placeholder="0">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-amber-200/80 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-3 text-xs">
                            <span class="text-slate-600">
                                MP: <strong class="text-amber-900 font-mono" id="summary-mp-total">Rp {{ number_format($mpPediaRate, 0, ',', '.') }}</strong>
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-600">
                                Akomodasi: <strong class="text-amber-900 font-mono" id="summary-transport-total">Rp 207.900</strong>
                            </span>
                            <span class="text-slate-300">|</span>
                            <span class="text-slate-600">
                                Mobdemob: <strong class="text-amber-900 font-mono" id="summary-mobdemob-total">Rp 0</strong>
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" onclick="applyMpItemToTable('{{ $blockKey }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-xl transition shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ MP Pedia</span>
                            </button>
                            <button type="button" onclick="applyTransportItemToTable('{{ $blockKey }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-xl transition shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Akomodasi</span>
                            </button>
                            <button type="button" onclick="applyMobdemobItemToTable('{{ $blockKey }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-xl transition shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Mobdemob</span>
                            </button>
                            <button type="button" onclick="applyAllLaborAndMobdemob('{{ $blockKey }}')"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl transition shadow-xs">
                                <span>⚡ Terapkan Semua</span>
                            </button>
                        </div>
                    </div>
                </div>
                @endif
                        
                <div class="space-y-5" id="items-container-{{ $blockKey }}">
                    <div id="empty-state-{{ $blockKey }}" class="{{ $categoryItems->count() > 0 ? 'hidden ' : '' }}bg-white rounded-2xl border border-dashed border-slate-300 p-8 text-center">
                        <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-2.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-600 mb-0.5">Belum ada item di {{ $block['title'] }}</p>
                        <p class="text-[11px] text-slate-400 mb-3">Klik tombol tambah untuk mulai mengisi rincian HPP barang pada blok ini.</p>
                        <div class="flex items-center justify-center gap-2">
                            <button type="button" onclick="addNewPriceItem('{{ $categoryName }}', '{{ $blockKey }}')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg transition shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Item ke {{ $block['title'] }}
                            </button>
                            @if(!$categoryName || $categoryName === 'Hardware')
                            <button type="button" onclick="addNewPcRakitanItem('{{ $categoryName }}', '{{ $blockKey }}', 'standar')"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg transition shadow-2xs">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                + Paket PC Rakitan
                            </button>
                            @endif
                        </div>
                    </div>

                    @foreach($categoryItems as $item)
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden item-card-row" id="card-item-{{ $item->id }}">
                        
                        {{-- Card Header --}}
                        <div class="px-6 py-3.5 bg-gradient-to-r from-slate-50 via-slate-50/50 to-white border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center justify-center shadow-xs item-badge-num">
                                    #{{ $globalItemIndex++ }}
                                </span>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                        <span id="display-name-{{ $item->id }}">{{ $item->product_name }}</span>
                                        @if($categoryName)
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                                {{ $categoryName }}
                                            </span>
                                        @endif
                                        @if($item->isHiddenFromQuotation())
                                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                                Internal HPP (Dilebur di PDF)
                                            </span>
                                        @endif
                                    </h3>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100/70 cursor-pointer shadow-2xs text-xs font-semibold text-indigo-800 transition select-none">
                                    <input type="checkbox" name="items[{{ $item->id }}][is_bundle]" value="1" id="bundle-toggle-{{ $item->id }}"
                                           class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5"
                                           {{ ($item->is_bundle || $item->components->isNotEmpty() || str_contains(strtolower($item->product_name ?? ''), 'rakitan')) ? 'checked' : '' }}
                                           onchange="toggleBundlePanel('{{ $item->id }}')">
                                    <span>Paket Rakitan</span>
                                </label>
                                <div class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 shadow-2xs">
                                    Qty Target: <span class="text-blue-600 font-bold" id="badge-qty-{{ $item->id }}">{{ (int)$item->qty }}</span> {{ $item->unit ?? 'Unit' }}
                                </div>
                                <button type="button" onclick="deletePriceItem('{{ $item->id }}', '{{ $blockKey }}')"
                                    class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition"
                                    title="Hapus Item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Card Body: 3 Clean Balanced Columns Grid --}}
                        <div class="p-5 lg:p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            {{-- COL 1: Info Barang & Vendor --}}
                            <div class="space-y-3 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            1. Deskripsi &amp; Vendor
                                        </span>
                                    </div>

                                    <input type="hidden" name="items[{{ $item->id }}][category]" value="{{ $categoryName }}">

                                    <div>
                                        <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Nama Item / Produk <span class="text-rose-500">*</span></label>
                                        <input type="text" name="items[{{ $item->id }}][product_name]" value="{{ old('items.'.$item->id.'.product_name', $item->product_name) }}"
                                               class="w-full text-xs font-semibold text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                               oninput="document.getElementById('display-name-{{ $item->id }}').innerText = this.value" required>
                                    </div>

                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="flex items-center gap-1.5">
                                                <label class="text-[11px] font-semibold text-slate-600 block">Vendor / Supplier</label>
                                                <span class="text-[10px] text-slate-400 font-normal">(bisa ketik langsung)</span>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <input type="text"
                                                   name="items[{{ $item->id }}][vendor_name]"
                                                   id="vendor-input-{{ $item->id }}"
                                                   list="vendor-datalist"
                                                   value="{{ old('items.'.$item->id.'.vendor_name', $item->vendor->nama_vendor ?? '') }}"
                                                   class="vendor-input-field w-full text-xs text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs font-medium placeholder:text-slate-400"
                                                   placeholder="Ketik langsung nama vendor / supplier...">
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2.5">
                                        <div>
                                            <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Qty <span class="text-rose-500">*</span></label>
                                            <input type="number" name="items[{{ $item->id }}][qty]" id="qty-{{ $item->id }}" value="{{ old('items.'.$item->id.'.qty', (int) $item->qty) }}"
                                                   class="w-full text-xs font-bold text-center border-slate-200 rounded-xl calc-trigger focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                                   oninput="document.getElementById('badge-qty-{{ $item->id }}').innerText = this.value" required min="1">
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Satuan</label>
                                            <input type="text" name="items[{{ $item->id }}][unit]" value="{{ old('items.'.$item->id.'.unit', $item->unit) }}"
                                                   class="w-full text-xs text-center border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs" placeholder="Unit / Pcs">
                                        </div>
                                    </div>

                                    <div>
                                        <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Spesifikasi / Keterangan Item</label>
                                        <textarea name="items[{{ $item->id }}][description]" rows="2"
                                                  class="w-full text-xs border-slate-200 rounded-xl resize-none focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                                  placeholder="Part number, spesifikasi teknis, atau keterangan barang...">{{ old('items.'.$item->id.'.description', $item->description ?: $item->detail_item) }}</textarea>
                                    </div>
                                </div>
                            </div>

                            {{-- COL 2: Modal & Biaya Logistik (HPP Components) --}}
                            <div class="space-y-3 bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-200/80 pb-1.5">
                                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                            2. Biaya Modal (HPP)
                                        </span>
                                    </div>

                                    <div>
                                        <label class="text-[11px] font-bold text-slate-700 mb-1 block">HPP Dasar / Modal Satuan <span class="text-rose-500">*</span></label>
                                        <div class="relative rounded-xl shadow-2xs">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">Rp</span>
                                            @php
                                                $oldHpp = old('items.'.$item->id.'.hpp');
                                                $valHpp = $oldHpp !== null ? $oldHpp : (($item->hpp && (float)$item->hpp > 0) ? number_format((float)$item->hpp, 0, ',', '.') : '');
                                            @endphp
                                            <input type="text" inputmode="numeric" name="items[{{ $item->id }}][hpp]" id="hpp-{{ $item->id }}" data-item-id="{{ $item->id }}"
                                                   value="{{ $valHpp }}"
                                                   class="w-full pl-9 pr-3 text-sm font-bold text-slate-800 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                                   required placeholder="0">
                                        </div>
                                    </div>

                                    <div class="space-y-2.5">
                                        <div>
                                            <label class="text-[11px] font-semibold text-slate-600 mb-1 flex items-center justify-between">
                                                <span>Ongkir ke Pedia <span class="text-[10px] text-slate-400 font-normal">(dari Vendor)</span></span>
                                                <span class="text-[10px] bg-slate-200/80 px-1.5 py-0.5 rounded text-slate-600 font-mono">Inbound</span>
                                            </label>
                                            <div class="relative rounded-xl shadow-2xs">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">Rp</span>
                                                @php
                                                    $oldBiayaKirim = old('items.'.$item->id.'.biaya_kirim');
                                                    $valBiayaKirim = $oldBiayaKirim !== null ? $oldBiayaKirim : (($item->biaya_kirim && (float)$item->biaya_kirim > 0) ? number_format((float)$item->biaya_kirim, 0, ',', '.') : '0');
                                                @endphp
                                                <input type="text" inputmode="numeric" name="items[{{ $item->id }}][biaya_kirim]" id="biaya-kirim-{{ $item->id }}" data-item-id="{{ $item->id }}"
                                                       value="{{ $valBiayaKirim }}"
                                                       class="w-full pl-9 pr-3 text-xs font-semibold text-slate-800 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                                       placeholder="0">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="text-[11px] font-semibold text-blue-700 mb-1 flex items-center justify-between">
                                                <span>Ongkir dari Pedia <span class="text-[10px] text-blue-500 font-normal">(ke Customer)</span></span>
                                                <span class="text-[10px] bg-blue-100 px-1.5 py-0.5 rounded text-blue-700 font-mono font-medium">Outbound</span>
                                            </label>
                                            <div class="relative rounded-xl shadow-2xs">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-blue-400">Rp</span>
                                                @php
                                                    $oldOngkirPedia = old('items.'.$item->id.'.ongkir_pedia');
                                                    $rawOngkirPedia = ($item->ongkir_pedia && (float)$item->ongkir_pedia > 0) ? $item->ongkir_pedia : ($rfq->customer->ongkir_pedia ?? 0);
                                                    $valOngkirPedia = $oldOngkirPedia !== null ? $oldOngkirPedia : ((float)$rawOngkirPedia > 0 ? number_format((float)$rawOngkirPedia, 0, ',', '.') : '0');
                                                @endphp
                                                <input type="text" inputmode="numeric" name="items[{{ $item->id }}][ongkir_pedia]" id="ongkir-pedia-{{ $item->id }}" data-item-id="{{ $item->id }}"
                                                       value="{{ $valOngkirPedia }}"
                                                       class="w-full pl-9 pr-3 text-xs font-semibold text-blue-900 bg-blue-50/50 border-blue-200 rounded-xl rupiah-input calc-trigger focus:border-blue-500 focus:ring-blue-500">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="text-[11px] font-semibold text-slate-500 mb-1 block flex items-center justify-between">
                                                <span>Fee End-User <span class="text-[10px] text-slate-400 font-normal">(Opsional)</span></span>
                                            </label>
                                            <div class="relative rounded-xl shadow-2xs">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">Rp</span>
                                                @php
                                                    $oldFeeEu = old('items.'.$item->id.'.fee_eu');
                                                    $valFeeEu = $oldFeeEu !== null ? $oldFeeEu : (($item->fee_eu && (float)$item->fee_eu > 0) ? number_format((float)$item->fee_eu, 0, ',', '.') : '0');
                                                @endphp
                                                <input type="text" inputmode="numeric" name="items[{{ $item->id }}][fee_eu]" id="fee-eu-{{ $item->id }}" data-item-id="{{ $item->id }}"
                                                       value="{{ $valFeeEu }}"
                                                       class="w-full pl-9 pr-3 text-xs font-medium text-slate-700 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                                       placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Total Modal Box --}}
                                <div class="p-3 bg-blue-50/90 rounded-xl border border-blue-200/90 flex items-center justify-between mt-2">
                                    <div>
                                        <span class="text-[10px] font-bold text-blue-800 uppercase tracking-wider block">Total Modal Satuan</span>
                                        <span class="text-[10px] text-slate-500 font-normal">HPP + Ongkir + Fee</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm font-bold font-mono text-blue-950">Rp <span id="total-modal-{{ $item->id }}">0</span></span>
                                    </div>
                                </div>
                            </div>

                            {{-- COL 3: Margin, Ceiling & Penawaran Klien --}}
                            <div class="space-y-3 flex flex-col justify-between">
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                        <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                            3. Margin, Ceiling &amp; Penawaran
                                        </span>
                                    </div>

                                    {{-- Margin & Profit row --}}
                                    <div class="grid grid-cols-12 gap-2">
                                        <div class="col-span-6">
                                            <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Tipe &amp; Nilai Margin</label>
                                            <div class="flex rounded-xl shadow-2xs overflow-hidden border border-slate-200 bg-white">
                                                <select name="items[{{ $item->id }}][margin_type]" id="margin-type-{{ $item->id }}" data-item-id="{{ $item->id }}"
                                                        class="text-xs font-bold text-slate-700 border-0 bg-slate-50 focus:ring-0 w-16 py-1.5 pl-2 pr-6 calc-trigger">
                                                    <option value="percentage" {{ ($item->margin_type ?? 'percentage') == 'percentage' ? 'selected' : '' }}>%</option>
                                                    <option value="nominal" {{ ($item->margin_type ?? 'percentage') == 'nominal' ? 'selected' : '' }}>Rp</option>
                                                </select>
                                                @php
                                                    $isNominalMargin = ($item->margin_type ?? 'percentage') === 'nominal';
                                                    $oldMarginVal = old('items.'.$item->id.'.margin_value');
                                                    $rawMarginVal = ($item->margin_value && (float)$item->margin_value > 0) ? $item->margin_value : (($item->margin && (float)$item->margin > 0) ? $item->margin : 12.5);
                                                    $valMargin = $oldMarginVal !== null ? $oldMarginVal : ($isNominalMargin ? number_format((float)$rawMarginVal, 0, ',', '.') : (float)$rawMarginVal);
                                                @endphp
                                                <input type="text" inputmode="numeric" name="items[{{ $item->id }}][margin_value]" id="margin-val-{{ $item->id }}" data-item-id="{{ $item->id }}"
                                                       value="{{ $valMargin }}"
                                                       class="w-full text-xs font-bold text-right border-0 border-l border-slate-200 focus:ring-0 py-1.5 pr-2.5 calc-trigger"
                                                       required placeholder="12.5">
                                            </div>
                                        </div>

                                        <div class="col-span-6">
                                            <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Untung / Unit</label>
                                            <div class="p-2 bg-emerald-50/90 border border-emerald-200/90 rounded-xl flex items-center justify-between h-[36px]">
                                                <span class="text-[10px] font-bold text-emerald-700 uppercase">Profit</span>
                                                <span class="text-xs font-bold font-mono text-emerald-800" id="margin-rp-preview-{{ $item->id }}">Rp 0</span>
                                            </div>
                                            <input type="hidden" id="margin-rp-raw-{{ $item->id }}" value="0">
                                        </div>
                                    </div>

                                    {{-- Ceiling Section --}}
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="text-[11px] font-semibold text-slate-600">Pembulatan (Ceiling)</label>
                                            <span class="text-[10px] text-slate-400">Bulatkan harga ke atas</span>
                                        </div>
                                        <div class="grid grid-cols-4 gap-1.5">
                                            <button type="button" onclick="setCeiling('{{ $item->id }}', 1)" id="btn-ceil-{{ $item->id }}-1"
                                                    class="ceil-btn-{{ $item->id }} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">1</button>
                                            <button type="button" onclick="setCeiling('{{ $item->id }}', 1000)" id="btn-ceil-{{ $item->id }}-1000"
                                                    class="ceil-btn-{{ $item->id }} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">1k</button>
                                            <button type="button" onclick="setCeiling('{{ $item->id }}', 10000)" id="btn-ceil-{{ $item->id }}-10000"
                                                    class="ceil-btn-{{ $item->id }} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">10k</button>
                                            <button type="button" onclick="setCeiling('{{ $item->id }}', 50000)" id="btn-ceil-{{ $item->id }}-50000"
                                                    class="ceil-btn-{{ $item->id }} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">50k</button>
                                        </div>
                                        <div class="relative rounded-xl shadow-2xs mt-1.5">
                                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">Rp</span>
                                            @php
                                                $oldCeiling = old('items.'.$item->id.'.custom_ceiling');
                                                $rawCeiling = $item->custom_ceiling ?? $item->ceiling ?? 50000;
                                                $valCeiling = $oldCeiling !== null ? $oldCeiling : number_format((float)$rawCeiling, 0, ',', '.');
                                            @endphp
                                            <input type="text" inputmode="numeric" name="items[{{ $item->id }}][custom_ceiling]" id="ceiling-{{ $item->id }}" data-item-id="{{ $item->id }}"
                                                   value="{{ $valCeiling }}"
                                                   class="w-full pl-9 pr-3 py-1.5 text-xs font-semibold text-slate-800 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="Custom ceiling">
                                        </div>
                                    </div>
                                </div>

                                {{-- Executive Pricing Result Card --}}
                                <div class="p-4 rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white shadow-md border border-slate-700 mt-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                            Harga Jual Klien
                                        </span>
                                        <span class="text-[10px] bg-slate-800/90 px-2 py-0.5 rounded border border-slate-700 text-slate-300 font-mono">Per Unit</span>
                                    </div>

                                    <div class="text-2xl font-extrabold font-mono text-emerald-400 tracking-tight my-1.5 flex items-baseline gap-1">
                                        <span class="text-base text-emerald-400/80">Rp</span>
                                        <span id="final-price-{{ $item->id }}">0</span>
                                    </div>

                                    <p class="text-[10px] text-slate-400 leading-snug">
                                        Setelah margin &amp; pembulatan ceiling.
                                    </p>

                                    <div class="pt-2.5 border-t border-slate-700/80 mt-2.5 flex items-center justify-between text-xs">
                                        <div>
                                            <span class="text-slate-400 block text-[10px]">Subtotal Item:</span>
                                            <span class="font-bold font-mono text-white text-sm">Rp <span id="final-total-{{ $item->id }}">0</span></span>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-slate-400 block text-[10px]">Est. Laba Item:</span>
                                            <span class="font-semibold font-mono text-emerald-300 text-xs" id="final-profit-row-{{ $item->id }}">+Rp 0</span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                        {{-- Panel Komponen PC Rakitan (Hardware Bundling) --}}
                        <div id="bundle-panel-{{ $item->id }}" class="{{ ($item->is_bundle || $item->components->isNotEmpty() || str_contains(strtolower($item->product_name ?? ''), 'rakitan')) ? '' : 'hidden ' }}px-6 py-4 bg-slate-50/80 border-t border-slate-200/90">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-3 pb-2.5 border-b border-slate-200">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold shadow-2xs">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Komponen Part PC Rakitan</h4>
                                        <p class="text-[11px] text-slate-500">Hitung HPP &amp; margin masing-masing part. Total Saler otomatis menjadi harga jual paket di atas.</p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="inline-flex rounded-lg shadow-2xs border border-indigo-200 bg-white p-0.5">
                                        <button type="button" onclick="loadPcPreset('{{ $item->id }}', 'standar')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-indigo-700 hover:bg-indigo-50 rounded-md transition"
                                            title="Muat template 8 part PC Standar (Core i5)">
                                            <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                            <span>PC Standar (i5)</span>
                                        </button>
                                        <button type="button" onclick="loadPcPreset('{{ $item->id }}', 'tinggi')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-purple-700 hover:bg-purple-50 rounded-md transition border-l border-indigo-100"
                                            title="Muat template 10 part PC Spek Tinggi (Core i7 + RTX)">
                                            <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            <span>Spek Tinggi (i7+RTX)</span>
                                        </button>
                                        <button type="button" onclick="loadPcPreset('{{ $item->id }}', 'workstation')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-blue-700 hover:bg-blue-50 rounded-md transition border-l border-indigo-100"
                                            title="Muat template 10 part PC Workstation / Rendering (Core i9)">
                                            <span>Workstation (i9)</span>
                                        </button>
                                    </div>
                                    <button type="button" onclick="loadComponentsFromSalesSpec('{{ $item->id }}')"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-lg transition shadow-2xs"
                                        title="Salin baris daftar part dari catatan Sales jika ada">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span>Salin Catatan Sales</span>
                                    </button>
                                    <button type="button" onclick="addBundleComponent('{{ $item->id }}')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        <span>+ Tambah Part</span>
                                    </button>
                                </div>
                            </div>

                            @php
                                $itemSpecText = trim($item->description ?: $item->detail_item ?: '');
                                $rfqNotesText = trim($rfq->notes ?? '');
                                $salesNotePreview = $itemSpecText ?: $rfqNotesText;
                            @endphp
                            @if(!empty($salesNotePreview))
                            <div class="mb-3.5 p-3 rounded-xl bg-indigo-50/70 border border-indigo-200/90 text-indigo-950 text-xs">
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="font-bold flex items-center gap-1.5 text-indigo-900">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Catatan / Spesifikasi Permintaan Sales:
                                    </span>
                                    <span class="text-[10px] text-indigo-600 font-semibold uppercase tracking-wider">
                                        {{ !empty($itemSpecText) ? 'Dari Deskripsi Item' : 'Dari Catatan Global RFQ' }}
                                    </span>
                                </div>
                                <div class="font-mono text-[11px] leading-relaxed text-slate-700 bg-white/80 p-2.5 rounded-lg border border-indigo-100 whitespace-pre-line max-h-32 overflow-y-auto">
                                    {{ $salesNotePreview }}
                                </div>
                            </div>
                            @endif

                            <div class="overflow-x-auto">
                                <table class="w-full text-left text-xs">
                                    <thead>
                                        <tr class="text-[11px] font-bold text-slate-600 border-b border-slate-200">
                                            <th class="py-2 px-1 w-8 text-center">#</th>
                                            <th class="py-2 px-2 min-w-[200px]">Part / Komponen</th>
                                            <th class="py-2 px-2 min-w-[130px]">Vendor / Toko</th>
                                            <th class="py-2 px-2 w-16 text-center">Qty</th>
                                            <th class="py-2 px-2 min-w-[110px]">HPP (Rp)</th>
                                            <th class="py-2 px-2 min-w-[90px]">Ongkir (Rp)</th>
                                            <th class="py-2 px-2 w-20 text-center">Margin %</th>
                                            <th class="py-2 px-2 w-20 text-center">Ceiling</th>
                                            <th class="py-2 px-2 min-w-[110px] text-right">Saler / Unit</th>
                                            <th class="py-2 px-2 min-w-[110px] text-right">Subtotal</th>
                                            <th class="py-2 px-1 w-8 text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="bundle-components-list-{{ $item->id }}" class="divide-y divide-slate-100">
                                        @foreach($item->components as $cIdx => $comp)
                                        @php $cId = $comp->id; @endphp
                                        <tr class="bundle-comp-row hover:bg-white/60 transition-colors" id="comp-row-{{ $item->id }}-{{ $cId }}">
                                            <td class="py-2 px-1 text-center font-mono text-slate-400 comp-row-num">{{ $loop->iteration }}</td>
                                            <td class="py-2 px-2">
                                                <input type="hidden" name="items[{{ $item->id }}][components][{{ $cId }}][id]" value="{{ $cId }}">
                                                <input type="text" name="items[{{ $item->id }}][components][{{ $cId }}][product_name]"
                                                       value="{{ $comp->product_name }}"
                                                       class="w-full text-xs font-semibold text-slate-800 border-slate-200 rounded-lg py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500"
                                                       placeholder="Nama komponen...">
                                            </td>
                                            <td class="py-2 px-2">
                                                <input type="text" name="items[{{ $item->id }}][components][{{ $cId }}][vendor_name]"
                                                       list="vendor-datalist"
                                                       value="{{ $comp->vendor?->nama_vendor ?? '' }}"
                                                       class="w-full text-xs text-slate-700 border-slate-200 rounded-lg py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500"
                                                       placeholder="Vendor / Toko...">
                                            </td>
                                            <td class="py-2 px-2 text-center">
                                                <input type="number" name="items[{{ $item->id }}][components][{{ $cId }}][qty]"
                                                       id="comp-qty-{{ $item->id }}-{{ $cId }}"
                                                       value="{{ (int)($comp->qty ?: 1) }}" min="1" step="1"
                                                       class="w-14 text-xs font-bold text-center border-slate-200 rounded-lg py-1 px-1 calc-bundle-trigger"
                                                       data-parent-id="{{ $item->id }}">
                                                <input type="hidden" name="items[{{ $item->id }}][components][{{ $cId }}][unit]" value="{{ $comp->unit ?: 'Unit' }}">
                                            </td>
                                            <td class="py-2 px-2">
                                                <input type="text" inputmode="numeric" name="items[{{ $item->id }}][components][{{ $cId }}][hpp]"
                                                       id="comp-hpp-{{ $item->id }}-{{ $cId }}"
                                                       value="{{ number_format((float)$comp->hpp, 0, ',', '.') }}"
                                                       class="w-full text-xs font-bold text-slate-800 border-slate-200 rounded-lg py-1 px-2 rupiah-input calc-bundle-trigger"
                                                       data-parent-id="{{ $item->id }}" placeholder="0">
                                            </td>
                                            <td class="py-2 px-2">
                                                <input type="text" inputmode="numeric" name="items[{{ $item->id }}][components][{{ $cId }}][biaya_kirim]"
                                                       id="comp-biaya-{{ $item->id }}-{{ $cId }}"
                                                       value="{{ number_format((float)$comp->biaya_kirim, 0, ',', '.') }}"
                                                       class="w-full text-xs font-medium text-slate-700 border-slate-200 rounded-lg py-1 px-2 rupiah-input calc-bundle-trigger"
                                                       data-parent-id="{{ $item->id }}" placeholder="0">
                                            </td>
                                            <td class="py-2 px-2 text-center">
                                                <input type="hidden" name="items[{{ $item->id }}][components][{{ $cId }}][margin_type]" value="percentage">
                                                <input type="text" name="items[{{ $item->id }}][components][{{ $cId }}][margin_value]"
                                                       id="comp-margin-{{ $item->id }}-{{ $cId }}"
                                                       value="{{ rtrim(rtrim(number_format((float)($comp->margin_value ?: 12.5), 2, '.', ''), '0'), '.') }}"
                                                       class="w-16 text-xs font-bold text-center border-slate-200 rounded-lg py-1 px-1 calc-bundle-trigger"
                                                       data-parent-id="{{ $item->id }}" placeholder="12.5">
                                            </td>
                                            <td class="py-2 px-2 text-center">
                                                <input type="text" inputmode="numeric" name="items[{{ $item->id }}][components][{{ $cId }}][custom_ceiling]"
                                                       id="comp-ceiling-{{ $item->id }}-{{ $cId }}"
                                                       value="{{ number_format((float)($comp->custom_ceiling ?: 50000), 0, ',', '.') }}"
                                                       class="w-16 text-xs text-center border-slate-200 rounded-lg py-1 px-1 rupiah-input calc-bundle-trigger"
                                                       data-parent-id="{{ $item->id }}" placeholder="50.000">
                                            </td>
                                            <td class="py-2 px-2 text-right font-mono font-bold text-slate-800" id="comp-saler-{{ $item->id }}-{{ $cId }}">
                                                Rp {{ number_format((float)$comp->price_after_margin, 0, ',', '.') }}
                                            </td>
                                            <td class="py-2 px-2 text-right font-mono font-bold text-indigo-700" id="comp-subtotal-{{ $item->id }}-{{ $cId }}">
                                                Rp {{ number_format((float)($comp->price_after_margin * ($comp->qty ?: 1)), 0, ',', '.') }}
                                            </td>
                                            <td class="py-2 px-1 text-center">
                                                <button type="button" onclick="deleteBundleComponent('{{ $item->id }}', '{{ $cId }}')"
                                                    class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded" title="Hapus Part">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-bold text-xs border-t-2 border-slate-200 bg-white/70">
                                            <td colspan="4" class="py-2 px-2 text-slate-700">Akumulasi Paket PC Rakitan:</td>
                                            <td class="py-2 px-2 font-mono text-slate-800" id="bundle-total-modal-{{ $item->id }}">Rp 0</td>
                                            <td colspan="3" class="py-2 px-2 text-right text-slate-500 text-[11px]">Total Saler (Harga Jual Paket):</td>
                                            <td colspan="2" class="py-2 px-2 text-right font-mono text-emerald-700 text-sm" id="bundle-total-saler-{{ $item->id }}">Rp 0</td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                </div>
            </div>
            @endforeach

            {{-- Riwayat Versi Penawaran (History Revisi) --}}
            @if(isset($priceHistories) && $priceHistories->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl mb-8 border border-slate-200">
                <div class="p-5 bg-slate-50 border-b border-slate-200">
                    <div class="flex items-center justify-between mb-1.5">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Riwayat Versi Penawaran (Audit Snapshot)
                        </h3>
                        <span class="text-xs bg-blue-100 text-blue-800 font-semibold px-2.5 py-0.5 rounded-full border border-blue-200">{{ $priceHistories->count() }} Versi Tersimpan</span>
                    </div>
                    <p class="text-xs text-slate-500">Rekam jejak setiap kali kalkulasi HPP atau margin disesuaikan sebelum disahkan.</p>
                </div>

                <div class="p-5 space-y-4">
                    @foreach($priceHistories as $history)
                    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-2xs">
                        <div class="flex flex-wrap justify-between items-center mb-3 pb-2 border-b border-slate-100 gap-2">
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-bold {{ $history->approved_at ? ($history->version > 1 ? 'bg-amber-600 text-white' : 'bg-emerald-700 text-white') : 'bg-slate-700 text-white' }}">{{ $history->approved_at ? ($history->version > 1 ? 'Revisi ke-' . ($history->version - 1) . ' (Approved)' : 'Draf Awal (Approved)') : ($history->version > 1 ? 'Draf Penyesuaian ' . ($history->version - 1) . ' (Menunggu Approval)' : 'Draf Awal (Menunggu Approval)') }}</span>
                                <span class="text-xs text-slate-500 font-medium">{{ $history->created_at->format('d M Y, H:i') }}</span>
                            </div>
                            <div class="text-xs text-slate-600">
                                Disimpan oleh: <strong class="text-slate-800">{{ $history->creator->name ?? 'Admin Purchase' }}</strong>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead class="bg-slate-50 text-slate-500 font-semibold uppercase">
                                    <tr>
                                        <th class="px-3 py-2">Nama Item</th>
                                        <th class="px-3 py-2 text-center">Qty</th>
                                        <th class="px-3 py-2 text-right">Modal Satuan (HPP)</th>
                                        <th class="px-3 py-2 text-center">Margin</th>
                                        <th class="px-3 py-2 text-right">Harga Jual / Unit</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @if(is_array($history->history_data))
                                        @foreach($history->history_data as $hItem)
                                        <tr>
                                            <td class="px-3 py-2 font-semibold text-slate-800">{{ $hItem['product_name'] ?? '-' }}</td>
                                            <td class="px-3 py-2 text-center text-slate-600 font-medium">{{ (int)($hItem['qty'] ?? 1) }} {{ $hItem['unit'] ?? '' }}</td>
                                            <td class="px-3 py-2 text-right text-slate-600 font-mono">Rp {{ number_format((float)($hItem['hpp'] ?? 0), 0, ',', '.') }}</td>
                                            <td class="px-3 py-2 text-center text-slate-600">
                                                {{ ($hItem['margin_type'] ?? 'percentage') === 'nominal' ? 'Rp ' . number_format((float)($hItem['margin_value'] ?? 0), 0, ',', '.') : ($hItem['margin_value'] ?? $hItem['margin'] ?? 0) . '%' }}
                                            </td>
                                            <td class="px-3 py-2 text-right font-bold font-mono text-emerald-700">Rp {{ number_format((float)($hItem['price_after_margin'] ?? 0), 0, ',', '.') }}</td>
                                        </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Catatan Tambahan Penawaran (Quotation Note - Teks Merah) --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs mb-6">
                <div class="flex items-center justify-between mb-2">
                    <label for="rfq-notes" class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Catatan Khusus Penawaran (Teks Merah)
                    </label>
                    <span class="text-xs text-rose-600 font-semibold bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-100">Tampil warna merah di atas template tetap</span>
                </div>
                <textarea 
                    name="notes" 
                    id="rfq-notes" 
                    rows="3" 
                    class="w-full text-xs text-rose-700 bg-rose-50/20 border border-rose-200 rounded-xl p-3 focus:bg-white focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition resize-y font-medium leading-relaxed"
                    placeholder="Tulis catatan kustom penawaran (per baris), misal:&#10;- Unit READY LIMITED stok tidak mengikat&#10;- Weekend working time, estimate 3-4 weeks&#10;- Work at Height perform by certfied (TKBT2) personel">{{ old('notes', $rfq->notes) }}</textarea>

                <div class="mt-2.5 p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 space-y-1">
                    <div class="font-bold text-slate-700">Susunan Baris Note di Lembar Quotation (PDF & Preview):</div>
                    <div class="italic text-rose-600 font-medium pl-3">[Catatan kustom merah yang Anda tulis di atas akan tampil di sini]</div>
                    <div class="italic text-slate-800 font-medium pl-3">- Harga dapat berubah tanpa pemberitahuan <span class="text-[10px] text-slate-400 font-normal">(template tetap hitam)</span></div>
                    <div class="italic text-slate-800 font-medium pl-3">- Mohon tanyakan stok dan warna terlebih dahulu sebelum mengirim PO <span class="text-[10px] text-slate-400 font-normal">(template tetap hitam)</span></div>
                </div>
            </div>

            {{-- Format Pajak & Tampilan Quotation (PPN) --}}
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-xs mb-6">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                    <label class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                        </svg>
                        Format Pajak &amp; Tampilan Quotation (PPN)
                    </label>
                    @php
                        $isAutoInclude = $rfq->isIncludeTax();
                        $currentTaxType = old('tax_type', $rfq->tax_type ?? 'auto');
                    @endphp
                    <span class="text-xs text-blue-700 font-semibold bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-100">
                        Tipe Klien: {{ $rfq->customer?->customer_type ?? 'Otomatis' }} &bull; Default: {{ $isAutoInclude ? 'Include PPN' : 'Rincian PPN 11%' }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="flex items-start p-3.5 rounded-xl border border-slate-200 cursor-pointer transition hover:bg-slate-50 bg-slate-50/40">
                        <input type="radio" name="tax_type" value="auto" class="mt-0.5 text-blue-600 focus:ring-blue-500" {{ $currentTaxType === 'auto' ? 'checked' : '' }}>
                        <div class="ml-3">
                            <span class="block text-xs font-bold text-slate-800">Otomatis (Sesuai Klien)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">
                                Perorangan otomatis Include PPN (1 baris), PT/CV rincian PPN 11% (3 baris).
                            </span>
                        </div>
                    </label>

                    <label class="flex items-start p-3.5 rounded-xl border border-slate-200 cursor-pointer transition hover:bg-slate-50 bg-slate-50/40">
                        <input type="radio" name="tax_type" value="include" class="mt-0.5 text-blue-600 focus:ring-blue-500" {{ $currentTaxType === 'include' ? 'checked' : '' }}>
                        <div class="ml-3">
                            <span class="block text-xs font-bold text-slate-800">Include PPN (Pribadi / Perorangan)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">
                                Format 1 baris TOTAL HARGA. Baris PPN 11% disembunyikan agar harga tidak tampak besar.
                            </span>
                        </div>
                    </label>

                    <label class="flex items-start p-3.5 rounded-xl border border-slate-200 cursor-pointer transition hover:bg-slate-50 bg-slate-50/40">
                        <input type="radio" name="tax_type" value="exclude" class="mt-0.5 text-blue-600 focus:ring-blue-500" {{ $currentTaxType === 'exclude' ? 'checked' : '' }}>
                        <div class="ml-3">
                            <span class="block text-xs font-bold text-slate-800">Rincian PPN 11% (PT / CV / B2B)</span>
                            <span class="block text-[11px] text-slate-500 mt-0.5 leading-snug">
                                Format 3 baris resmi: TOTAL (DPP), PPn 11%, dan TOTAL HARGA (Grand Total).
                            </span>
                        </div>
                    </label>
                </div>
            </div>

            {{-- Sticky Executive Grand Total Summary Bar --}}
            <div class="sticky bottom-4 z-20 bg-white/95 backdrop-blur-md p-4 rounded-2xl border border-slate-200/90 shadow-xl flex flex-col md:flex-row items-center justify-between gap-4 mt-6">
                <div class="flex flex-wrap items-center gap-6 divide-y md:divide-y-0 md:divide-x divide-slate-200 w-full md:w-auto">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Total Modal Seluruh Item</span>
                            <span class="text-sm font-bold font-mono text-slate-800">Rp <span id="grand-total-modal">0</span></span>
                        </div>
                    </div>

                    <div class="pt-2 md:pt-0 md:pl-6">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 block">Estimasi Total Laba Kotor</span>
                        <span class="text-sm font-bold font-mono text-emerald-600">+Rp <span id="grand-total-profit">0</span></span>
                    </div>

                    <div class="pt-2 md:pt-0 md:pl-6">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 block">Grand Total Penawaran Jual</span>
                        <span class="text-lg font-extrabold font-mono text-blue-700">Rp <span id="grand-total-sales">0</span></span>
                    </div>
                </div>

                <div class="flex items-center gap-3 w-full md:w-auto justify-end">
                    <a href="{{ route('rfq.show', $rfq) }}" class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-bold text-white rounded-xl shadow-lg transition-all hover:-translate-y-0.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        @if(auth()->user()->isLeader() || auth()->user()->isSuperAdmin())
                            Simpan &amp; Sesuaikan HPP (Leader)
                        @elseif($rfq->isPendingAdmin())
                            Submit HPP ke Leader
                        @elseif($rfq->status === \App\Models\Rfq::STATUS_PENDING_LEADER)
                            Simpan &amp; Update HPP
                        @else
                            Simpan &amp; Ajukan Revisi HPP ke Leader
                        @endif
                    </button>
                </div>
            </div>

        </form>
    </div>

    {{-- Interactive Calculation Engine --}}
    <script>
        const mpPediaRate = Number('{{ $mpPediaRate ?? 336179 }}');
        const defaultCustomerOngkir = Number('{{ (float)($rfq->customer->ongkir_pedia ?? 0) }}');
        let tempItemSeq = 1;

        function validatePriceFormSubmit(event) {
            const itemCards = document.querySelectorAll('.item-card-row');
            if (itemCards.length === 0) {
                alert('Mohon tambahkan minimal satu item untuk dihitung HPP & Penawarannya.');
                if (event) event.preventDefault();
                return false;
            }
            return true;
        }

        function updateAllCardNumbers() {
            let idx = 1;
            document.querySelectorAll('.item-badge-num').forEach(el => {
                el.innerText = '#' + (idx++);
            });
            const headerTotalEl = document.getElementById('header-total-items');
            if (headerTotalEl) {
                headerTotalEl.innerText = document.querySelectorAll('.item-card-row').length;
            }
        }

        function updateBlockCount(blockKey) {
            const container = document.getElementById('items-container-' + blockKey);
            if (!container) return;
            const cards = container.querySelectorAll('.item-card-row');
            const badge = document.getElementById('block-count-badge-' + blockKey);
            if (badge) {
                badge.innerText = cards.length + ' Item';
            }
            const emptyState = document.getElementById('empty-state-' + blockKey);
            if (emptyState) {
                if (cards.length === 0) {
                    emptyState.classList.remove('hidden');
                } else {
                    emptyState.classList.add('hidden');
                }
            }
        }

        function deletePriceItem(itemId, blockKey) {
            const card = document.getElementById('card-item-' + itemId);
            if (card) {
                card.remove();
                updateBlockCount(blockKey);
                updateAllCardNumbers();
                updateGrandTotals();
            }
        }

        function addNewPriceItem(categoryName, blockKey, initialData = {}) {
            const tempId = 'new_' + Date.now() + '_' + (tempItemSeq++);
            const container = document.getElementById('items-container-' + blockKey);
            if (!container) return;

            const initName = initialData.product_name || '';
            const initVendor = initialData.vendor_name || '';
            const initQty = initialData.qty !== undefined ? initialData.qty : 1;
            const initUnit = initialData.unit || 'Unit';
            const initDesc = initialData.description || '';
            const initHpp = initialData.hpp !== undefined ? initialData.hpp : 0;
            const initBiayaKirim = initialData.biaya_kirim !== undefined ? initialData.biaya_kirim : 0;
            const initOngkirPedia = initialData.ongkir_pedia !== undefined ? initialData.ongkir_pedia : defaultCustomerOngkir;
            const initFeeEu = initialData.fee_eu !== undefined ? initialData.fee_eu : 0;
            const initMarginType = initialData.margin_type || 'percentage';
            const initMarginValue = initialData.margin_value !== undefined ? initialData.margin_value : 12.5;
            const initCeiling = initialData.custom_ceiling !== undefined ? initialData.custom_ceiling : 50000;
            const isBundle = initialData.is_bundle ? true : false;

            const catBadge = categoryName ? `<span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">${categoryName}</span>` : '';

            const html = `
            <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden item-card-row" id="card-item-${tempId}">
                <div class="px-6 py-3.5 bg-gradient-to-r from-slate-50 via-slate-50/50 to-white border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center justify-center shadow-xs item-badge-num">
                            #
                        </span>
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                <span id="display-name-${tempId}">${initName || 'Item Baru'}</span>
                                ${catBadge}
                            </h3>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-indigo-200 bg-indigo-50/70 hover:bg-indigo-100/70 cursor-pointer shadow-2xs text-xs font-semibold text-indigo-800 transition select-none">
                            <input type="checkbox" name="items[${tempId}][is_bundle]" value="1" id="bundle-toggle-${tempId}"
                                   class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 w-3.5 h-3.5"
                                   ${isBundle ? 'checked' : ''}
                                   onchange="toggleBundlePanel('${tempId}')">
                            <span>Paket Rakitan</span>
                        </label>
                        <div class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 shadow-2xs">
                            Qty Target: <span class="text-blue-600 font-bold" id="badge-qty-${tempId}">${initQty}</span> <span id="badge-unit-${tempId}">${initUnit}</span>
                        </div>
                        <button type="button" onclick="deletePriceItem('${tempId}', '${blockKey}')"
                            class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 border border-transparent hover:border-rose-200 transition"
                            title="Hapus Item">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                </div>

                <div class="p-5 lg:p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="space-y-3 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    1. Deskripsi &amp; Vendor
                                </span>
                            </div>

                            <input type="hidden" name="items[${tempId}][category]" value="${categoryName || ''}">

                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Nama Item / Produk <span class="text-rose-500">*</span></label>
                                <input type="text" name="items[${tempId}][product_name]" value="${initName}"
                                       class="w-full text-xs font-semibold text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                       placeholder="Nama barang atau jasa..."
                                       oninput="document.getElementById('display-name-${tempId}').innerText = this.value || 'Item Baru'" required>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-1.5">
                                        <label class="text-[11px] font-semibold text-slate-600 block">Vendor / Supplier</label>
                                        <span class="text-[10px] text-slate-400 font-normal">(bisa ketik langsung)</span>
                                    </div>
                                </div>
                                <div class="relative">
                                    <input type="text"
                                           name="items[${tempId}][vendor_name]"
                                           id="vendor-input-${tempId}"
                                           list="vendor-datalist"
                                           value="${initVendor}"
                                           class="vendor-input-field w-full text-xs text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs font-medium placeholder:text-slate-400"
                                           placeholder="Ketik langsung nama vendor / supplier...">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Qty <span class="text-rose-500">*</span></label>
                                    <input type="number" name="items[${tempId}][qty]" id="qty-${tempId}" value="${initQty}"
                                           class="w-full text-xs font-bold text-center border-slate-200 rounded-xl calc-trigger focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                           oninput="document.getElementById('badge-qty-${tempId}').innerText = this.value" required min="1">
                                </div>
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Satuan</label>
                                    <input type="text" name="items[${tempId}][unit]" value="${initUnit}"
                                           class="w-full text-xs text-center border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs" placeholder="Unit / Pcs">
                                </div>
                            </div>

                            <div>
                                <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Spesifikasi / Keterangan Item</label>
                                <textarea name="items[${tempId}][description]" rows="2"
                                          class="w-full text-xs border-slate-200 rounded-xl resize-none focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                          placeholder="Part number, spesifikasi teknis, atau keterangan barang...">${initDesc}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-200/80 pb-1.5">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    2. Biaya Modal (HPP)
                                </span>
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-slate-700 mb-1 block">HPP Dasar / Modal Satuan <span class="text-rose-500">*</span></label>
                                <div class="relative rounded-xl shadow-2xs">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-bold text-slate-400">Rp</span>
                                    <input type="text" inputmode="numeric" name="items[${tempId}][hpp]" id="hpp-${tempId}" data-item-id="${tempId}"
                                           value="${formatRupiah(initHpp)}"
                                           class="w-full pl-9 pr-3 text-sm font-bold text-slate-800 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                           required placeholder="0">
                                </div>
                            </div>

                            <div class="space-y-2.5">
                                <div>
                                    <label class="text-[11px] font-semibold text-slate-600 mb-1 flex items-center justify-between">
                                        <span>Ongkir ke Pedia <span class="text-[10px] text-slate-400 font-normal">(dari Vendor)</span></span>
                                        <span class="text-[10px] bg-slate-200/80 px-1.5 py-0.5 rounded text-slate-600 font-mono">Inbound</span>
                                    </label>
                                    <div class="relative rounded-xl shadow-2xs">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">Rp</span>
                                        <input type="text" inputmode="numeric" name="items[${tempId}][biaya_kirim]" id="biaya-kirim-${tempId}" data-item-id="${tempId}"
                                               value="${formatRupiah(initBiayaKirim)}"
                                               class="w-full pl-9 pr-3 text-xs font-semibold text-slate-800 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                               placeholder="0">
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[11px] font-semibold text-blue-700 mb-1 flex items-center justify-between">
                                        <span>Ongkir dari Pedia <span class="text-[10px] text-blue-500 font-normal">(ke Customer)</span></span>
                                        <span class="text-[10px] bg-blue-100 px-1.5 py-0.5 rounded text-blue-700 font-mono font-medium">Outbound</span>
                                    </label>
                                    <div class="relative rounded-xl shadow-2xs">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-blue-400">Rp</span>
                                        <input type="text" inputmode="numeric" name="items[${tempId}][ongkir_pedia]" id="ongkir-pedia-${tempId}" data-item-id="${tempId}"
                                               value="${formatRupiah(initOngkirPedia)}"
                                               class="w-full pl-9 pr-3 text-xs font-semibold text-blue-900 bg-blue-50/50 border-blue-200 rounded-xl rupiah-input calc-trigger focus:border-blue-500 focus:ring-blue-500">
                                    </div>
                                </div>

                                <div>
                                    <label class="text-[11px] font-semibold text-slate-500 mb-1 block flex items-center justify-between">
                                        <span>Fee End-User <span class="text-[10px] text-slate-400 font-normal">(Opsional)</span></span>
                                    </label>
                                    <div class="relative rounded-xl shadow-2xs">
                                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">Rp</span>
                                        <input type="text" inputmode="numeric" name="items[${tempId}][fee_eu]" id="fee-eu-${tempId}" data-item-id="${tempId}"
                                               value="${formatRupiah(initFeeEu)}"
                                               class="w-full pl-9 pr-3 text-xs font-medium text-slate-700 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                               placeholder="0">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-blue-50/90 rounded-xl border border-blue-200/90 flex items-center justify-between mt-2">
                            <div>
                                <span class="text-[10px] font-bold text-blue-800 uppercase tracking-wider block">Total Modal Satuan</span>
                                <span class="text-[10px] text-slate-500 font-normal">HPP + Ongkir + Fee</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm font-bold font-mono text-blue-950">Rp <span id="total-modal-${tempId}">0</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3 flex flex-col justify-between">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                    3. Margin, Ceiling &amp; Penawaran
                                </span>
                            </div>

                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-6">
                                    <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Tipe &amp; Nilai Margin</label>
                                    <div class="flex rounded-xl shadow-2xs overflow-hidden border border-slate-200 bg-white">
                                        <select name="items[${tempId}][margin_type]" id="margin-type-${tempId}" data-item-id="${tempId}"
                                                class="text-xs font-bold text-slate-700 border-0 bg-slate-50 focus:ring-0 w-16 py-1.5 pl-2 pr-6 calc-trigger">
                                            <option value="percentage" ${initMarginType === 'percentage' ? 'selected' : ''}>%</option>
                                            <option value="nominal" ${initMarginType === 'nominal' ? 'selected' : ''}>Rp</option>
                                        </select>
                                        <input type="text" inputmode="numeric" name="items[${tempId}][margin_value]" id="margin-val-${tempId}" data-item-id="${tempId}"
                                               value="${initMarginType === 'nominal' ? formatRupiah(initMarginValue) : initMarginValue}"
                                               class="w-full text-xs font-bold text-right border-0 border-l border-slate-200 focus:ring-0 py-1.5 pr-2.5 calc-trigger"
                                               required placeholder="12.5">
                                    </div>
                                </div>

                                <div class="col-span-6">
                                    <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Untung / Unit</label>
                                    <div class="p-2 bg-emerald-50/90 border border-emerald-200/90 rounded-xl flex items-center justify-between h-[36px]">
                                        <span class="text-[10px] font-bold text-emerald-700 uppercase">Profit</span>
                                        <span class="text-xs font-bold font-mono text-emerald-800" id="margin-rp-preview-${tempId}">Rp 0</span>
                                    </div>
                                    <input type="hidden" id="margin-rp-raw-${tempId}" value="0">
                                </div>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="text-[11px] font-semibold text-slate-600">Pembulatan (Ceiling)</label>
                                    <span class="text-[10px] text-slate-400">Bulatkan harga ke atas</span>
                                </div>
                                <div class="grid grid-cols-4 gap-1.5">
                                    <button type="button" onclick="setCeiling('${tempId}', 1)" id="btn-ceil-${tempId}-1"
                                            class="ceil-btn-${tempId} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">1</button>
                                    <button type="button" onclick="setCeiling('${tempId}', 1000)" id="btn-ceil-${tempId}-1000"
                                            class="ceil-btn-${tempId} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">1k</button>
                                    <button type="button" onclick="setCeiling('${tempId}', 10000)" id="btn-ceil-${tempId}-10000"
                                            class="ceil-btn-${tempId} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">10k</button>
                                    <button type="button" onclick="setCeiling('${tempId}', 50000)" id="btn-ceil-${tempId}-50000"
                                            class="ceil-btn-${tempId} py-1.5 text-xs font-bold rounded-lg border border-slate-200 text-slate-700 bg-white hover:bg-slate-50 transition shadow-2xs text-center">50k</button>
                                </div>
                                <div class="relative rounded-xl shadow-2xs mt-1.5">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">Rp</span>
                                    <input type="text" inputmode="numeric" name="items[${tempId}][custom_ceiling]" id="ceiling-${tempId}" data-item-id="${tempId}"
                                           value="${formatRupiah(initCeiling)}"
                                           class="w-full pl-9 pr-3 py-1.5 text-xs font-semibold text-slate-800 border-slate-200 rounded-xl rupiah-input calc-trigger focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="Custom ceiling">
                                </div>
                            </div>
                        </div>

                        <div class="p-4 rounded-xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white shadow-md border border-slate-700 mt-2">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    Harga Jual Klien
                                </span>
                                <span class="text-[10px] bg-slate-800/90 px-2 py-0.5 rounded border border-slate-700 text-slate-300 font-mono">Per Unit</span>
                            </div>

                            <div class="text-2xl font-extrabold font-mono text-emerald-400 tracking-tight my-1.5 flex items-baseline gap-1">
                                <span class="text-base text-emerald-400/80">Rp</span>
                                <span id="final-price-${tempId}">0</span>
                            </div>

                            <p class="text-[10px] text-slate-400 leading-snug">
                                Setelah margin &amp; pembulatan ceiling.
                            </p>

                            <div class="pt-2.5 border-t border-slate-700/80 mt-2.5 flex items-center justify-between text-xs">
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Subtotal Item:</span>
                                    <span class="font-bold font-mono text-white text-sm">Rp <span id="final-total-${tempId}">0</span></span>
                                </div>
                                <div class="text-right">
                                    <span class="text-slate-400 block text-[10px]">Est. Laba Item:</span>
                                    <span class="font-semibold font-mono text-emerald-300 text-xs" id="final-profit-row-${tempId}">+Rp 0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="bundle-panel-${tempId}" class="${isBundle ? '' : 'hidden '}px-6 py-4 bg-slate-50/80 border-t border-slate-200/90">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3 pb-2.5 border-b border-slate-200">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white flex items-center justify-center font-bold shadow-2xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Komponen Part PC Rakitan</h4>
                                <p class="text-[11px] text-slate-500">Hitung HPP &amp; margin masing-masing part. Total Saler otomatis menjadi harga jual paket di atas.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="inline-flex rounded-lg shadow-2xs border border-indigo-200 bg-white p-0.5">
                                <button type="button" onclick="loadPcPreset('${tempId}', 'standar')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-indigo-700 hover:bg-indigo-50 rounded-md transition"
                                    title="Muat template 8 part PC Standar (Core i5)">
                                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/></svg>
                                    <span>PC Standar (i5)</span>
                                </button>
                                <button type="button" onclick="loadPcPreset('${tempId}', 'tinggi')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-purple-700 hover:bg-purple-50 rounded-md transition border-l border-indigo-100"
                                    title="Muat template 10 part PC Spek Tinggi (Core i7 + RTX)">
                                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    <span>Spek Tinggi (i7+RTX)</span>
                                </button>
                                <button type="button" onclick="loadPcPreset('${tempId}', 'workstation')"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-blue-700 hover:bg-blue-50 rounded-md transition border-l border-indigo-100"
                                    title="Muat template 10 part PC Workstation / Rendering (Core i9)">
                                    <span>Workstation (i9)</span>
                                </button>
                            </div>
                            <button type="button" onclick="loadComponentsFromSalesSpec('${tempId}')"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-200 rounded-lg transition shadow-2xs"
                                title="Salin baris daftar part dari catatan Sales jika ada">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <span>Salin Catatan Sales</span>
                            </button>
                            <button type="button" onclick="addBundleComponent('${tempId}')"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg transition shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>+ Tambah Part</span>
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="text-[11px] font-bold text-slate-600 border-b border-slate-200">
                                    <th class="py-2 px-1 w-8 text-center">#</th>
                                    <th class="py-2 px-2 min-w-[200px]">Part / Komponen</th>
                                    <th class="py-2 px-2 min-w-[130px]">Vendor / Toko</th>
                                    <th class="py-2 px-2 w-16 text-center">Qty</th>
                                    <th class="py-2 px-2 min-w-[110px]">HPP (Rp)</th>
                                    <th class="py-2 px-2 min-w-[90px]">Ongkir (Rp)</th>
                                    <th class="py-2 px-2 w-20 text-center">Margin %</th>
                                    <th class="py-2 px-2 w-20 text-center">Ceiling</th>
                                    <th class="py-2 px-2 min-w-[110px] text-right">Saler / Unit</th>
                                    <th class="py-2 px-2 min-w-[110px] text-right">Subtotal</th>
                                    <th class="py-2 px-1 w-8 text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="bundle-components-list-${tempId}" class="divide-y divide-slate-100"></tbody>
                            <tfoot>
                                <tr class="font-bold text-xs border-t-2 border-slate-200 bg-white/70">
                                    <td colspan="4" class="py-2 px-2 text-slate-700">Akumulasi Paket PC Rakitan:</td>
                                    <td class="py-2 px-2 font-mono text-slate-800" id="bundle-total-modal-${tempId}">Rp 0</td>
                                    <td colspan="3" class="py-2 px-2 text-right text-slate-500 text-[11px]">Total Saler (Harga Jual Paket):</td>
                                    <td colspan="2" class="py-2 px-2 text-right font-mono text-emerald-700 text-sm" id="bundle-total-saler-${tempId}">Rp 0</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            `;

            container.insertAdjacentHTML('beforeend', html);

            const newCard = document.getElementById('card-item-' + tempId);
            attachRupiahListeners(newCard);
            newCard.querySelectorAll('.calc-trigger').forEach(function(el) {
                el.addEventListener('input', function() {
                    calculateRow(tempId);
                });
                el.addEventListener('change', function() {
                    calculateRow(tempId);
                });
            });

            updateBlockCount(blockKey);
            updateAllCardNumbers();
            calculateRow(tempId);
            return tempId;
        }

        function updateMpCalcSummary() {
            const days = parseInt(document.getElementById('calc-mp-days')?.value) || 1;
            const mpRate = parseRupiah(document.getElementById('calc-mp-rate')?.value) || 0;
            const trRate = parseRupiah(document.getElementById('calc-transport-rate')?.value) || 0;
            const mobRate = parseRupiah(document.getElementById('calc-mobdemob-rate')?.value) || 0;

            const mpEl = document.getElementById('summary-mp-total');
            if (mpEl) mpEl.innerText = 'Rp ' + formatRupiah(days * mpRate);

            const trEl = document.getElementById('summary-transport-total');
            if (trEl) trEl.innerText = 'Rp ' + formatRupiah(days * trRate);

            const mobEl = document.getElementById('summary-mobdemob-total');
            if (mobEl) mobEl.innerText = 'Rp ' + formatRupiah(mobRate);
        }

        function applyMpItemToTable(blockKey) {
            const days = parseInt(document.getElementById('calc-mp-days')?.value) || 1;
            const rate = parseRupiah(document.getElementById('calc-mp-rate')?.value) || mpPediaRate;
            const block = document.getElementById('items-container-' + blockKey);
            if (!block) return;

            let existingItemCard = null;
            block.querySelectorAll('.item-card-row').forEach(card => {
                const nameInput = card.querySelector('input[name$="[product_name]"]');
                if (nameInput && (nameInput.value.toLowerCase().includes('mp team pedia') || nameInput.value.toLowerCase().includes('tarif mp'))) {
                    existingItemCard = card;
                }
            });

            const itemName = `MP Team Pedia @${days}day`;
            if (existingItemCard) {
                const itemId = existingItemCard.id.replace('card-item-', '');
                const nameInput = existingItemCard.querySelector('input[name$="[product_name]"]');
                if (nameInput) {
                    nameInput.value = itemName;
                    const displayName = document.getElementById('display-name-' + itemId);
                    if (displayName) displayName.innerText = itemName;
                }
                const qtyInput = document.getElementById('qty-' + itemId);
                if (qtyInput) {
                    qtyInput.value = days;
                    const badgeQty = document.getElementById('badge-qty-' + itemId);
                    if (badgeQty) badgeQty.innerText = days;
                }
                const unitInput = existingItemCard.querySelector('input[name$="[unit]"]');
                if (unitInput) unitInput.value = 'Hari';
                const hppInput = document.getElementById('hpp-' + itemId);
                if (hppInput) hppInput.value = formatRupiah(rate);
                const marginType = document.getElementById('margin-type-' + itemId);
                if (marginType) marginType.value = 'nominal';
                const marginVal = document.getElementById('margin-val-' + itemId);
                if (marginVal) marginVal.value = '0';
                const ceilingInput = document.getElementById('ceiling-' + itemId);
                if (ceilingInput) ceilingInput.value = '1';
                const vendorInput = document.getElementById('vendor-input-' + itemId);
                if (vendorInput) vendorInput.value = 'Mainpower Pedia';

                calculateRow(itemId);
                alert(`Item "${itemName}" berhasil diperbarui di tabel!`);
            } else {
                addNewPriceItem('Jasa Pemasangan', blockKey, {
                    product_name: itemName,
                    vendor_name: 'Mainpower Pedia',
                    qty: days,
                    unit: 'Hari',
                    description: `Tenaga teknisi internal tim Pedia (${days} hari kerja)`,
                    hpp: rate,
                    biaya_kirim: 0,
                    ongkir_pedia: 0,
                    fee_eu: 0,
                    margin_type: 'nominal',
                    margin_value: 0,
                    custom_ceiling: 1
                });
                alert(`Item "${itemName}" berhasil ditambahkan ke tabel!`);
            }
        }

        function applyTransportItemToTable(blockKey) {
            const days = parseInt(document.getElementById('calc-mp-days')?.value) || 1;
            const rate = parseRupiah(document.getElementById('calc-transport-rate')?.value) || 207900;
            const block = document.getElementById('items-container-' + blockKey);
            if (!block) return;

            let existingItemCard = null;
            block.querySelectorAll('.item-card-row').forEach(card => {
                const nameInput = card.querySelector('input[name$="[product_name]"]');
                if (nameInput && (nameInput.value.toLowerCase().includes('akomodasi') || nameInput.value.toLowerCase().includes('transport'))) {
                    existingItemCard = card;
                }
            });

            const itemName = `Akomodasi & Transport Pedia @${days}day`;
            if (existingItemCard) {
                const itemId = existingItemCard.id.replace('card-item-', '');
                const nameInput = existingItemCard.querySelector('input[name$="[product_name]"]');
                if (nameInput) {
                    nameInput.value = itemName;
                    const displayName = document.getElementById('display-name-' + itemId);
                    if (displayName) displayName.innerText = itemName;
                }
                const qtyInput = document.getElementById('qty-' + itemId);
                if (qtyInput) {
                    qtyInput.value = days;
                    const badgeQty = document.getElementById('badge-qty-' + itemId);
                    if (badgeQty) badgeQty.innerText = days;
                }
                const unitInput = existingItemCard.querySelector('input[name$="[unit]"]');
                if (unitInput) unitInput.value = 'Hari';
                const hppInput = document.getElementById('hpp-' + itemId);
                if (hppInput) hppInput.value = formatRupiah(rate);
                const marginType = document.getElementById('margin-type-' + itemId);
                if (marginType) marginType.value = 'nominal';
                const marginVal = document.getElementById('margin-val-' + itemId);
                if (marginVal) marginVal.value = '0';
                const ceilingInput = document.getElementById('ceiling-' + itemId);
                if (ceilingInput) ceilingInput.value = '1';
                const vendorInput = document.getElementById('vendor-input-' + itemId);
                if (vendorInput) vendorInput.value = 'Operasional Pedia';

                calculateRow(itemId);
                alert(`Item "${itemName}" berhasil diperbarui di tabel!`);
            } else {
                addNewPriceItem('Jasa Pemasangan', blockKey, {
                    product_name: itemName,
                    vendor_name: 'Operasional Pedia',
                    qty: days,
                    unit: 'Hari',
                    description: `Biaya transportasi, bensin, tol, dan konsumsi teknisi (${days} hari kerja)`,
                    hpp: rate,
                    biaya_kirim: 0,
                    ongkir_pedia: 0,
                    fee_eu: 0,
                    margin_type: 'nominal',
                    margin_value: 0,
                    custom_ceiling: 1
                });
                alert(`Item "${itemName}" berhasil ditambahkan ke tabel!`);
            }
        }

        function applyMobdemobItemToTable(blockKey) {
            const rate = parseRupiah(document.getElementById('calc-mobdemob-rate')?.value) || 0;
            const block = document.getElementById('items-container-' + blockKey);
            if (!block) return;

            if (rate <= 0) {
                alert('Silakan isi nominal biaya Mobdemob terlebih dahulu!');
                return;
            }

            let existingItemCard = null;
            block.querySelectorAll('.item-card-row').forEach(card => {
                const nameInput = card.querySelector('input[name$="[product_name]"]');
                if (nameInput && (nameInput.value.toLowerCase().includes('mobdemob') || nameInput.value.toLowerCase().includes('mobilisasi'))) {
                    existingItemCard = card;
                }
            });

            const itemName = 'Mobdemob (Mobilisasi & Demobilisasi)';
            if (existingItemCard) {
                const itemId = existingItemCard.id.replace('card-item-', '');
                const nameInput = existingItemCard.querySelector('input[name$="[product_name]"]');
                if (nameInput) {
                    nameInput.value = itemName;
                    const displayName = document.getElementById('display-name-' + itemId);
                    if (displayName) displayName.innerText = itemName;
                }
                const qtyInput = document.getElementById('qty-' + itemId);
                if (qtyInput) {
                    qtyInput.value = 1;
                    const badgeQty = document.getElementById('badge-qty-' + itemId);
                    if (badgeQty) badgeQty.innerText = 1;
                }
                const unitInput = existingItemCard.querySelector('input[name$="[unit]"]');
                if (unitInput) unitInput.value = 'Lot';
                const hppInput = document.getElementById('hpp-' + itemId);
                if (hppInput) hppInput.value = formatRupiah(rate);
                const marginType = document.getElementById('margin-type-' + itemId);
                if (marginType) marginType.value = 'nominal';
                const marginVal = document.getElementById('margin-val-' + itemId);
                if (marginVal) marginVal.value = '0';
                const ceilingInput = document.getElementById('ceiling-' + itemId);
                if (ceilingInput) ceilingInput.value = '1';
                const vendorInput = document.getElementById('vendor-input-' + itemId);
                if (vendorInput) vendorInput.value = 'Operasional Pedia';

                calculateRow(itemId);
                alert(`Item "${itemName}" berhasil diperbarui di tabel!`);
            } else {
                addNewPriceItem('Jasa Pemasangan', blockKey, {
                    product_name: itemName,
                    vendor_name: 'Operasional Pedia',
                    qty: 1,
                    unit: 'Lot',
                    description: 'Biaya logistik, peralatan kerja, mobilisasi teknisi ke lokasi dan pemulangan kembali (demobilisasi)',
                    hpp: rate,
                    biaya_kirim: 0,
                    ongkir_pedia: 0,
                    fee_eu: 0,
                    margin_type: 'nominal',
                    margin_value: 0,
                    custom_ceiling: 1
                });
                alert(`Item "${itemName}" berhasil ditambahkan ke tabel!`);
            }
        }

        function applyBothMpAndTransport(blockKey) {
            applyMpItemToTable(blockKey);
            applyTransportItemToTable(blockKey);
        }

        function applyAllLaborAndMobdemob(blockKey) {
            applyMpItemToTable(blockKey);
            applyTransportItemToTable(blockKey);
            const mobRate = parseRupiah(document.getElementById('calc-mobdemob-rate')?.value) || 0;
            if (mobRate > 0) {
                applyMobdemobItemToTable(blockKey);
            }
        }

        function calculateAndApplyMiscellaneousMaterial(blockKey) {
            const container = document.getElementById('items-container-' + blockKey);
            if (!container) return;

            const cards = container.querySelectorAll('.item-card-row');
            let totalMaterialSales = 0;
            let materialItemCount = 0;
            let existingMiscCard = null;

            cards.forEach(card => {
                const nameInput = card.querySelector('input[name$="[product_name]"]');
                const name = nameInput ? nameInput.value.trim().toLowerCase() : '';
                const itemId = card.id.replace('card-item-', '');

                if (name.includes('miscelanious') || name.includes('miscellaneous')) {
                    existingMiscCard = card;
                } else {
                    const finalTotalText = document.getElementById('final-total-' + itemId)?.innerText || '0';
                    const rowTotal = parseRupiah(finalTotalText);
                    totalMaterialSales += rowTotal;
                    materialItemCount++;
                }
            });

            if (materialItemCount === 0) {
                alert('Belum ada item barang material support. Tambahkan item material terlebih dahulu sebelum menghitung Miscellaneous.');
                return;
            }

            const miscAmount = Math.round(totalMaterialSales * 0.15);
            const itemName = 'Miscelanious Material';

            if (existingMiscCard) {
                const itemId = existingMiscCard.id.replace('card-item-', '');
                const nameInput = existingMiscCard.querySelector('input[name$="[product_name]"]');
                if (nameInput) {
                    nameInput.value = itemName;
                    const displayName = document.getElementById('display-name-' + itemId);
                    if (displayName) displayName.innerText = itemName;
                }
                const qtyInput = document.getElementById('qty-' + itemId);
                if (qtyInput) {
                    qtyInput.value = 1;
                    const badgeQty = document.getElementById('badge-qty-' + itemId);
                    if (badgeQty) badgeQty.innerText = 1;
                }
                const unitInput = existingMiscCard.querySelector('input[name$="[unit]"]');
                if (unitInput) unitInput.value = 'Lot';
                const hppInput = document.getElementById('hpp-' + itemId);
                if (hppInput) hppInput.value = formatRupiah(miscAmount);
                const marginType = document.getElementById('margin-type-' + itemId);
                if (marginType) marginType.value = 'nominal';
                const marginVal = document.getElementById('margin-val-' + itemId);
                if (marginVal) marginVal.value = '0';
                const ceilingInput = document.getElementById('ceiling-' + itemId);
                if (ceilingInput) ceilingInput.value = '1';
                const vendorInput = document.getElementById('vendor-input-' + itemId);
                if (vendorInput) vendorInput.value = 'Toko Material / Operasional';

                calculateRow(itemId);
                alert(`Miscelanious Material berhasil diupdate: Rp ${formatRupiah(miscAmount)} (15% dari total Rp ${formatRupiah(totalMaterialSales)})`);
            } else {
                addNewPriceItem('Material Support', blockKey, {
                    product_name: itemName,
                    vendor_name: 'Toko Material / Operasional',
                    qty: 1,
                    unit: 'Lot',
                    description: 'Material pendukung & habis pakai (sekrup, fischer, klem, isolasi, seal, dll)',
                    hpp: miscAmount,
                    biaya_kirim: 0,
                    ongkir_pedia: 0,
                    fee_eu: 0,
                    margin_type: 'nominal',
                    margin_value: 0,
                    custom_ceiling: 1
                });
                alert(`Miscelanious Material berhasil ditambahkan: Rp ${formatRupiah(miscAmount)} (15% dari total Rp ${formatRupiah(totalMaterialSales)})`);
            }
        }
        
        function useMpPedia(itemId) {
            const hppInput = document.getElementById('hpp-' + itemId);
            if (hppInput) {
                hppInput.value = formatRupiah(mpPediaRate);
            }
            const vInput = document.getElementById('vendor-input-' + itemId);
            if (vInput && !vInput.value) {
                vInput.value = 'Mainpower Pedia';
            }
            calculateRow(itemId);
        }

        function setCeiling(itemId, val) {
            const input = document.getElementById('ceiling-' + itemId);
            if (input) {
                input.value = formatRupiah(val);
                calculateRow(itemId);
            }
        }

        function formatRupiah(number) {
            if (isNaN(number) || number === null || number === undefined) return '0';
            return new Intl.NumberFormat('id-ID').format(Math.round(number));
        }

        function parseRupiah(val) {
            if (val === null || val === undefined || val === '') return 0;
            if (typeof val === 'number') return isNaN(val) ? 0 : val;
            const clean = String(val).replace(/[^\d]/g, '');
            const num = parseFloat(clean);
            return isNaN(num) ? 0 : num;
        }

        function parseMarginVal(val, type) {
            if (val === null || val === undefined || val === '') return 0;
            if (type === 'percentage') {
                const clean = String(val).replace(/,/g, '.').replace(/[^\d.-]/g, '');
                const num = parseFloat(clean);
                return isNaN(num) ? 0 : num;
            }
            return parseRupiah(val);
        }

        function formatRupiahInput(input) {
            if (!input) return;
            const cursor = input.selectionStart;
            const oldLength = input.value.length;
            const digits = input.value.replace(/\D/g, '');

            if (!digits) {
                input.value = '';
                return;
            }

            const formatted = new Intl.NumberFormat('id-ID').format(digits);
            input.value = formatted;

            const diff = formatted.length - oldLength;
            const newPos = Math.max(0, cursor + diff);
            try {
                input.setSelectionRange(newPos, newPos);
            } catch (e) {}
        }

        function attachRupiahListeners(container) {
            const root = container || document;

            root.querySelectorAll('.rupiah-input').forEach(function(input) {
                input.addEventListener('input', function() {
                    formatRupiahInput(this);
                });
                if (input.value && !input.value.includes('.')) {
                    formatRupiahInput(input);
                }
            });

            root.querySelectorAll('[id^="margin-val-"]').forEach(function(input) {
                const itemId = input.dataset.itemId || input.id.replace('margin-val-', '');
                const typeSelect = document.getElementById('margin-type-' + itemId);

                input.addEventListener('input', function() {
                    if (typeSelect && typeSelect.value === 'nominal') {
                        formatRupiahInput(this);
                    }
                });

                if (typeSelect) {
                    typeSelect.addEventListener('change', function() {
                        if (this.value === 'nominal') {
                            formatRupiahInput(input);
                        } else {
                            input.value = input.value.replace(/\./g, '');
                        }
                    });
                }
            });
        }

        let tempCompSeq = 1;

        function toggleBundlePanel(itemId) {
            const toggle = document.getElementById('bundle-toggle-' + itemId);
            const panel = document.getElementById('bundle-panel-' + itemId);
            if (!toggle || !panel) return;

            if (toggle.checked) {
                panel.classList.remove('hidden');
                const tbody = document.getElementById('bundle-components-list-' + itemId);
                if (tbody && tbody.children.length === 0) {
                    loadComponentsFromSalesSpec(itemId, true);
                }
            } else {
                panel.classList.add('hidden');
                const hppInput = document.getElementById('hpp-' + itemId);
                if (hppInput) {
                    hppInput.readOnly = false;
                    hppInput.classList.remove('bg-slate-100');
                }
            }
            calculateRow(itemId);
        }

        function addBundleComponent(itemId, initData = {}) {
            const tbody = document.getElementById('bundle-components-list-' + itemId);
            if (!tbody) return;

            const tempCompId = 'c_' + Date.now() + '_' + (tempCompSeq++);
            const pName = initData.product_name || '';
            const vName = initData.vendor_name || '';
            const qty = initData.qty !== undefined ? initData.qty : 1;
            const hpp = initData.hpp !== undefined ? initData.hpp : 0;
            const biaya = initData.biaya_kirim !== undefined ? initData.biaya_kirim : 0;
            const margin = initData.margin_value !== undefined ? initData.margin_value : 12.5;
            const ceiling = initData.custom_ceiling !== undefined ? initData.custom_ceiling : 50000;

            const rowHtml = `
            <tr class="bundle-comp-row hover:bg-white/60 transition-colors" id="comp-row-${itemId}-${tempCompId}">
                <td class="py-2 px-1 text-center font-mono text-slate-400 comp-row-num">#</td>
                <td class="py-2 px-2">
                    <input type="hidden" name="items[${itemId}][components][${tempCompId}][id]" value="">
                    <input type="text" name="items[${itemId}][components][${tempCompId}][product_name]"
                           value="${pName}"
                           class="w-full text-xs font-semibold text-slate-800 border-slate-200 rounded-lg py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Nama komponen...">
                </td>
                <td class="py-2 px-2">
                    <input type="text" name="items[${itemId}][components][${tempCompId}][vendor_name]"
                           list="vendor-datalist"
                           value="${vName}"
                           class="w-full text-xs text-slate-700 border-slate-200 rounded-lg py-1 px-2 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="Vendor / Toko...">
                </td>
                <td class="py-2 px-2 text-center">
                    <input type="number" name="items[${itemId}][components][${tempCompId}][qty]"
                           id="comp-qty-${itemId}-${tempCompId}"
                           value="${qty}" min="1" step="1"
                           class="w-14 text-xs font-bold text-center border-slate-200 rounded-lg py-1 px-1 calc-bundle-trigger"
                           data-parent-id="${itemId}">
                    <input type="hidden" name="items[${itemId}][components][${tempCompId}][unit]" value="Unit">
                </td>
                <td class="py-2 px-2">
                    <input type="text" inputmode="numeric" name="items[${itemId}][components][${tempCompId}][hpp]"
                           id="comp-hpp-${itemId}-${tempCompId}"
                           value="${formatRupiah(hpp)}"
                           class="w-full text-xs font-bold text-slate-800 border-slate-200 rounded-lg py-1 px-2 rupiah-input calc-bundle-trigger"
                           data-parent-id="${itemId}" placeholder="0">
                </td>
                <td class="py-2 px-2">
                    <input type="text" inputmode="numeric" name="items[${itemId}][components][${tempCompId}][biaya_kirim]"
                           id="comp-biaya-${itemId}-${tempCompId}"
                           value="${formatRupiah(biaya)}"
                           class="w-full text-xs font-medium text-slate-700 border-slate-200 rounded-lg py-1 px-2 rupiah-input calc-bundle-trigger"
                           data-parent-id="${itemId}" placeholder="0">
                </td>
                <td class="py-2 px-2 text-center">
                    <input type="hidden" name="items[${itemId}][components][${tempCompId}][margin_type]" value="percentage">
                    <input type="text" name="items[${itemId}][components][${tempCompId}][margin_value]"
                           id="comp-margin-${itemId}-${tempCompId}"
                           value="${margin}"
                           class="w-16 text-xs font-bold text-center border-slate-200 rounded-lg py-1 px-1 calc-bundle-trigger"
                           data-parent-id="${itemId}" placeholder="12.5">
                </td>
                <td class="py-2 px-2 text-center">
                    <input type="text" inputmode="numeric" name="items[${itemId}][components][${tempCompId}][custom_ceiling]"
                           id="comp-ceiling-${itemId}-${tempCompId}"
                           value="${formatRupiah(ceiling)}"
                           class="w-16 text-xs text-center border-slate-200 rounded-lg py-1 px-1 rupiah-input calc-bundle-trigger"
                           data-parent-id="${itemId}" placeholder="50.000">
                </td>
                <td class="py-2 px-2 text-right font-mono font-bold text-slate-800" id="comp-saler-${itemId}-${tempCompId}">
                    Rp 0
                </td>
                <td class="py-2 px-2 text-right font-mono font-bold text-indigo-700" id="comp-subtotal-${itemId}-${tempCompId}">
                    Rp 0
                </td>
                <td class="py-2 px-1 text-center">
                    <button type="button" onclick="deleteBundleComponent('${itemId}', '${tempCompId}')"
                        class="p-1 text-rose-500 hover:text-rose-700 hover:bg-rose-50 rounded" title="Hapus Part">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </td>
            </tr>
            `;

            tbody.insertAdjacentHTML('beforeend', rowHtml);
            const newRow = document.getElementById(`comp-row-${itemId}-${tempCompId}`);
            if (newRow) {
                attachRupiahListeners(newRow);
            }
            calculateRow(itemId);
        }

        function deleteBundleComponent(itemId, compId) {
            const row = document.getElementById(`comp-row-${itemId}-${compId}`);
            if (row) {
                row.remove();
                calculateRow(itemId);
            }
        }

        const PC_PRESETS = {
            standar: [
                { product_name: 'Processor Intel Core i5-12400 (6 Core, 12 Thread)', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Motherboard MSI PRO B760M-A WIFI DDR4', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'RAM Team Elite Plus DDR4 16GB (2x8GB) 3200MHz', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'SSD Storage Samsung 990 EVO PLUS NVMe 1TB', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Casing GameMax Spark Air M-ATX', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Power Supply Antec ATOM B750 750W 80+ Bronze', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'CPU Cooler Deepcool AK400 Fan 12CM', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Monitor MSI Pro MP2412 23.8 FHD 100Hz', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Keyboard & Mouse Vention USB Wired Combo', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 }
            ],
            tinggi: [
                { product_name: 'Processor Intel Core i7-14700 (20 Core, 28 Thread)', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Motherboard MSI B760 GAMING PLUS WIFI DDR5', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'RAM Kingston Fury Beast DDR5 32GB (2x16GB) 6000MHz', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'VGA ZOTAC Gaming GeForce RTX 4070 Twin Edge 12GB GDDR6X', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'SSD Storage Samsung 990 PRO NVMe PCIe 4.0 1TB', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'AIO Liquid Cooler Deepcool LT520 240mm ARGB', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Power Supply Corsair RM750e 750W 80+ Gold Fully Modular', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Casing NZXT H5 Flow Tempered Glass Mid Tower', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Monitor LG UltraGear 27GR75Q 27 Inch IPS QHD 165Hz', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Keyboard Mechanical & Gaming Mouse Logitech Combo', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 }
            ],
            workstation: [
                { product_name: 'Processor Intel Core i9-14900K (24 Core, 32 Thread)', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Motherboard ASUS ROG STRIX Z790-F GAMING WIFI II DDR5', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'RAM Corsair Vengeance DDR5 64GB (2x32GB) 6000MHz', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'VGA ASUS TUF Gaming GeForce RTX 4070 Ti SUPER 16GB GDDR6X', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'SSD Storage Samsung 990 PRO NVMe PCIe 4.0 2TB', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'AIO Liquid Cooler Deepcool LT720 360mm ARGB', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Power Supply Seasonic Focus GX-850 850W 80+ Gold Fully Modular', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Casing Fractal Design Meshify 2 Black Solid ATX', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Monitor ASUS ProArt PA278CV 27 Inch IPS QHD 100% sRGB Calibrated', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 },
                { product_name: 'Logitech MX Keys & MX Master 3S Wireless Combo', vendor_name: '', margin_value: 12.5, custom_ceiling: 50000 }
            ]
        };

        function isHardwareLine(text) {
            if (!text || text.length < 3) return false;
            const lower = text.toLowerCase();

            const generalPhrases = [
                'minta pc rakitan', 'pc rakitan spek tinggi', 'butuh pc rakitan',
                'pc spek tinggi', 'komputer spek tinggi', 'spek tinggi',
                'spesifikasi pc', 'buatkan penawaran', 'tolong buatkan',
                'rakit pc gaming', 'rakit pc', 'paket pc'
            ];
            for (let phrase of generalPhrases) {
                if (lower === phrase || lower.startsWith(phrase + ' untuk') || lower.startsWith(phrase + ' dong') || lower.startsWith(phrase + ' ya')) {
                    return false;
                }
            }

            const hwKeywords = [
                'processor', 'intel', 'core i', 'ryzen', 'amd', 'cpu',
                'motherboard', 'mainboard', 'mobo', 'b760', 'z790', 'b650', 'x670', 'h610',
                'ram', 'memory', 'ddr4', 'ddr5', 'sodimm',
                'ssd', 'nvme', 'm.2', 'sata', 'hdd', 'harddisk', 'storage',
                'vga', 'geforce', 'rtx', 'gtx', 'radeon', 'gpu', 'graphic',
                'casing', 'case', 'chassis', 'tower',
                'psu', 'power supply', 'bronze', 'gold', 'watt',
                'cooler', 'cooling', 'fan', 'heatsink', 'liquid', 'aio',
                'monitor', 'display', 'screen', 'ips',
                'keyboard', 'mouse', 'headset', 'webcam', 'speaker', 'ups'
            ];

            return hwKeywords.some(kw => lower.includes(kw));
        }

        function extractPartsFromText(rawText) {
            if (!rawText) return [];
            const lines = rawText.split(/\r?\n/);
            const parts = [];
            lines.forEach(line => {
                let clean = line.trim();
                clean = clean.replace(/^[-+•\d\.\)]+\s{0,}/, '').trim();
                const lower = clean.toLowerCase();
                if (clean.length > 2 
                    && !lower.startsWith('note') 
                    && !lower.startsWith('catatan') 
                    && !lower.startsWith('spesifikasi') 
                    && !lower.startsWith('harga dapat') 
                    && !lower.startsWith('mohon')
                    && isHardwareLine(clean)) {
                    parts.push(clean);
                }
            });
            return parts;
        }

        function addNewPcRakitanItem(categoryName, blockKey, tier = 'standar') {
            const titleMap = {
                standar: 'PC RAKITAN i5-12400 | 16GB | 1TB SSD | NO VGA | NO OS',
                tinggi: 'PC RAKITAN SPEK TINGGI i7-14700 | 32GB DDR5 | RTX 4070 12GB | 1TB NVMe',
                workstation: 'PC WORKSTATION RENDERING i9-14900K | 64GB DDR5 | RTX 4070 Ti SUPER 16GB | 2TB NVMe'
            };

            const title = titleMap[tier] || titleMap['standar'];

            const newItemId = addNewPriceItem(categoryName, blockKey, {
                product_name: title,
                vendor_name: '',
                qty: 1,
                unit: 'Unit',
                description: 'Paket Komputer PC Rakitan',
                is_bundle: true
            });

            if (newItemId) {
                const chk = document.getElementById('bundle-toggle-' + newItemId);
                if (chk) chk.checked = true;
                const panel = document.getElementById('bundle-panel-' + newItemId);
                if (panel) panel.classList.remove('hidden');

                loadPcPreset(newItemId, tier, true);
            }
        }

        function loadComponentsFromSalesSpec(itemId, auto = false) {
            const tbody = document.getElementById('bundle-components-list-' + itemId);
            if (!tbody) return;

            const descText = document.querySelector(`textarea[name="items[${itemId}][description]"]`)?.value || '';
            const rfqNotes = document.getElementById('rfq-notes')?.value || '';
            const sourceText = descText.trim() || rfqNotes.trim();

            const extractedParts = extractPartsFromText(sourceText);

            if (extractedParts.length === 0) {
                if (!auto) {
                    alert('Tidak ada butir rincian part di catatan Sales. Silakan gunakan tombol template PC Standar atau Spek Tinggi di atas.');
                }
                return;
            }

            if (!auto && tbody.children.length > 0) {
                if (!confirm(`Ditemukan ${extractedParts.length} part dari catatan Sales. Tambahkan ke dalam tabel komponen?`)) {
                    return;
                }
            }

            extractedParts.forEach(partName => {
                addBundleComponent(itemId, {
                    product_name: partName,
                    vendor_name: '',
                    qty: 1,
                    margin_value: 12.5,
                    custom_ceiling: 50000
                });
            });

            if (!auto) {
                alert(`Berhasil memuat ${extractedParts.length} komponen part dari catatan spesifikasi Sales!`);
            }
        }

        function loadPcPreset(itemId, tier = 'standar', auto = false) {
            const toggle = document.getElementById('bundle-toggle-' + itemId);
            if (toggle && !toggle.checked) {
                toggle.checked = true;
                toggleBundlePanel(itemId);
            }

            const tbody = document.getElementById('bundle-components-list-' + itemId);
            if (!tbody) return;

            const tierNames = {
                standar: 'Standar (Core i5)',
                tinggi: 'Spek Tinggi (Core i7 + RTX)',
                workstation: 'Workstation / AI (Core i9)'
            };

            const label = tierNames[tier] || tier;

            if (!auto && tbody.children.length > 0) {
                if (!confirm(`Muat template ${label} akan menambahkan komponen part baru ke dalam tabel. Lanjutkan?`)) {
                    return;
                }
            }

            const parts = PC_PRESETS[tier] || PC_PRESETS['standar'];
            parts.forEach(p => {
                addBundleComponent(itemId, p);
            });
        }

        function loadStandardPcPreset(itemId, auto = false) {
            loadPcPreset(itemId, 'standar', auto);
        }

        function calculateRow(itemId) {
            const isBundle = document.getElementById('bundle-toggle-' + itemId)?.checked;
            const hppInput = document.getElementById('hpp-' + itemId);

            if (isBundle) {
                let bundleTotalCost = 0;
                let bundleTotalSelling = 0;
                const compRows = document.querySelectorAll(`#bundle-components-list-${itemId} .bundle-comp-row`);

                compRows.forEach((row, idx) => {
                    const numEl = row.querySelector('.comp-row-num');
                    if (numEl) numEl.innerText = idx + 1;

                    const rowId = row.id.replace(`comp-row-${itemId}-`, '');
                    const qty = parseFloat(document.getElementById(`comp-qty-${itemId}-${rowId}`)?.value) || 1;
                    const hpp = parseRupiah(document.getElementById(`comp-hpp-${itemId}-${rowId}`)?.value);
                    const biaya = parseRupiah(document.getElementById(`comp-biaya-${itemId}-${rowId}`)?.value);
                    const marginVal = parseMarginVal(document.getElementById(`comp-margin-${itemId}-${rowId}`)?.value, 'percentage');
                    const ceiling = parseRupiah(document.getElementById(`comp-ceiling-${itemId}-${rowId}`)?.value) || 50000;

                    const modalUnit = hpp + biaya;
                    let profit = modalUnit * (marginVal / 100);
                    if (modalUnit > 0 && profit < 50000) {
                        profit = 50000;
                    }
                    const salerUnit = Math.ceil((modalUnit + profit) / ceiling) * ceiling;
                    const subtotal = salerUnit * qty;

                    const salerEl = document.getElementById(`comp-saler-${itemId}-${rowId}`);
                    if (salerEl) salerEl.innerText = 'Rp ' + formatRupiah(salerUnit);

                    const subtotalEl = document.getElementById(`comp-subtotal-${itemId}-${rowId}`);
                    if (subtotalEl) subtotalEl.innerText = 'Rp ' + formatRupiah(subtotal);

                    bundleTotalCost += modalUnit * qty;
                    bundleTotalSelling += subtotal;
                });

                const bModalEl = document.getElementById(`bundle-total-modal-${itemId}`);
                if (bModalEl) bModalEl.innerText = 'Rp ' + formatRupiah(bundleTotalCost);

                const bSalerEl = document.getElementById(`bundle-total-saler-${itemId}`);
                if (bSalerEl) bSalerEl.innerText = 'Rp ' + formatRupiah(bundleTotalSelling);

                if (compRows.length > 0) {
                    if (hppInput) {
                        hppInput.value = formatRupiah(bundleTotalCost);
                        hppInput.readOnly = true;
                        hppInput.classList.add('bg-slate-100');
                    }

                    const parentQty = parseFloat(document.getElementById(`qty-${itemId}`)?.value) || 1;
                    const finalPriceUnit = bundleTotalSelling;
                    const finalTotal = finalPriceUnit * parentQty;
                    const rowProfit = (finalPriceUnit - bundleTotalCost) * parentQty;

                    const finalPriceEl = document.getElementById(`final-price-${itemId}`);
                    if (finalPriceEl) finalPriceEl.innerText = formatRupiah(finalPriceUnit);

                    const finalTotalEl = document.getElementById(`final-total-${itemId}`);
                    if (finalTotalEl) finalTotalEl.innerText = formatRupiah(finalTotal);

                    const finalProfitRowEl = document.getElementById(`final-profit-row-${itemId}`);
                    if (finalProfitRowEl) finalProfitRowEl.innerText = '+Rp ' + formatRupiah(rowProfit);

                    const totalModalEl = document.getElementById(`total-modal-${itemId}`);
                    if (totalModalEl) totalModalEl.innerText = formatRupiah(bundleTotalCost);

                    const marginPreviewEl = document.getElementById(`margin-rp-preview-${itemId}`);
                    if (marginPreviewEl) marginPreviewEl.innerText = '+Rp ' + formatRupiah(rowProfit / parentQty);

                    updateGrandTotals();
                    return;
                }
            } else {
                if (hppInput) {
                    hppInput.readOnly = false;
                    hppInput.classList.remove('bg-slate-100');
                }
            }

            const hpp = parseRupiah(document.getElementById('hpp-' + itemId)?.value);
            const ongkirPedia = parseRupiah(document.getElementById('ongkir-pedia-' + itemId)?.value);
            const biayaKirim = parseRupiah(document.getElementById('biaya-kirim-' + itemId)?.value);
            const feeEu = parseRupiah(document.getElementById('fee-eu-' + itemId)?.value);
            
            const marginType = document.getElementById('margin-type-' + itemId)?.value || 'percentage';
            const marginVal = parseMarginVal(document.getElementById('margin-val-' + itemId)?.value, marginType);
            const ceiling = parseRupiah(document.getElementById('ceiling-' + itemId)?.value) || 1;
            const qty = parseFloat(document.getElementById('qty-' + itemId)?.value) || 1;

            // 1. Total Modal Satuan
            const baseModal = hpp + ongkirPedia + biayaKirim + feeEu;
            const totalModalEl = document.getElementById('total-modal-' + itemId);
            if (totalModalEl) totalModalEl.innerText = formatRupiah(baseModal);

            // 2. Margin & Profit
            let priceWithMargin = baseModal;
            let profitRp = 0;
            let isMinApplied = false;
            
            if (marginType === 'nominal') {
                priceWithMargin = baseModal + marginVal;
                profitRp = marginVal;
            } else {
                profitRp = baseModal * (marginVal / 100);
                if (baseModal > 0 && profitRp < 50000) {
                    profitRp = 50000;
                    isMinApplied = true;
                }
                priceWithMargin = baseModal + profitRp;
            }

            const marginPreviewEl = document.getElementById('margin-rp-preview-' + itemId);
            if (marginPreviewEl) {
                marginPreviewEl.innerText = '+Rp ' + formatRupiah(profitRp) + (isMinApplied ? ' (Min. 50k)' : '');
            }

            // 3. Ceiling Pembulatan
            const finalCeiling = ceiling > 0 ? ceiling : 1;
            const finalPriceUnit = Math.ceil(priceWithMargin / finalCeiling) * finalCeiling;
            const finalTotal = finalPriceUnit * qty;
            const rowProfit = (finalPriceUnit - baseModal) * qty;

            // 4. Update UI Item
            const finalPriceEl = document.getElementById('final-price-' + itemId);
            if (finalPriceEl) finalPriceEl.innerText = formatRupiah(finalPriceUnit);

            const finalTotalEl = document.getElementById('final-total-' + itemId);
            if (finalTotalEl) finalTotalEl.innerText = formatRupiah(finalTotal);

            const finalProfitRowEl = document.getElementById('final-profit-row-' + itemId);
            if (finalProfitRowEl) finalProfitRowEl.innerText = '+Rp ' + formatRupiah(rowProfit);

            // 5. Update Active State of Ceiling Buttons
            const buttons = document.querySelectorAll('.ceil-btn-' + itemId);
            buttons.forEach(btn => {
                btn.classList.remove('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-sm');
                btn.classList.add('bg-white', 'text-slate-700', 'border-slate-200');
            });
            const activeBtn = document.getElementById('btn-ceil-' + itemId + '-' + ceiling);
            if (activeBtn) {
                activeBtn.classList.remove('bg-white', 'text-slate-700', 'border-slate-200');
                activeBtn.classList.add('bg-blue-600', 'text-white', 'border-blue-600', 'shadow-sm');
            }

            // 6. Update Bottom Grand Totals
            updateGrandTotals();
        }

        function updateGrandTotals() {
            let grandModal = 0;
            let grandProfit = 0;
            let grandSales = 0;

            document.querySelectorAll('input[id^="hpp-"]').forEach(el => {
                const itemId = el.dataset.itemId || el.id.replace('hpp-', '');
                const hpp = parseRupiah(el.value);
                const ongkirPedia = parseRupiah(document.getElementById('ongkir-pedia-' + itemId)?.value);
                const biayaKirim = parseRupiah(document.getElementById('biaya-kirim-' + itemId)?.value);
                const feeEu = parseRupiah(document.getElementById('fee-eu-' + itemId)?.value);
                const qty = parseFloat(document.getElementById('qty-' + itemId)?.value) || 1;

                const baseModal = (hpp + ongkirPedia + biayaKirim + feeEu) * qty;
                grandModal += baseModal;

                const finalTotalText = document.getElementById('final-total-' + itemId)?.innerText || '0';
                const finalTotal = parseRupiah(finalTotalText);
                grandSales += finalTotal;
            });

            grandProfit = grandSales - grandModal;

            const gmEl = document.getElementById('grand-total-modal');
            if (gmEl) gmEl.innerText = formatRupiah(grandModal);

            const gpEl = document.getElementById('grand-total-profit');
            if (gpEl) gpEl.innerText = formatRupiah(grandProfit);

            const gsEl = document.getElementById('grand-total-sales');
            if (gsEl) gsEl.innerText = formatRupiah(grandSales);
        }

        document.addEventListener('DOMContentLoaded', function() {
            attachRupiahListeners(document);

            // Delegasi event kalkulasi bundle
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('calc-bundle-trigger')) {
                    const parentId = e.target.dataset.parentId;
                    if (parentId) {
                        calculateRow(parentId);
                    }
                }
            });
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('calc-bundle-trigger')) {
                    const parentId = e.target.dataset.parentId;
                    if (parentId) {
                        calculateRow(parentId);
                    }
                }
            });

            // Initial Calculation for all items
            document.querySelectorAll('input[id^="hpp-"]').forEach(function(el) {
                const itemId = el.dataset.itemId || el.id.replace('hpp-', '');
                calculateRow(itemId);
            });

            // Trigger events
            document.querySelectorAll('.calc-trigger').forEach(function(el) {
                el.addEventListener('input', function() {
                    const itemId = this.dataset.itemId || this.id.split('-').pop();
                    calculateRow(itemId);
                });
                el.addEventListener('change', function() {
                    const itemId = this.dataset.itemId || this.id.split('-').pop();
                    calculateRow(itemId);
                });
            });
        });

        // ─── Inline Vendor Addition (Blueprint 3.1) ──────────────────────────
        let activeItemForVendor = null;

        function openInlineVendorModal(itemId, categoryName) {
            activeItemForVendor = itemId;
            const modal = document.getElementById('inline-vendor-modal');
            const errDiv = document.getElementById('inline-vendor-error');
            if (errDiv) {
                errDiv.classList.add('hidden');
                errDiv.innerText = '';
            }
            document.getElementById('inline-vendor-form').reset();

            // Set default category based on current block
            const selectKat = document.getElementById('inline_vendor_kategori');
            if (selectKat) {
                if (categoryName === 'Jasa Pemasangan') {
                    selectKat.value = 'Jasa & Subcon';
                } else if (categoryName === 'Material Support') {
                    selectKat.value = 'General Trading & Office';
                } else {
                    selectKat.value = 'Hardware & IT';
                }
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                document.getElementById('inline_vendor_name').focus();
            }, 60);
        }

        function closeInlineVendorModal() {
            const modal = document.getElementById('inline-vendor-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.getElementById('inline-vendor-form').reset();
            activeItemForVendor = null;
        }

        async function submitInlineVendor(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-save-inline-vendor');
            const errDiv = document.getElementById('inline-vendor-error');
            const originalText = btn.innerHTML;

            const payload = {
                nama_vendor: document.getElementById('inline_vendor_name').value.trim(),
                kategori: document.getElementById('inline_vendor_kategori').value,
                pic: document.getElementById('inline_vendor_pic').value.trim(),
                kontak: document.getElementById('inline_vendor_kontak').value.trim(),
                alamat: document.getElementById('inline_vendor_alamat').value.trim(),
                status: 'Active'
            };

            if (!payload.nama_vendor) {
                errDiv.classList.remove('hidden');
                errDiv.innerText = 'Nama vendor wajib diisi!';
                return;
            }

            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin w-4 h-4 text-white inline-block mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg> Menyimpan...
            `;

            try {
                const response = await fetch('{{ route("vendors.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && (data.success || data.vendor)) {
                    const newVendor = data.vendor;
                    
                    // Update typed vendor input on active item
                    if (activeItemForVendor) {
                        const targetInput = document.getElementById('vendor-input-' + activeItemForVendor);
                        if (targetInput) {
                            targetInput.value = newVendor.nama_vendor;
                        }
                    }

                    // Append to datalist so it appears in suggestions
                    const dl = document.getElementById('vendor-datalist');
                    if (dl) {
                        const opt = document.createElement('option');
                        opt.value = newVendor.nama_vendor;
                        opt.textContent = newVendor.kategori || 'General';
                        dl.appendChild(opt);
                    }

                    closeInlineVendorModal();

                    // Toast notification
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-20 right-6 z-50 bg-emerald-600 text-white text-xs font-semibold px-4 py-3 rounded-xl shadow-xl flex items-center gap-2 animate-in';
                    toast.innerHTML = `
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Vendor <strong>${newVendor.nama_vendor}</strong> berhasil ditambahkan dan dipilih!</span>
                    `;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.classList.add('opacity-0', 'transition-opacity', 'duration-300');
                        setTimeout(() => toast.remove(), 300);
                    }, 3500);
                } else {
                    errDiv.classList.remove('hidden');
                    errDiv.innerText = data.message || 'Gagal menyimpan vendor.';
                }
            } catch (err) {
                console.error(err);
                errDiv.classList.remove('hidden');
                errDiv.innerText = 'Terjadi kesalahan jaringan atau server saat menyimpan vendor.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
    </script>

    {{-- Modal Tambah Vendor Instan (Blueprint 3.1) --}}
    <div id="inline-vendor-modal" class="fixed inset-0 z-50 bg-black/50 hidden items-center justify-center p-4 backdrop-blur-xs">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200 animate-in">
            <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-sm">Tambah Vendor Baru (Inline)</h3>
                        <p class="text-[11px] text-slate-500">Tersimpan ke database &amp; langsung terpilih</p>
                    </div>
                </div>
                <button type="button" onclick="closeInlineVendorModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form id="inline-vendor-form" onsubmit="submitInlineVendor(event)" class="space-y-3.5 text-xs">
                <div>
                    <label class="block font-semibold mb-1 text-slate-700">Nama Perusahaan / Toko Vendor <span class="text-rose-500">*</span></label>
                    <input type="text" id="inline_vendor_name" required placeholder="Contoh: PT. Sumber Solusi IT / Toko Medusa"
                           class="w-full text-xs font-semibold text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs">
                </div>

                <div>
                    <label class="block font-semibold mb-1 text-slate-700">Kategori Vendor <span class="text-rose-500">*</span></label>
                    <select id="inline_vendor_kategori" required class="w-full text-xs text-slate-700 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs">
                        @foreach(\App\Models\Vendor::categories() as $cat)
                            <option value="{{ $cat }}">{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold mb-1 text-slate-700">Nama PIC / Kontak</label>
                        <input type="text" id="inline_vendor_pic" placeholder="Contoh: Pak Fahmi"
                               class="w-full text-xs text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs">
                    </div>
                    <div>
                        <label class="block font-semibold mb-1 text-slate-700">No. HP / Telp</label>
                        <input type="text" id="inline_vendor_kontak" placeholder="0812xxxxxxxx"
                               class="w-full text-xs text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold mb-1 text-slate-700">Alamat / Lokasi (Opsional)</label>
                    <textarea id="inline_vendor_alamat" rows="2" placeholder="Mangga Dua Mall / Glodok / Harco..."
                              class="w-full text-xs text-slate-800 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs resize-none"></textarea>
                </div>

                <div id="inline-vendor-error" class="hidden p-2.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-xs"></div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeInlineVendorModal()" class="px-4 py-2 rounded-xl text-slate-600 bg-slate-100 hover:bg-slate-200 font-semibold transition">
                        Batal
                    </button>
                    <button type="submit" id="btn-save-inline-vendor" class="px-5 py-2 rounded-xl text-white bg-blue-600 hover:bg-blue-700 font-bold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Simpan &amp; Pilih</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <datalist id="vendor-datalist">
        @foreach($vendors as $v)
            <option value="{{ $v->nama_vendor }}">{{ $v->kategori }}</option>
        @endforeach
    </datalist>
</x-app-layout>
