<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Quotation {{ $rfq->quotation_number }}</title>
    <!-- Gunakan CDN untuk preview karena ini halaman terpisah dari app layout -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-gray-200 flex flex-col items-center py-8">

    <!-- Tombol Action (Tidak ikut tercetak saat di-print/PDF) -->
    <div class="no-print mb-6 flex gap-4 w-[210mm] justify-between items-center">
        <a href="{{ route('rfq.show', $rfq->id) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded shadow-md text-sm font-bold transition flex items-center">
            &larr; Kembali ke Detail RFQ
        </a>
        <div class="flex items-center gap-3">
            @if(in_array($rfq->status, [\App\Models\Rfq::STATUS_APPROVED, \App\Models\Rfq::STATUS_QUOTATION_CREATED]))
            <form action="{{ route('rfq.mark_quotation_sent', $rfq->id) }}" method="POST" class="inline m-0">
                @csrf
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded shadow-md text-sm font-bold transition flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Mark Quotation Sent to Client
                </button>
            </form>
            @elseif($rfq->status === \App\Models\Rfq::STATUS_QUOTATION_SENT)
            <div class="bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs font-bold px-3 py-2 rounded flex items-center">
                <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Quotation Sent to Client
            </div>
            @endif
            <a href="{{ route('rfq.download_quotation', $rfq->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow-md text-sm font-bold transition flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download PDF
            </a>
        </div>
    </div>

    <!-- Kertas A4 -->
    <div class="bg-white w-[210mm] min-h-[297mm] p-10 shadow-xl font-sans text-sm relative text-gray-800" id="quotation-document">
        
        <!-- HEADER -->
        <div class="flex justify-between items-start border-b-2 border-transparent pb-4">
            <!-- Kiri: Logo & Tagline -->
            <div class="w-1/2">
                <img src="{{ asset('images/logo.png') }}" alt="Pedia Technology" class="h-16 mb-2 object-contain" onerror="this.src='https://placehold.co/180x60?text=Logo+Pedia'">
                <h1 class="font-bold text-base uppercase leading-tight">PT. PEDIA TEKNOLOGI INDONESIA</h1>
                <p class="text-[11px] italic text-gray-600">With Our Experience Everything Is Possible</p>
            </div>
            <!-- Kanan: Judul Quotation -->
            <div class="w-1/2 text-right">
                <h2 class="text-[42px] text-gray-500 tracking-wide font-medium leading-none mb-6">Quotation</h2>
                <div class="grid grid-cols-[1fr_auto] gap-x-4 text-xs font-medium text-right ml-auto w-max">
                    <span class="text-gray-700">TANGGAL</span>
                    <span>{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d M Y') }}</span>
                    <span class="text-gray-700">No. Penawaran</span>
                    <span class="font-bold">{{ $rfq->quotation_number }}</span>
                    
                    @if(isset($revisionCount) && $revisionCount > 0)
                    <span></span>
                    <span class="text-red-600 font-bold mt-1">REVISI {{ $revisionCount }} : {{ date('d M Y') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- INFO KLIEN & VALIDITY -->
        <div class="flex justify-between items-end mt-4 mb-3 text-xs">
            <div class="leading-snug">
                <p>Kepada YTH / <span class="italic text-gray-500">To:</span></p>
                <p class="font-bold text-base mt-1">{{ $rfq->customer->name ?? '-' }}</p>
                <p class="whitespace-pre-line">{{ $rfq->customer->address ?? '-' }}</p>
                <p>Telp : {{ $rfq->customer->phone ?? '-' }}</p>
                <p>Up. / <span class="italic text-gray-500">Attn:</span> {{ $rfq->customerContact->name ?? 'Bpk/Ibu' }}</p>
            </div>
            
            <div class="flex bg-gray-100 px-3 py-1 w-64 items-center justify-between border-b border-gray-300">
                <span class="italic text-gray-700">Berlaku s/d tgl :</span>
                <span class="font-medium">{{ \Carbon\Carbon::parse($rfq->rfq_date)->addDays(7)->format('d M Y') }}</span>
            </div>
        </div>

        <p class="text-xs mb-1">Berikut penawaran dari kami / <span class="italic text-gray-500">We are pleased to submit our quotation as follows:</span></p>

        <!-- TABEL RINCIAN -->
        <table class="w-full text-xs border-collapse border border-gray-400 mb-4">
            <thead class="bg-gray-100 text-center font-bold">
                <tr>
                    <th class="border border-gray-400 px-2 py-1.5 w-16">JUMLAH</th>
                    <th class="border border-gray-400 px-2 py-1.5">DESKRIPSI</th>
                    <th class="border border-gray-400 px-2 py-1.5 w-28">HARGA</th>
                    <th class="border border-gray-400 px-2 py-1.5 w-28">JUMLAH HARGA</th>
                </tr>
            </thead>
            <tbody class="align-top">
                @php 
                    $grandSubtotal = 0;
                    $groups = $rfq->items->groupBy('category');
                @endphp
                
                @foreach(['Hardware', 'Jasa Pemasangan', 'Material Support'] as $catName)
                    @if(isset($groups[$catName]) && $groups[$catName]->count() > 0)
                        
                        @foreach($groups[$catName] as $item)
                            @php
                                $unitPrice = $item->price_after_margin;
                                $rowTotal = $unitPrice * $item->qty;
                                $grandSubtotal += $rowTotal;
                            @endphp
                            <!-- Baris Data -->
                            <tr>
                                <td class="border-l border-r border-gray-400 px-2 py-1.5 text-center font-bold">{{ (int) $item->qty }} {{ $item->unit }}</td>
                                <td class="border-l border-r border-gray-400 px-2 py-1.5">
                                    <strong class="text-[13px]">{{ $item->product_name }}</strong><br>
                                    <div class="mt-1 leading-relaxed whitespace-pre-line text-gray-700">{!! e($item->detail_item ?? $item->description) !!}</div>
                                </td>
                                <td class="border-l border-r border-gray-400 px-2 py-1.5 font-bold">
                                    <div class="flex justify-between"><span>Rp</span><span>{{ number_format($unitPrice, 0, ',', '.') }}</span></div>
                                </td>
                                <td class="border-l border-r border-gray-400 px-2 py-1.5 font-bold">
                                    <div class="flex justify-between"><span>Rp</span><span>{{ number_format($rowTotal, 0, ',', '.') }}</span></div>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach

                @php
                    $handledCats = ['Hardware', 'Jasa Pemasangan', 'Material Support'];
                    $otherItems = $rfq->items->filter(function($item) use ($handledCats) {
                        return !in_array($item->category, $handledCats);
                    });
                @endphp
                @if($otherItems->count() > 0)
                    @foreach($otherItems as $item)
                        @php
                            $unitPrice = $item->price_after_margin;
                            $rowTotal = $unitPrice * $item->qty;
                            $grandSubtotal += $rowTotal;
                        @endphp
                        <tr>
                            <td class="border-l border-r border-gray-400 px-2 py-1.5 text-center font-bold">{{ (int) $item->qty }} {{ $item->unit }}</td>
                            <td class="border-l border-r border-gray-400 px-2 py-1.5">
                                <strong class="text-[13px]">{{ $item->product_name }}</strong><br>
                                <div class="mt-1 leading-relaxed whitespace-pre-line text-gray-700">{!! e($item->detail_item ?? $item->description) !!}</div>
                            </td>
                            <td class="border-l border-r border-gray-400 px-2 py-1.5 font-bold">
                                <div class="flex justify-between"><span>Rp</span><span>{{ number_format($unitPrice, 0, ',', '.') }}</span></div>
                            </td>
                            <td class="border-l border-r border-gray-400 px-2 py-1.5 font-bold">
                                <div class="flex justify-between"><span>Rp</span><span>{{ number_format($rowTotal, 0, ',', '.') }}</span></div>
                            </td>
                        </tr>
                    @endforeach
                @endif
                
                <!-- Bagian Note Merah (Selalu ada di akhir baris deskripsi) -->
                <tr>
                    <td class="border-l border-r border-b border-gray-400 px-2"></td>
                    <td class="border-l border-r border-b border-gray-400 px-2 pb-4 leading-tight">
                        <div class="font-bold mb-1 mt-2">Note :</div>
                        <div class="text-red-600 font-bold italic">- Unit READY LIMITED stok tidak mengikat / <span class="font-normal text-gray-600">Stock is limited and subject to prior sales</span></div>
                        <div class="italic">- Harga dapat berubah tanpa pemberitahuan / <span class="text-gray-600">Prices subject to change without prior notice</span></div>
                        <div class="italic">- Mohon konfirmasi ketersediaan stok sebelum mengirim PO / <span class="text-gray-600">Please confirm stock availability before issuing PO</span></div>
                    </td>
                    <td class="border-l border-r border-b border-gray-400 px-2"></td>
                    <td class="border-l border-r border-b border-gray-400 px-2"></td>
                </tr>
                
                <!-- TOTAL FOOTER -->
                @php
                    $ppn = $grandSubtotal * 0.11;
                    $grandTotal = $grandSubtotal + $ppn;
                    $totalQty = $rfq->items->sum('qty');
                @endphp
                <tr class="font-bold">
                    <td class="border-x border-gray-400 px-2 py-1.5 text-center bg-gray-50">{{ (int) $totalQty }} Item</td>
                    <td class="border-x border-gray-400 px-2 py-1.5 text-right bg-gray-50">TOTAL</td>
                    <td class="border-x border-gray-400 px-2 py-1.5 bg-gray-50"></td>
                    <td class="border-x border-gray-400 px-2 py-1.5 bg-gray-50"><div class="flex justify-between"><span>Rp</span><span>{{ number_format($grandSubtotal, 0, ',', '.') }}</span></div></td>
                </tr>
                <tr class="border-t border-gray-400 font-bold">
                    <td colspan="2" class="border-x border-gray-400 px-2 py-1.5 text-right bg-gray-50">PPn 11%</td>
                    <td class="border-x border-gray-400 px-2 py-1.5 bg-gray-50"></td>
                    <td class="border-x border-gray-400 px-2 py-1.5 bg-gray-50"><div class="flex justify-between"><span>Rp</span><span>{{ number_format($ppn, 0, ',', '.') }}</span></div></td>
                </tr>
                <tr class="border-t border-b border-gray-400 font-bold text-sm">
                    <td colspan="2" class="border-x border-gray-400 px-2 py-2 text-right bg-blue-50 text-blue-800">TOTAL HARGA</td>
                    <td class="border-x border-gray-400 px-2 py-2 bg-blue-50"></td>
                    <td class="border-x border-gray-400 px-2 py-2 bg-blue-50 text-blue-800"><div class="flex justify-between"><span>Rp</span><span>{{ number_format($grandTotal, 0, ',', '.') }}</span></div></td>
                </tr>
            </tbody>
        </table>

        <!-- TERMS & CONDITIONS (Bilingual ID & EN) -->
        <div class="text-[11px] leading-tight mb-3">
            <div class="font-bold text-gray-800 mb-1.5 text-xs">Syarat & Ketentuan / <span class="italic font-normal text-gray-600">Terms & Conditions:</span></div>
            <div class="space-y-1.5">
                <div class="flex items-start">
                    <span class="mr-2 font-bold text-blue-600">✓</span>
                    <div>
                        <p class="font-medium text-gray-800">Sistem pembayaran 14 hari kalender setelah invoice diterima / <span class="italic font-normal text-gray-500">Payment terms: 14 calendar days upon invoice receipt.</span></p>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="mr-2 font-bold text-blue-600">✓</span>
                    <div>
                        <p class="font-medium text-gray-800">Harga sudah termasuk PPn 11% dan penawaran berlaku selama 7 hari kalender / <span class="italic font-normal text-gray-500">Price includes 11% VAT and quotation is valid for 7 calendar days.</span></p>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="mr-2 font-bold text-blue-600">✓</span>
                    <div>
                        <p class="font-medium text-gray-800">Ketersediaan stok tidak mengikat sebelum terbitnya Purchase Order (PO) resmi / <span class="italic font-normal text-gray-500">Stock availability is subject to prior sales until official PO.</span></p>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="mr-2 font-bold text-blue-600">✓</span>
                    <div>
                        <p class="font-medium text-gray-800">Dengan menandatangani penawaran ini, Pemesan menyetujui rincian harga, kuantiti, dan ketentuan yang berlaku / <span class="italic font-normal text-gray-500">By signing this quotation, the Client agrees to the quantities, pricing, and terms.</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- REKENING BANK RESMI PT -->
        <div class="mb-4 p-3 bg-slate-50 border border-slate-300 rounded-lg text-xs">
            <div class="font-bold text-slate-800 mb-2 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <span>Informasi Rekening Resmi Pembayaran / <span class="italic font-normal text-gray-600">Official Company Bank Accounts:</span></span>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-2.5 rounded border border-gray-200 border-l-4 border-l-blue-600 shadow-sm">
                    <p class="font-bold text-blue-900 text-xs">Bank Central Asia (BCA)</p>
                    <p class="font-mono text-sm font-bold text-gray-900 my-0.5">5415-888-999</p>
                    <p class="text-[11px] font-semibold text-gray-700">A/N: PT. PEDIA TEKNOLOGI INDONESIA</p>
                    <p class="text-[10px] text-gray-500">KCP Grand Galaxy City Bekasi</p>
                </div>
                <div class="bg-white p-2.5 rounded border border-gray-200 border-l-4 border-l-amber-600 shadow-sm">
                    <p class="font-bold text-amber-900 text-xs">Bank Mandiri</p>
                    <p class="font-mono text-sm font-bold text-gray-900 my-0.5">156-00-1789-8889</p>
                    <p class="text-[11px] font-semibold text-gray-700">A/N: PT. PEDIA TEKNOLOGI INDONESIA</p>
                    <p class="text-[10px] text-gray-500">KC Bekasi Juanda</p>
                </div>
            </div>
        </div>

        <p class="text-[10px] text-gray-600 mb-6 leading-relaxed">
            Demikian penawaran ini kami kirimkan. Jika ada hal yang ingin ditanyakan, dapat menghubungi sales kami di nomor telp: <span class="font-bold text-gray-800">{{ $rfq->sales->phone ?? '0812-xxxx-xxxx' }}</span> atau email: <span class="font-bold text-gray-800">{{ $rfq->sales->email ?? 'sales@pedia-technology.co.id' }}</span>. Atas perhatian dan kepercayaannya kami ucapkan terima kasih.
        </p>

        <!-- TANDA TANGAN -->
        <div class="flex justify-between text-xs mb-12">
            <!-- TTD Pedia -->
            <div class="w-1/2 relative">
                <p class="font-medium text-gray-700 mb-1">Hormat Kami / <span class="italic text-gray-500">Sincerely,</span></p>
                <p class="font-bold text-gray-900">PT. PEDIA TEKNOLOGI INDONESIA</p>
                <div style="height: 55px;"></div> <!-- Ruang untuk tanda tangan manual -->
                
                <p class="font-bold underline text-sm">{{ $rfq->sales->name ?? 'Sales Representative' }}</p>
                <p class="italic text-gray-600">{{ $rfq->sales->role ?? 'Account Manager' }}</p>
            </div>
            <!-- TTD Klien -->
            <div class="w-1/2 text-left pl-8 border-l border-gray-200">
                <p class="font-medium text-gray-700 mb-1">Disetujui Oleh / <span class="italic text-gray-500">Accepted & Confirmed by:</span></p>
                <p class="font-bold text-gray-900">{{ $rfq->customer->name ?? 'Klien' }}</p>
                <p class="text-[11px] italic text-gray-500 mb-3">Kami menyetujui kuantiti dan harga di atas / <span class="italic">We agree to the quantities & pricing</span></p>
                
                <table class="mt-4 w-64 text-xs">
                    <tr><td class="w-16 py-1 text-gray-600">Nama</td><td>: _________________</td></tr>
                    <tr><td class="py-1 text-gray-600">Jabatan</td><td>: _________________</td></tr>
                    <tr><td class="py-1 text-gray-600">Tanggal</td><td>: _________________</td></tr>
                </table>
            </div>
        </div>

        <!-- FOOTER ALAMAT -->
        <div class="mt-8 text-center border-t border-gray-200 pt-3">
            <div class="font-bold text-[11px] mb-1 text-blue-900">
                <p>THANK YOU FOR YOUR BUSINESS!</p>
                <p class="text-[10px] font-normal text-gray-600">Terima kasih atas kerja sama dan kepercayaan Anda kepada PT. Pedia Teknologi Indonesia.</p>
            </div>
            <p class="text-[10px] leading-tight mb-2 text-gray-600">
                Rukan Rose Garden Blok RRGB No. 93, Jl. Grand Galaxy City Central Park 3,<br>
                Kel. Jaka Setia, Kec. Bekasi Selatan, Kota Bekasi, Jawa Barat 17147<br>
                Telp : (+62) 021-3971-2155, Fax : (+62) 021-3970-0175 | Email : info@pedia-technology.co.id
            </p>
            <!-- Blue Banner -->
            <div class="bg-[#005a9c] text-white text-xs py-1.5 tracking-wide font-medium rounded-b-lg">
                website : https://pedia-technology.co.id
            </div>
        </div>

    </div>
</body>
</html>
