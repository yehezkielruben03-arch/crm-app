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
                        @if(($rfq->customer->ongkir_pedia ?? 0) > 0)
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-blue-600 bg-blue-50 px-2 py-0.5 rounded text-[11px] font-medium border border-blue-100 flex items-center gap-1">
                                🚚 Ongkir Default Customer: <b>Rp {{ number_format($rfq->customer->ongkir_pedia, 0, ',', '.') }}</b>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs font-medium text-slate-500">Total Item: <strong class="text-slate-800">{{ $rfq->items->count() }}</strong></span>
                <a href="{{ route('rfq.show', $rfq) }}" class="px-4 py-2 text-xs font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 shadow-xs transition">
                    Batal
                </a>
            </div>
        </div>

        <form action="{{ route('rfq.submit_price', $rfq) }}" method="POST">
            @csrf
            
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
                if ($rfq->type === 'Non Projek') {
                    $blocks[] = [
                        'title' => 'Daftar Item Permintaan Non-Projek',
                        'items' => $rfq->items,
                        'categoryName' => null,
                    ];
                } else {
                    foreach (['Hardware', 'Jasa Pemasangan', 'Material Support'] as $cat) {
                        $catItems = $rfq->items->filter(function($it) use ($cat) {
                            return \App\Models\RfqItem::normalizeCategory($it->category) === $cat;
                        });
                        if ($catItems->count() > 0) {
                            $blocks[] = [
                                'title' => 'Blok ' . $cat,
                                'items' => $catItems,
                                'categoryName' => $cat,
                            ];
                        }
                    }
                    $uncat = $rfq->items->filter(function($it) {
                        return empty($it->category);
                    });
                    if ($uncat->count() > 0) {
                        $blocks[] = [
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
            @endphp
            
            @if($categoryItems->count() > 0)
            <div class="mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-slate-800 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                        {{ $block['title'] }}
                    </h2>
                    <span class="text-xs bg-slate-100 text-slate-700 font-semibold px-2.5 py-1 rounded-full border border-slate-200">
                        {{ $categoryItems->count() }} Item
                    </span>
                </div>
                        
                <div class="space-y-5">
                    @foreach($categoryItems as $item)
                    <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden" id="card-item-{{ $item->id }}">
                        
                        {{-- Card Header --}}
                        <div class="px-6 py-3.5 bg-gradient-to-r from-slate-50 via-slate-50/50 to-white border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold text-xs flex items-center justify-center shadow-xs">
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
                                    </h3>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="px-3 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 shadow-2xs">
                                    Qty Target: <span class="text-blue-600 font-bold" id="badge-qty-{{ $item->id }}">{{ (int)$item->qty }}</span> {{ $item->unit ?? 'Unit' }}
                                </div>
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
                                            <label class="text-[11px] font-semibold text-slate-600 block">Vendor Supplier</label>
                                            @if($categoryName == 'Jasa Pemasangan' || str_contains(strtolower($item->product_name ?? ''), 'jasa') || str_contains(strtolower($item->product_name ?? ''), 'instalasi') || str_contains(strtolower($item->product_name ?? ''), 'pasang'))
                                                <button type="button" onclick="useMpPedia('{{ $item->id }}')" class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 text-[10px] px-2 py-0.5 rounded-md border border-amber-300 hover:bg-amber-100 font-semibold transition shadow-2xs" title="Tarik tarif standar teknisi dari Portal Mainpower">
                                                    ⚡ Tarif MP Pedia (Rp {{ number_format($mpPediaRate, 0, ',', '.') }})
                                                </button>
                                            @endif
                                        </div>
                                        <select name="items[{{ $item->id }}][vendor_id]" class="w-full text-xs text-slate-700 border-slate-200 rounded-xl focus:border-blue-500 focus:ring-blue-500 shadow-2xs">
                                            <option value="">-- Tanpa Vendor / Non-Vendor --</option>
                                            @foreach($vendors as $v)
                                                <option value="{{ $v->id }}" {{ $item->vendor_id == $v->id ? 'selected' : '' }}>
                                                    {{ $v->nama_vendor }} ({{ $v->kategori }})
                                                </option>
                                            @endforeach
                                        </select>
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

                                    <div class="space-y-2">
                                        <div>
                                            <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Deskripsi Singkat</label>
                                            <textarea name="items[{{ $item->id }}][description]" rows="1"
                                                      class="w-full text-xs border-slate-200 rounded-xl resize-none focus:border-blue-500 focus:ring-blue-500 shadow-2xs"
                                                      placeholder="Deskripsi singkat barang...">{{ old('items.'.$item->id.'.description', $item->description) }}</textarea>
                                        </div>
                                        <div>
                                            <label class="text-[11px] font-semibold text-slate-600 mb-1 block">Spesifikasi Detail</label>
                                            <textarea name="items[{{ $item->id }}][detail_item]" rows="2"
                                                      class="w-full text-xs border-slate-200 rounded-xl resize-none focus:border-blue-500 focus:ring-blue-500 shadow-2xs font-mono text-[11px]"
                                                      placeholder="Part number, spesifikasi teknis...">{{ old('items.'.$item->id.'.detail_item', $item->detail_item) }}</textarea>
                                        </div>
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
                                            <input type="number" name="items[{{ $item->id }}][hpp]" id="hpp-{{ $item->id }}"
                                                   value="{{ old('items.'.$item->id.'.hpp', (float) $item->hpp) }}"
                                                   class="w-full pl-9 pr-3 text-sm font-bold text-slate-800 border-slate-200 rounded-xl calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                                   required min="0" placeholder="0">
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
                                                <input type="number" name="items[{{ $item->id }}][biaya_kirim]" id="biaya-kirim-{{ $item->id }}"
                                                       value="{{ old('items.'.$item->id.'.biaya_kirim', (float) ($item->biaya_kirim ?? 0)) }}"
                                                       class="w-full pl-9 pr-3 text-xs font-semibold text-slate-800 border-slate-200 rounded-xl calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                                       min="0" placeholder="0">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="text-[11px] font-semibold text-blue-700 mb-1 flex items-center justify-between">
                                                <span>Ongkir dari Pedia <span class="text-[10px] text-blue-500 font-normal">(ke Customer)</span></span>
                                                <span class="text-[10px] bg-blue-100 px-1.5 py-0.5 rounded text-blue-700 font-mono font-medium">Outbound</span>
                                            </label>
                                            <div class="relative rounded-xl shadow-2xs">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-blue-400">Rp</span>
                                                <input type="number" name="items[{{ $item->id }}][ongkir_pedia]" id="ongkir-pedia-{{ $item->id }}"
                                                       value="{{ old('items.'.$item->id.'.ongkir_pedia', (float) (($item->ongkir_pedia && $item->ongkir_pedia > 0) ? $item->ongkir_pedia : ($rfq->customer->ongkir_pedia ?? 0))) }}"
                                                       class="w-full pl-9 pr-3 text-xs font-semibold text-blue-900 bg-blue-50/50 border-blue-200 rounded-xl calc-trigger focus:border-blue-500 focus:ring-blue-500"
                                                       min="0">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="text-[11px] font-semibold text-slate-500 mb-1 block flex items-center justify-between">
                                                <span>Fee End-User <span class="text-[10px] text-slate-400 font-normal">(Opsional)</span></span>
                                            </label>
                                            <div class="relative rounded-xl shadow-2xs">
                                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-xs font-medium text-slate-400">Rp</span>
                                                <input type="number" name="items[{{ $item->id }}][fee_eu]" id="fee-eu-{{ $item->id }}"
                                                       value="{{ old('items.'.$item->id.'.fee_eu', (float) ($item->fee_eu ?? 0)) }}"
                                                       class="w-full pl-9 pr-3 text-xs font-medium text-slate-700 border-slate-200 rounded-xl calc-trigger focus:border-indigo-500 focus:ring-indigo-500"
                                                       min="0" placeholder="0">
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
                                                <select name="items[{{ $item->id }}][margin_type]" id="margin-type-{{ $item->id }}"
                                                        class="text-xs font-bold text-slate-700 border-0 bg-slate-50 focus:ring-0 w-16 py-1.5 pl-2 pr-6 calc-trigger">
                                                    <option value="percentage" {{ ($item->margin_type ?? 'percentage') == 'percentage' ? 'selected' : '' }}>%</option>
                                                    <option value="nominal" {{ ($item->margin_type ?? 'percentage') == 'nominal' ? 'selected' : '' }}>Rp</option>
                                                </select>
                                                <input type="number" name="items[{{ $item->id }}][margin_value]" id="margin-val-{{ $item->id }}"
                                                       value="{{ old('items.'.$item->id.'.margin_value', (float) (($item->margin_value && $item->margin_value > 0) ? $item->margin_value : (($item->margin && $item->margin > 0) ? $item->margin : 12.5))) }}"
                                                       class="w-full text-xs font-bold text-right border-0 border-l border-slate-200 focus:ring-0 py-1.5 pr-2.5 calc-trigger"
                                                       required min="0" step="any">
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
                                            <input type="number" name="items[{{ $item->id }}][custom_ceiling]" id="ceiling-{{ $item->id }}"
                                                   value="{{ old('items.'.$item->id.'.custom_ceiling', (float) ($item->custom_ceiling ?? $item->ceiling ?? 50000)) }}"
                                                   class="w-full pl-9 pr-3 py-1.5 text-xs font-semibold text-slate-800 border-slate-200 rounded-xl calc-trigger focus:border-blue-500 focus:ring-blue-500"
                                                   min="0" placeholder="Custom ceiling">
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
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
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
                                <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-600 text-white">Revisi {{ $history->version }}</span>
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
        
        function useMpPedia(itemId) {
            document.getElementById('hpp-' + itemId).value = mpPediaRate;
            calculateRow(itemId);
        }

        function setCeiling(itemId, val) {
            const input = document.getElementById('ceiling-' + itemId);
            if (input) {
                input.value = val;
                calculateRow(itemId);
            }
        }

        function formatRupiah(number) {
            return new Intl.NumberFormat('id-ID').format(Math.round(number));
        }

        function calculateRow(itemId) {
            const hpp = parseFloat(document.getElementById('hpp-' + itemId)?.value) || 0;
            const ongkirPedia = parseFloat(document.getElementById('ongkir-pedia-' + itemId)?.value) || 0;
            const biayaKirim = parseFloat(document.getElementById('biaya-kirim-' + itemId)?.value) || 0;
            const feeEu = parseFloat(document.getElementById('fee-eu-' + itemId)?.value) || 0;
            
            const marginType = document.getElementById('margin-type-' + itemId)?.value || 'percentage';
            const marginVal = parseFloat(document.getElementById('margin-val-' + itemId)?.value) || 0;
            const ceiling = parseFloat(document.getElementById('ceiling-' + itemId)?.value) || 1;
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

            document.querySelectorAll('input[name*="[hpp]"]').forEach(el => {
                const itemId = el.id.replace('hpp-', '');
                const hpp = parseFloat(el.value) || 0;
                const ongkirPedia = parseFloat(document.getElementById('ongkir-pedia-' + itemId)?.value) || 0;
                const biayaKirim = parseFloat(document.getElementById('biaya-kirim-' + itemId)?.value) || 0;
                const feeEu = parseFloat(document.getElementById('fee-eu-' + itemId)?.value) || 0;
                const qty = parseFloat(document.getElementById('qty-' + itemId)?.value) || 1;

                const baseModal = (hpp + ongkirPedia + biayaKirim + feeEu) * qty;
                grandModal += baseModal;

                const finalTotalText = document.getElementById('final-total-' + itemId)?.innerText.replace(/\./g, '') || '0';
                const finalTotal = parseFloat(finalTotalText) || 0;
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
            // Initial Calculation for all items
            document.querySelectorAll('input[name*="[hpp]"]').forEach(function(el) {
                const itemId = el.id.replace('hpp-', '');
                calculateRow(itemId);
            });

            // Trigger events
            document.querySelectorAll('.calc-trigger').forEach(function(el) {
                el.addEventListener('input', function() {
                    const itemId = this.id.split('-').pop();
                    calculateRow(itemId);
                });
                el.addEventListener('change', function() {
                    const itemId = this.id.split('-').pop();
                    calculateRow(itemId);
                });
            });
        });
    </script>
</x-app-layout>
