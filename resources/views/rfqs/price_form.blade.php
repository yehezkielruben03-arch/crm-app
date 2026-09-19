<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Isi HPP RFQ: ') }} {{ $rfq->rfq_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('rfq.submit_price', $rfq) }}" method="POST">
                @csrf
                
                @if($rfq->revision_notes)
                <div class="mb-6 p-4 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 shadow-sm animate-in">
                    <div class="flex items-center gap-2 font-bold text-sm text-amber-800 mb-1">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Catatan Revisi dari Leader:
                    </div>
                    <p class="text-xs text-amber-800 leading-relaxed font-medium pl-7">{{ $rfq->revision_notes }}</p>
                </div>
                @endif
                
                @foreach(['Hardware', 'Jasa Pemasangan', 'Material Support'] as $categoryName)
                @php 
                    $categoryItems = $rfq->items->filter(function($it) use ($categoryName) {
                        return \App\Models\RfqItem::normalizeCategory($it->category) === $categoryName;
                    }); 
                @endphp
                
                @if($categoryItems->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-bold mb-4 text-blue-600 border-b pb-2 flex items-center justify-between">
                            <span>Blok {{ $categoryName }}</span>
                            <span class="text-xs bg-blue-100 text-blue-800 font-normal px-2.5 py-1 rounded-full">{{ $categoryItems->count() }} Item</span>
                        </h3>
                        
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3">Item Description</th>
                                        <th class="px-4 py-3 text-center">Qty / Unit</th>
                                        <th class="px-4 py-3">Detail HPP (Modal & Vendor)</th>
                                        <th class="px-4 py-3 w-48">Margin & Ceiling</th>
                                        <th class="px-4 py-3 text-right">Harga Jual / Unit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($categoryItems as $item)
                                    <tr class="border-b hover:bg-gray-50">
                                        <td class="px-4 py-4 align-top">
                                            <input type="hidden" name="items[{{ $item->id }}][category]" value="{{ $categoryName }}">
                                            <input type="hidden" name="items[{{ $item->id }}][ongkir_pedia]" value="{{ (float) $item->ongkir_pedia }}" id="ongkir-pedia-{{ $item->id }}">
                                            
                                            <input type="text" name="items[{{ $item->id }}][product_name]" value="{{ old('items.'.$item->id.'.product_name', $item->product_name) }}" class="w-full text-sm border-gray-300 rounded-md mb-2 font-bold" required>
                                            <textarea name="items[{{ $item->id }}][description]" rows="2" class="w-full text-xs border-gray-300 rounded-md mb-2" placeholder="Deskripsi Singkat">{{ old('items.'.$item->id.'.description', $item->description) }}</textarea>
                                            <textarea name="items[{{ $item->id }}][detail_item]" rows="2" class="w-full text-xs border-gray-300 rounded-md" placeholder="Spesifikasi Detail">{{ old('items.'.$item->id.'.detail_item', $item->detail_item) }}</textarea>
                                        </td>
                                        
                                        <td class="px-4 py-4 align-top text-center">
                                            <input type="number" name="items[{{ $item->id }}][qty]" id="qty-{{ $item->id }}" value="{{ old('items.'.$item->id.'.qty', (int) $item->qty) }}" class="w-20 text-center border-gray-300 rounded-md mb-2 calc-trigger" required min="1">
                                            <input type="text" name="items[{{ $item->id }}][unit]" value="{{ old('items.'.$item->id.'.unit', $item->unit) }}" class="w-20 text-center border-gray-300 rounded-md" placeholder="Unit">
                                        </td>
                                        
                                        <td class="px-4 py-4 align-top">
                                            {{-- Pilihan Vendor Supplier untuk semua kategori --}}
                                            <div class="mb-2">
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="text-xs text-gray-600 font-medium block">Vendor Supplier</label>
                                                    @if($categoryName == 'Jasa Pemasangan')
                                                        <button type="button" onclick="useMpPedia('{{ $item->id }}')" class="bg-blue-100 text-blue-700 text-[11px] px-2 py-0.5 rounded border border-blue-300 hover:bg-blue-200 font-semibold transition">
                                                            ⚡ Gunakan Tarif MP (Rp {{ number_format($mpPediaRate, 0, ',', '.') }})
                                                        </button>
                                                    @endif
                                                </div>
                                                <select name="items[{{ $item->id }}][vendor_id]" class="w-full text-xs border-gray-300 rounded-md">
                                                    <option value="">-- Pilih Vendor Supplier --</option>
                                                    @foreach($vendors as $v)
                                                        <option value="{{ $v->id }}" {{ $item->vendor_id == $v->id ? 'selected' : '' }}>
                                                            {{ $v->nama_vendor }} ({{ $v->kategori }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="text-xs text-gray-600 font-medium block">HPP Dasar / Modal (Rp)</label>
                                                <input type="number" name="items[{{ $item->id }}][hpp]" id="hpp-{{ $item->id }}" value="{{ old('items.'.$item->id.'.hpp', (float) $item->hpp) }}" class="w-full text-sm border-gray-300 rounded-md calc-trigger font-semibold" required min="0" placeholder="0">
                                            </div>
                                            
                                            <div class="flex gap-2 mb-2">
                                                <div class="w-1/2">
                                                    <label class="text-xs text-gray-700 font-semibold block">Ongkir dari Pedia</label>
                                                    <input type="number" name="items[{{ $item->id }}][biaya_kirim]" id="biaya-kirim-{{ $item->id }}" value="{{ old('items.'.$item->id.'.biaya_kirim', (float) (($item->biaya_kirim && $item->biaya_kirim > 0) ? $item->biaya_kirim : ($rfq->customer->ongkir_pedia ?? 0))) }}" class="w-full text-sm border-gray-300 rounded-md calc-trigger" min="0">
                                                </div>
                                                <div class="w-1/2">
                                                    <label class="text-xs text-gray-500 block">Fee EU</label>
                                                    <input type="number" name="items[{{ $item->id }}][fee_eu]" id="fee-eu-{{ $item->id }}" value="{{ old('items.'.$item->id.'.fee_eu', (float) ($item->fee_eu ?? 0)) }}" class="w-full text-sm border-gray-300 rounded-md calc-trigger" min="0">
                                                </div>
                                            </div>
                                            
                                            <div class="text-xs text-blue-600 font-semibold bg-blue-50 p-2 rounded border border-blue-100">
                                                Total Modal: Rp <span id="total-modal-{{ $item->id }}">0</span>
                                                @if(($rfq->customer->ongkir_pedia ?? 0) > 0)
                                                    <br><small class="text-gray-500 font-normal">(Termasuk Ongkir Customer Rp {{ number_format($rfq->customer->ongkir_pedia, 0, ',', '.') }})</small>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-4 align-top">
                                            <div class="flex gap-2 mb-2">
                                                <div class="w-1/3">
                                                    <label class="text-xs text-gray-500 block">Tipe</label>
                                                    <select name="items[{{ $item->id }}][margin_type]" id="margin-type-{{ $item->id }}" class="w-full text-xs border-gray-300 rounded-md calc-trigger">
                                                        <option value="percentage" {{ ($item->margin_type ?? 'percentage') == 'percentage' ? 'selected' : '' }}>%</option>
                                                        <option value="nominal" {{ ($item->margin_type ?? 'percentage') == 'nominal' ? 'selected' : '' }}>Rp</option>
                                                    </select>
                                                </div>
                                                <div class="w-2/3">
                                                    <label class="text-xs text-gray-500 block">Nilai Margin</label>
                                                    <input type="number" name="items[{{ $item->id }}][margin_value]" id="margin-val-{{ $item->id }}" value="{{ old('items.'.$item->id.'.margin_value', (float) ($item->margin_value ?? $item->margin ?? 0)) }}" class="w-full text-sm border-gray-300 rounded-md calc-trigger" required min="0" step="any">
                                                </div>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="text-xs text-gray-500 block">Estimasi Untung (Rp)</label>
                                                <input type="text" id="margin-rp-preview-{{ $item->id }}" class="w-full text-sm border-gray-100 bg-gray-50 rounded-md font-medium text-green-700" readonly>
                                            </div>
                                            
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="text-xs text-gray-500 block">Pembulatan (Ceiling)</label>
                                                </div>
                                                <div class="flex gap-1 mb-1.5">
                                                    <button type="button" onclick="setCeiling('{{ $item->id }}', 1)" class="px-2 py-0.5 text-[11px] bg-gray-100 hover:bg-gray-200 rounded border border-gray-300 text-gray-700">1 (Bebas)</button>
                                                    <button type="button" onclick="setCeiling('{{ $item->id }}', 1000)" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 rounded border border-blue-200 text-blue-700 font-semibold">1.000 (Ribuan)</button>
                                                    <button type="button" onclick="setCeiling('{{ $item->id }}', 10000)" class="px-2 py-0.5 text-[11px] bg-blue-50 hover:bg-blue-100 rounded border border-blue-200 text-blue-700 font-semibold">10.000 (Puluh Rb)</button>
                                                </div>
                                                <input type="number" name="items[{{ $item->id }}][custom_ceiling]" id="ceiling-{{ $item->id }}" value="{{ old('items.'.$item->id.'.custom_ceiling', (float) ($item->custom_ceiling ?? $item->ceiling ?? 1)) }}" class="w-full text-sm border-gray-300 rounded-md calc-trigger" min="0" placeholder="1000">
                                            </div>
                                        </td>
                                        
                                        <td class="px-4 py-4 align-middle text-right">
                                            <div class="text-xl font-bold text-green-600">
                                                Rp <span id="final-price-{{ $item->id }}">0</span>
                                            </div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Total Row: Rp <span id="final-total-{{ $item->id }}">0</span>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                    </div>
                </div>
                @endif
                @endforeach

                {{-- Riwayat Penawaran (History Revisi - Blueprint 1.5) --}}
                @if(isset($priceHistories) && $priceHistories->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border border-amber-200">
                    <div class="p-6 bg-amber-50/40 border-b border-amber-100">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-base font-bold text-amber-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Riwayat Versi Penawaran (History Revisi)
                            </h3>
                            <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2.5 py-0.5 rounded-full">{{ $priceHistories->count() }} Versi Tercatat</span>
                        </div>
                        <p class="text-xs text-amber-700 mb-4">Setiap kali penawaran direvisi atau harga diubah, sistem mencatat snapshot untuk membandingkan perubahan harga.</p>

                        <div class="space-y-3">
                            @foreach($priceHistories as $history)
                            <div class="bg-white p-4 rounded-lg border border-amber-200/70 shadow-sm">
                                <div class="flex justify-between items-center mb-2 pb-2 border-b border-gray-100">
                                    <div>
                                        <span class="font-bold text-sm text-gray-800">Revisi {{ $history->version }}</span>
                                        <span class="text-xs text-gray-500 ml-2">{{ $history->created_at->format('d M Y, H:i') }}</span>
                                    </div>
                                    <div class="text-xs text-gray-600">
                                        Disimpan oleh: <strong class="text-gray-800">{{ $history->creator->name ?? 'Admin' }}</strong>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs text-left">
                                        <thead class="bg-gray-50 text-gray-500">
                                            <tr>
                                                <th class="px-2 py-1">Nama Item</th>
                                                <th class="px-2 py-1 text-center">Qty</th>
                                                <th class="px-2 py-1 text-right">Modal Dasar (HPP)</th>
                                                <th class="px-2 py-1 text-center">Margin</th>
                                                <th class="px-2 py-1 text-right">Harga Jual / Unit</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100">
                                            @if(is_array($history->history_data))
                                                @foreach($history->history_data as $hItem)
                                                <tr>
                                                    <td class="px-2 py-1 font-medium text-gray-700">{{ $hItem['product_name'] ?? '-' }}</td>
                                                    <td class="px-2 py-1 text-center text-gray-600">{{ (int)($hItem['qty'] ?? 1) }} {{ $hItem['unit'] ?? '' }}</td>
                                                    <td class="px-2 py-1 text-right text-gray-600">Rp {{ number_format((float)($hItem['hpp'] ?? 0), 0, ',', '.') }}</td>
                                                    <td class="px-2 py-1 text-center text-gray-600">
                                                        {{ ($hItem['margin_type'] ?? 'percentage') === 'nominal' ? 'Rp ' . number_format((float)($hItem['margin_value'] ?? 0), 0, ',', '.') : ($hItem['margin_value'] ?? $hItem['margin'] ?? 0) . '%' }}
                                                    </td>
                                                    <td class="px-2 py-1 text-right font-bold text-gray-800">Rp {{ number_format((float)($hItem['price_after_margin'] ?? 0), 0, ',', '.') }}</td>
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
                </div>
                @endif
                
                <div class="flex justify-end mt-4 mb-8">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg shadow-lg transition duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Submit HPP ke Leader
                    </button>
                </div>
            </form>
        </div>
    </div>

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
            return new Intl.NumberFormat('id-ID').format(number);
        }

        function calculateRow(itemId) {
            const hpp = parseFloat(document.getElementById('hpp-' + itemId).value) || 0;
            const ongkirPedia = parseFloat(document.getElementById('ongkir-pedia-' + itemId).value) || 0;
            const biayaKirim = parseFloat(document.getElementById('biaya-kirim-' + itemId).value) || 0;
            const feeEu = parseFloat(document.getElementById('fee-eu-' + itemId).value) || 0;
            
            const marginType = document.getElementById('margin-type-' + itemId).value;
            const marginVal = parseFloat(document.getElementById('margin-val-' + itemId).value) || 0;
            const ceiling = parseFloat(document.getElementById('ceiling-' + itemId).value) || 1;
            const qty = parseFloat(document.getElementById('qty-' + itemId).value) || 1;

            // 1. Hitung Modal Dasar
            const baseModal = hpp + ongkirPedia + biayaKirim + feeEu;
            document.getElementById('total-modal-' + itemId).innerText = formatRupiah(baseModal);

            // 2. Hitung Margin
            let priceWithMargin = baseModal;
            let profitRp = 0;
            
            if (marginType === 'nominal') {
                priceWithMargin = baseModal + marginVal;
                profitRp = marginVal;
            } else {
                profitRp = baseModal * (marginVal / 100);
                priceWithMargin = baseModal + profitRp;
            }
            
            document.getElementById('margin-rp-preview-' + itemId).value = formatRupiah(profitRp);

            // 3. Hitung Ceiling (Pembulatan)
            const finalCeiling = ceiling > 0 ? ceiling : 1;
            const finalPriceUnit = Math.ceil(priceWithMargin / finalCeiling) * finalCeiling;
            
            // 4. Update UI
            document.getElementById('final-price-' + itemId).innerText = formatRupiah(finalPriceUnit);
            document.getElementById('final-total-' + itemId).innerText = formatRupiah(finalPriceUnit * qty);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const triggers = document.querySelectorAll('.calc-trigger');
            
            // Initial Calculation
            document.querySelectorAll('input[name*="[hpp]"]').forEach(function(el) {
                const itemId = el.id.replace('hpp-', '');
                calculateRow(itemId);
            });

            // Listeners
            triggers.forEach(function(el) {
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
