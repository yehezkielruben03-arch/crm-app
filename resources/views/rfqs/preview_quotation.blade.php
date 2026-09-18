<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Quotation {{ str_replace('RFQ', 'QUO', $rfq->rfq_number) }}</title>
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
    <div class="no-print mb-6 flex gap-4 w-[210mm] justify-between">
        <a href="{{ route('rfq.show', $rfq->id) }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded shadow-md text-sm font-bold transition">
            &larr; Kembali ke Detail RFQ
        </a>
        <a href="{{ route('rfq.download_quotation', $rfq->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow-md text-sm font-bold transition flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            Download PDF
        </a>
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
                    <span>{{ str_replace('RFQ', 'QUO', $rfq->rfq_number) }}</span>
                    
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
                <p>Kepada YTH</p>
                <p class="font-bold text-base mt-1">{{ $rfq->customer->name ?? '-' }}</p>
                <p class="whitespace-pre-line">{{ $rfq->customer->address ?? '-' }}</p>
                <p>Telp : {{ $rfq->customer->phone ?? '-' }}</p>
                <p>Up. {{ $rfq->customerContact->name ?? 'Bpk/Ibu' }}</p>
            </div>
            
            <div class="flex bg-gray-100 px-3 py-1 w-64 items-center justify-between border-b border-gray-300">
                <span class="italic text-gray-700">Berlaku s/d tgl :</span>
                <span class="font-medium">{{ \Carbon\Carbon::parse($rfq->rfq_date)->addDays(7)->format('d M Y') }}</span>
            </div>
        </div>

        <p class="text-xs mb-1">Berikut penawaran dari kami・下記の通り御見積申し上げます。</p>

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
                        <div class="text-red-600 font-bold italic">- Unit READY LIMITED stok tidak mengikat</div>
                        <div class="italic">- Harga dapat berubah tanpa pemberitahuan</div>
                        <div class="italic">- Mohon tanyakan stok terlebih dahulu sebelum mengirim PO</div>
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

        <!-- TERMS & CONDITIONS -->
        <div class="text-[11px] leading-tight mb-4">
            <div class="flex items-start mb-2">
                <span class="mr-2 font-bold text-blue-600">✓</span>
                <div>
                    <p>Sistem pembayaran 14 hari setelah invoice diterima</p>
                    <p class="italic text-gray-500">お支払い条件は、請求書受領後14日以内となります。</p>
                </div>
            </div>
            <div class="flex items-start">
                <span class="mr-2 font-bold text-blue-600">✓</span>
                <div>
                    <p>Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku. Setelah ditandatangani, penawaran tidak dapat dibatalkan.</p>
                    <p class="italic text-gray-500">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。ご署名後はキャンセルできませんのでご了承ください。</p>
                </div>
            </div>
        </div>

        <p class="text-[10px] text-gray-600 mb-8 leading-relaxed">
            Demikian penawaran ini kami kirimkan. Jika ada hal yang ingin ditanyakan, dapat menghubungi saya di nomor telp : {{ $rfq->sales->phone ?? '0812-xxxx-xxxx' }} atau e-mail {{ $rfq->sales->email ?? 'sales@pedia-group.com' }}. Atas perhatian dan kepercayaan nya kami ucapkan Terima Kasih.
        </p>

        <!-- TANDA TANGAN -->
        <div class="flex justify-between text-xs mb-16">
            <!-- TTD Pedia -->
            <div class="w-1/2 relative">
                <p class="mb-4">Hormat Kami</p>
                <div style="height: 60px;"></div> <!-- Ruang untuk tanda tangan manual -->
                
                <p class="font-bold underline text-sm">{{ $rfq->sales->name ?? 'Sales Representative' }}</p>
                <p class="italic text-gray-600">{{ $rfq->sales->role ?? 'Account Manager' }}</p>
            </div>
            <!-- TTD Klien -->
            <div class="w-1/2 text-left pl-10 border-l border-gray-200">
                <p>Kami menyetujui dengan qty dan harga yang ditawarkan di atas.</p>
                <p class="italic mb-6 text-gray-500">上記の数量と価格に同意いたします。</p>
                <p class="font-bold">{{ $rfq->customer->name ?? 'Klien' }}</p>
                
                <table class="mt-12 w-64">
                    <tr><td class="w-16 py-1">Nama</td><td>: _________________</td></tr>
                    <tr><td class="py-1">Jabatan</td><td>: _________________</td></tr>
                </table>
            </div>
        </div>

        <!-- FOOTER ALAMAT -->
        <div class="absolute bottom-0 left-0 w-full text-center">
            <div class="font-bold text-[11px] mb-2 text-blue-900">
                <p>THANK YOU FOR YOUR BUSINESS!</p>
                <p class="text-[10px]">お買い上げくださってありがとうございます！</p>
            </div>
            <p class="text-[10px] leading-tight mb-3 text-gray-600">
                Rukan Rose Garden Blok RRGB No. 93, Jl. Grand Galaxy City Central Park 3,<br>
                Kel. Jaka Setia, Kec. Bekasi Selatan, Kota Bekasi, Jawa Barat 17147<br>
                Telp : (+62) 021-3971-2155, Fax : (+62) 021-3970-0175
            </p>
            <!-- Blue Banner -->
            <div class="bg-[#005a9c] text-white text-xs py-2 tracking-wide font-medium rounded-b-lg">
                website : https://pedia-technology.co.id
            </div>
        </div>

    </div>
</body>
</html>
