<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $rfq->quotation_number }} - Preview</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; background-color: #fff !important; }
            @page { size: A4; margin: 0; }
            .no-print { display: none !important; }
            #quotation-document { box-shadow: none !important; margin: 0 !important; width: 100% !important; min-height: 100% !important; padding: 25mm 20mm !important; }
        }
        .quotation-page {
            font-family: Calibri, 'Segoe UI', Arial, sans-serif;
            color: #000000;
        }
        .table-sampling {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #555555;
            table-layout: fixed;
        }
        .table-sampling th {
            border: 1px solid #555555;
            background-color: #f2f2f2;
            font-family: 'Calibri Light', Calibri, sans-serif;
            font-size: 11.5px;
            font-weight: bold;
            text-align: center;
            padding: 4px 6px;
            color: #000;
        }
        .table-sampling td {
            border-left: 1px solid #555555;
            border-right: 1px solid #555555;
            padding: 3px 8px;
            font-size: 13.5px;
            font-family: Calibri, sans-serif;
            vertical-align: top;
            color: #000;
        }
        .table-sampling tr.item-row td {
            border-bottom: 1px solid #555555;
        }
        .table-sampling .total-row td {
            border-top: 1px solid #555555;
            border-bottom: 1px solid #555555;
            font-weight: bold;
            font-size: 13.5px;
            padding: 3px 8px;
        }
    </style>
</head>
<body class="bg-[#525659] flex flex-col items-center py-8 min-h-screen text-black">

    <!-- Tombol Action (Tidak ikut tercetak saat print/PDF) -->
    <div class="no-print mb-6 flex gap-4 w-[210mm] justify-between items-center">
        <a href="{{ route('rfq.show', $rfq->id) }}" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded shadow text-sm font-semibold transition flex items-center gap-2">
            <span>&larr;</span> Kembali ke Detail RFQ
        </a>
        <div class="flex items-center gap-3">
            @if(in_array($rfq->status, [\App\Models\Rfq::STATUS_APPROVED, \App\Models\Rfq::STATUS_QUOTATION_CREATED]))
            <form action="{{ route('rfq.mark_quotation_sent', $rfq->id) }}" method="POST" class="inline m-0">
                @csrf
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded shadow text-sm font-semibold transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Mark Quotation Sent to Client
                </button>
            </form>
            @elseif($rfq->status === \App\Models\Rfq::STATUS_QUOTATION_SENT)
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-900 text-xs font-bold px-3.5 py-2 rounded flex items-center gap-2 shadow-sm">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Quotation Sent to Client
            </div>
            @endif
            <a href="{{ route('rfq.download_quotation', $rfq->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm font-semibold transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Download PDF
            </a>
        </div>
    </div>

    @php
        $clientCompanyName = $rfq->customer?->company_name ?: ($rfq->customer_name ?: 'Pelanggan');
        $custAddress       = trim($rfq->customer?->address ?? '');
        $custPhone         = $rfq->customer?->phone ?: ($rfq->customerContact?->phone ?: $rfq->customerContact?->office_phone);
        $custPic           = $rfq->customerContact?->name ?: ($rfq->customer?->cp_name ?: 'Bpk/Ibu');
        $salesPhone        = $rfq->sales?->phone ?: '021-3971-2155';
        $salesEmail        = $rfq->sales?->email ?: 'info@pedia-technology.co.id';
        $salesName         = $rfq->sales?->name ?: ($rfq->sales_name ?: 'Ade Zulvida');
        $salesRole         = $rfq->sales?->effective_job_title ?: (in_array($rfq->sales?->role, ['Sales', 'Sales Marketing']) ? 'Account Manager' : ($rfq->sales?->role ?: 'Account Manager'));
        $salesSignature    = $rfq->sales?->signature_url;
        $validityDays      = $rfq->items->max('validity_days') ?: 7;

        $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X'];
        $revRoman = $romanMap[$revisionCount ?? 0] ?? ($revisionCount ?? 1);
        $formattedRevDate = isset($revisionDate) ? \Carbon\Carbon::parse($revisionDate)->format('d M Y') : date('d M Y');

        $itemCount = count($rfq->items);
        $noteMinHeight = match(true) {
            $itemCount <= 2 => '160px',
            $itemCount <= 3 => '130px',
            $itemCount <= 4 => '90px',
            default => 'auto'
        };
    @endphp

    <!-- Lembar Dokumen A4 (Identik Sampling) -->
    <div class="bg-white w-[210mm] min-h-[297mm] px-[42px] pt-[36px] pb-[40px] shadow-2xl relative quotation-page flex flex-col justify-between" id="quotation-document">
        
        <div>
            <!-- HEADER -->
            <div class="flex justify-between items-start">
                <!-- Kiri: Logo & Tagline -->
                <div>
                    <img src="{{ asset('images/pedia_logo_hd.png') }}" alt="Pedia Technology" class="h-[52px] object-contain block" onerror="this.src='{{ asset('images/logo.png') }}'">
                    <div class="font-bold text-[13px] uppercase tracking-tight text-black mt-2 leading-none">PT. PEDIA TEKNOLOGI INDONESIA</div>
                    <div class="italic text-[11px] text-[#444] font-normal mt-1">With Our Experience Everything Is Possible</div>
                </div>

                <!-- Kanan: Judul Quotation -->
                <div class="text-right">
                    <div class="text-[44px] text-[#555555] font-light leading-none mb-3 tracking-wide" style="font-family: 'Calibri Light', Calibri, sans-serif;">Quotation</div>
                    <table class="ml-auto text-[11.5px] text-right border-collapse">
                        <tr>
                            <td class="pr-5 py-0.5 text-right uppercase text-[#222]" style="font-family: 'Calibri Light', Calibri, sans-serif;">TANGGAL</td>
                            <td class="text-right font-normal text-black py-0.5">{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="pr-5 py-0.5 text-right text-[#222]" style="font-family: 'Calibri Light', Calibri, sans-serif;">No. Penawaran</td>
                            <td class="text-right font-normal text-black py-0.5">{{ $rfq->quotation_number }}</td>
                        </tr>
                        @if(isset($revisionCount) && $revisionCount > 0)
                        <tr>
                            <td colspan="2" class="text-red-600 font-bold py-0.5 text-right tracking-wide text-[11.5px]">
                                REVISI {{ $revRoman }} : {{ $formattedRevDate }}
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- INFO KLIEN & BERLAKU S/D -->
            <div class="flex justify-between items-end mt-4 mb-2 text-[11.5px]">
                <!-- Info Klien (Kiri) -->
                <div class="leading-relaxed" style="font-family: Arial, sans-serif;">
                    <div class="text-black" style="font-family: Calibri, sans-serif;">Kepada YTH</div>
                    <div class="font-bold text-[13px] text-black mt-0.5">{{ $clientCompanyName }}</div>
                    @if(!empty($custAddress))
                    <div class="whitespace-pre-line text-[#222]">{{ $custAddress }}</div>
                    @endif
                    @if(!empty($custPhone))
                    <div class="text-[#222]">Telp : {{ $custPhone }}</div>
                    @endif
                    <div class="text-[#222]">Up. {{ $custPic }}</div>
                </div>

                <!-- Berlaku s/d (Kanan, Kotak Abu-abu persis sampling) -->
                <div class="self-end mb-1">
                    <div class="bg-[#f2f2f2] px-3.5 py-1 w-[240px] flex items-center justify-between text-[11.5px]">
                        <span class="italic text-black" style="font-family: Arial, sans-serif;">Berlaku s/d tgl :</span>
                        <span class="font-normal text-black" style="font-family: Calibri, sans-serif;">{{ \Carbon\Carbon::parse($rfq->rfq_date)->addDays($validityDays)->format('d M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Kalimat Pembuka -->
            <div class="text-[11.5px] text-black mt-3 mb-2 font-normal">
                Berikut penawaran dari kami ・ 下記の通り御見積申し上げます。
            </div>

            <!-- TABEL PENAWARAN (PERSIS SAMPLING) -->
            <table class="table-sampling mb-4">
                <thead>
                    <tr>
                        <th style="width: 58px;">JUMLAH</th>
                        <th>DESKRIPSI</th>
                        <th style="width: 115px;">HARGA</th>
                        <th style="width: 125px;">JUMLAH HARGA</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $grandSubtotal = 0;
                        $totalQty = 0;
                    @endphp

                    @foreach($rfq->items as $item)
                        @php
                            $unitPrice = (float) $item->price_after_margin;
                            $rowTotal = $unitPrice * $item->qty;
                            $grandSubtotal += $rowTotal;
                            $totalQty += $item->qty;
                        @endphp
                        <tr class="item-row">
                            <td class="text-center">
                                <span class="font-bold">{{ (int) $item->qty }}</span> {{ $item->unit ?: 'Unit' }}
                            </td>
                            <td>
                                <div class="font-bold text-[13px] leading-snug">{{ $item->product_name }}</div>
                                @if($item->detail_item || $item->description)
                                <div class="font-normal text-[11.5px] text-[#333] whitespace-pre-line mt-0.5 leading-normal">{!! e($item->detail_item ?? $item->description) !!}</div>
                                @endif
                            </td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($unitPrice, 0, ',', '.') }}</span></div>
                            </td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($rowTotal, 0, ',', '.') }}</span></div>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Baris Note (Hanya tampil jika Admin/Sales mengisi catatan) --}}
                    @if(!empty(trim($rfq->notes ?? '')))
                    <tr class="item-row">
                        <td style="border-left: 1px solid #555555; border-right: 1px solid #555555; border-bottom: 1px solid #555555; vertical-align: top;"></td>
                        <td style="border-left: 1px solid #555555; border-right: 1px solid #555555; border-bottom: 1px solid #555555; padding: 8px 8px; vertical-align: top;">
                            <div style="min-height: {{ $noteMinHeight }};" class="flex flex-col justify-start">
                                <div class="font-bold text-[12px] text-black mb-1">Note :</div>
                                <div class="italic text-[#222] text-[11.5px] leading-relaxed whitespace-pre-line">{!! nl2br(e($rfq->notes)) !!}</div>
                            </div>
                        </td>
                        <td style="border-left: 1px solid #555555; border-right: 1px solid #555555; border-bottom: 1px solid #555555; vertical-align: top;"></td>
                        <td style="border-left: 1px solid #555555; border-right: 1px solid #555555; border-bottom: 1px solid #555555; vertical-align: top;"></td>
                    </tr>
                    @endif

                    @php
                        $ppn = $grandSubtotal * 0.11;
                        $grandTotal = $grandSubtotal + $ppn;
                    @endphp

                    <!-- 1. TOTAL -->
                    <tr class="total-row">
                        <td class="text-center font-bold">{{ (int) $totalQty }} Unit</td>
                        <td class="text-right pr-4 uppercase font-bold">TOTAL</td>
                        <td class="font-bold text-left px-2">Rp</td>
                        <td class="font-bold text-right px-2">{{ number_format($grandSubtotal, 0, ',', '.') }}</td>
                    </tr>

                    <!-- 2. PPn -->
                    <tr class="total-row" style="border-top: none;">
                        <td></td>
                        <td class="text-right pr-4 font-bold">PPn</td>
                        <td class="font-bold text-left px-2">Rp</td>
                        <td class="font-bold text-right px-2">{{ number_format($ppn, 0, ',', '.') }}</td>
                    </tr>

                    <!-- 3. TOTAL HARGA -->
                    <tr class="total-row" style="border-top: none;">
                        <td></td>
                        <td class="text-right pr-4 uppercase font-bold">TOTAL HARGA</td>
                        <td class="font-bold text-left px-2">Rp</td>
                        <td class="font-bold text-right px-2">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- SYARAT & KETENTUAN (Bilingual ID & JP persis Sampling) -->
            <div class="text-[11.5px] text-black leading-tight my-4 space-y-2">
                <div class="flex items-start">
                    <span class="mr-2 text-black font-bold" style="font-family: Arial, sans-serif;">✓</span>
                    <div>
                        <div>Sistem pembayaran 14 hari setelah invoice diterima</div>
                        <div class="italic text-[#555]">お支払い条件は、請求書受領後14日以内となります。</div>
                    </div>
                </div>
                <div class="flex items-start">
                    <span class="mr-2 text-black font-bold" style="font-family: Arial, sans-serif;">✓</span>
                    <div>
                        <div>Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku. Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                        <div class="italic text-[#555]">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。ご署名後はキャンセルできませんのでご了承ください。</div>
                    </div>
                </div>
            </div>

            <!-- Kalimat Penutup -->
            <p class="text-[11.5px] text-black mb-6 leading-relaxed">
                Demikian penawaran ini kami kirimkan. Jika ada hal yang ingin ditanyakan, dapat menghubungi saya di nomor telp : <span class="font-normal">{{ $salesPhone }}</span> atau e-mail <span class="font-normal">{{ $salesEmail }}</span> . Atas perhatian dan kepercayaan nya kami ucapkan Terima Kasih.
            </p>

            <!-- TANDA TANGAN (Persis Sampling) -->
            <div class="flex justify-between items-start text-[11.5px] text-black mb-8">
                <!-- TTD Pedia (Kiri) -->
                <div class="w-1/2">
                    <div class="mb-1">Hormat Kami</div>
                    <div class="relative w-[220px] h-[72px] my-1 -ml-1">
                        <!-- Stempel Resmi Perusahaan -->
                        <img src="{{ asset('images/pedia_company_stamp.png') }}" alt="Stempel Pedia" 
                             class="absolute left-8 top-1.5 h-[58px] object-contain opacity-90 select-none pointer-events-none" style="z-index: 1;">
                        
                        <!-- Tanda Tangan Sales -->
                        @if($salesSignature)
                            <img src="{{ $salesSignature }}" alt="Tanda Tangan Sales" 
                                 class="absolute left-0 top-0 h-[72px] object-contain block" style="z-index: 2;">
                        @endif
                    </div>
                    <div class="font-bold text-[13px] text-black mt-1">{{ $salesName }}</div>
                    <div class="italic text-[11.5px] text-[#333]">{{ $salesRole }}</div>
                </div>

                <!-- TTD Klien (Kanan) -->
                <div class="w-1/2 text-left pl-6">
                    <div>Kami menyetujui dengan qty dan harga yang ditawarkan di atas.</div>
                    <div class="italic text-[11px] text-[#555] mb-1">上記の数量と価格に同意いたします。</div>
                    <div class="font-bold text-[13px] text-black mb-12">{{ $clientCompanyName }}</div>
                    
                    <table class="text-[11.5px] text-black w-full max-w-[280px]">
                        <tr><td class="w-16 py-0.5 text-[#333]">Nama</td><td>: </td></tr>
                        <tr><td class="py-0.5 text-[#333]">Jabatan</td><td>: </td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- FOOTER (Persis Sampling) -->
        <div class="mt-8 text-center text-black">
            <div class="font-bold text-[11.5px] tracking-wide text-black mb-0.5 uppercase">THANK YOU FOR YOUR BUSINESS!</div>
            <div class="font-bold text-[11px] text-black mb-2">お買い上げくださってありがとうございます！</div>
            <div class="text-[10.5px] leading-relaxed text-[#333] mb-3">
                Rukan Rose Garden Blok RRGB No. 93, Jl. Grand Galaxy City Central Park 3,<br>
                Kel. Jaka Setia, Kec. Bekasi Selatan, Kota Bekasi, Jawa Barat 17147<br>
                Telp : (+62) 021-3971-2155, Fax : (+62) 021-3970-0175
            </div>
            <!-- Blue Banner Full Width -->
            <div class="bg-[#005a9c] text-white text-[12px] py-1.5 tracking-wider font-semibold">
                website : https://pedia-technology.co.id
            </div>
        </div>

    </div>
</body>
</html>
