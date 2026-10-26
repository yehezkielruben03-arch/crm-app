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
        @if(!empty($isDraftPreview))
        <div class="flex items-center gap-3">
            <button type="button" onclick="window.parent && window.parent.closeQuotationPreview ? window.parent.closeQuotationPreview() : window.close()" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded shadow text-sm font-semibold transition flex items-center gap-2 cursor-pointer">
                <span>&larr;</span> Tutup Pratinjau
            </button>
            <span class="bg-amber-100 border border-amber-300 text-amber-900 text-xs font-bold px-3 py-1.5 rounded-lg flex items-center gap-1.5 shadow-2xs">
                <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pratinjau Draf Sebelum Approval Leader
            </span>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm font-semibold transition flex items-center gap-2 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print / Simpan PDF
            </button>
        </div>
        @else
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
            <a href="{{ route('rfq.download_quotation', $rfq->id, false) }}" download onclick="return downloadQuotationPdf(event, this)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow text-sm font-semibold transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                <span>Download PDF</span>
            </a>
        </div>
        @endif
    </div>

    @php
        $rawCompName = trim($rfq->resolved_company_name ?? '');
        if (preg_match('/^(.+?),\s*(PT|CV)\.?$/i', $rawCompName, $m)) {
            $clientCompanyName = strtoupper($m[2]) . '. ' . trim($m[1]);
        } else {
            $clientCompanyName = $rawCompName;
        }
        $custAddress       = $rfq->resolved_customer_address;
        $custPhone         = $rfq->resolved_customer_phone;
        $custPic           = $rfq->resolved_pic_name;

        // Ambil data user sales: utamakan relasi sales di RFQ, fallback ke sales di customer
        $salesUser         = $rfq->sales ?: $rfq->customer?->sales;
        $isHouseAccount    = !$salesUser && ($rfq->sales_name === 'Pedia' || empty($rfq->sales_name));

        if ($salesUser) {
            $salesName     = $salesUser->name;
            $salesRole     = $salesUser->effective_job_title ?: (in_array($salesUser->role, ['Sales', 'Sales Marketing']) ? 'Account Manager' : ($salesUser->role ?: 'Account Manager'));
            $salesPhone    = $salesUser->phone ?: '021-3971-2155';
            $salesEmail    = $salesUser->email ?: 'info@pedia-technology.co.id';
            $sigUser       = !empty($salesUser->signature_path) ? $salesUser : null;
        } elseif ($isHouseAccount) {
            $salesName     = 'PT Pedia Technology Indonesia';
            $salesRole     = 'Management';
            $salesPhone    = '021-3971-2155';
            $salesEmail    = 'info@pedia-technology.co.id';
            try {
                $sigUser   = \App\Models\User::whereIn('role', ['Leader', 'Super Admin', 'Admin', 'Admin Purchase'])
                    ->whereNotNull('signature_path')
                    ->where('signature_path', '!=', '')
                    ->orderByRaw("CASE WHEN role = 'Leader' THEN 1 WHEN role = 'Super Admin' THEN 2 ELSE 3 END")
                    ->first();
            } catch (\Throwable $e) {
                $sigUser   = null;
            }
        } else {
            $salesName     = $rfq->sales_name ?: 'Ade Zulvida';
            $salesRole     = 'Account Manager';
            $salesPhone    = '021-3971-2155';
            $salesEmail    = 'info@pedia-technology.co.id';
            $sigUser       = null;
        }
        $salesSignature    = $sigUser?->signature_url;
        $validityDays      = $rfq->items->max('validity_days') ?: 7;

        $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X'];
        $revRoman = $romanMap[$revisionCount ?? 0] ?? ($revisionCount ?? 1);
        $formattedRevDate = isset($revisionDate) ? \Carbon\Carbon::parse($revisionDate)->format('d M Y') : date('d M Y');

        $quotationItems = $rfq->customer_quotation_items;
        $itemCount = count($quotationItems);
        $noteMinHeight = match(true) {
            $itemCount <= 1 => '210px',
            $itemCount <= 2 => '160px',
            $itemCount <= 3 => '120px',
            $itemCount <= 4 => '80px',
            default => 'auto'
        };
    @endphp

    <!-- Lembar Dokumen A4 (Identik Sampling) -->
    <div class="bg-white w-[210mm] min-h-[297mm] px-[42px] pt-[36px] pb-[40px] shadow-2xl relative quotation-page flex flex-col justify-between" id="quotation-document">
        
        <div>
            <!-- HEADER -->
            <div class="flex justify-between items-start">
                <!-- Kiri: Logo & Tagline (65%) -->
                <div class="w-[65%]">
                    <img src="{{ asset('images/pedia_logo_hd.png') }}" alt="Pedia Technology" class="w-[195px] h-auto object-contain block" onerror="this.src='{{ asset('images/logo.png') }}'">
                    <div class="font-bold text-[13px] uppercase tracking-tight text-black mt-1 leading-tight">PT. PEDIA TEKNOLOGI INDONESIA</div>
                    <div class="italic font-bold text-[10.5px] text-[#7f7f7f] pl-2 leading-tight">With Our Experience Everything Is Possible</div>
                </div>

                <!-- Kanan: Judul Quotation (35% sejajar HARGA + JUMLAH HARGA) -->
                <div class="w-[35%] text-right">
                    <div class="text-[52px] text-[#7f7f7f] font-normal leading-none mb-3" style="font-family: Calibri, sans-serif;">Quotation</div>
                    <table class="w-full text-[12px] border-collapse">
                        <tr>
                            <td class="w-1/2 pr-2 py-0.5 text-right uppercase text-black">TANGGAL</td>
                            <td class="w-1/2 text-right font-normal text-black py-0.5">{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td class="w-1/2 pr-2 py-0.5 text-right text-black">No. Penawaran</td>
                            <td class="w-1/2 text-right font-normal text-black py-0.5">{{ $rfq->quotation_number }}</td>
                        </tr>
                        @if(isset($revisionCount) && $revisionCount > 0)
                        <tr>
                            <td colspan="2" class="text-[#ff0000] font-bold py-0.5 text-right text-[12px]">
                                REVISI {{ $revRoman }} : {{ $formattedRevDate }}
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- INFO KLIEN & BERLAKU S/D -->
            <div class="flex justify-between items-end mt-2 mb-2 text-[12px]">
                <!-- Info Klien (Kiri 65%) -->
                <div class="w-[65%] leading-snug pr-4" style="font-family: Arial, sans-serif;">
                    <div class="text-black">Kepada YTH</div>
                    <div class="font-bold text-[13px] text-black my-0.5">{{ $clientCompanyName }}</div>
                    @if(!empty($custAddress))
                    <div class="whitespace-pre-line text-black">{{ $custAddress }}</div>
                    @endif
                    @if(!empty($custPhone))
                    <div class="text-black">Telp : {{ $custPhone }}</div>
                    @endif
                    @if(!empty($custPic))
                    <div class="text-black">Up. {{ $custPic }}</div>
                    @endif
                </div>

                <!-- Berlaku s/d (Kanan 35%, Kotak Abu-abu sejajar kolom HARGA + JUMLAH HARGA) -->
                <div class="w-[35%] self-end">
                    <div class="bg-[#f2f2f2] py-1 px-1 w-full flex items-center text-[12px]">
                        <span class="w-1/2 text-center italic text-black" style="font-family: Arial, sans-serif;">Berlaku s/d tgl :</span>
                        <span class="w-1/2 text-right pr-1 font-normal text-black" style="font-family: Calibri, sans-serif;">{{ $rfq->formatted_valid_until }}</span>
                    </div>
                </div>
            </div>

            <!-- Kalimat Pembuka -->
            <div class="text-[13px] text-black mt-3 mb-1.5 font-normal">
                Berikut penawaran dari kami・下記の通り御見積申し上げます。
            </div>

            <!-- TABEL PENAWARAN (PERSIS SAMPLING: 9.6% | 55.4% | 17.5% | 17.5%) -->
            <table class="table-sampling mb-2">
                <thead>
                    <tr>
                        <th style="width: 9.6%; font-weight: normal;">JUMLAH</th>
                        <th style="width: 55.4%; font-weight: normal;">DESKRIPSI</th>
                        <th style="width: 17.5%; font-weight: normal;">HARGA</th>
                        <th style="width: 17.5%; font-weight: normal;">JUMLAH HARGA</th>
                    </tr>
                </thead>
                <tbody>
                    @php 
                        $grandSubtotal = 0;
                        $totalQty = 0;
                    @endphp

                    @forelse($quotationItems as $item)
                        @php
                            $unitPrice = (float) $item->unit_price;
                            $rowTotal = (float) $item->row_total;
                            $grandSubtotal += $rowTotal;
                            $totalQty += $item->qty;
                        @endphp
                        <tr class="item-row">
                            <td class="font-bold !px-1">
                                <div class="flex w-full">
                                    <span class="w-[40%] text-right pr-1">{{ (int) $item->qty }}</span>
                                    <span class="w-[60%] text-left pl-0.5">{{ ucwords(strtolower(trim($item->unit ?: 'Unit'))) }}</span>
                                </div>
                            </td>
                            <td>
                                @if($loop->last)
                                    <div style="min-height: {{ $noteMinHeight }};" class="flex flex-col justify-between">
                                        <div>
                                            <div class="font-bold text-[14px] leading-snug text-black">{{ ucfirst($item->product_name) }}</div>
                                            @php $itemSpec = $item->description ?: $item->detail_item; @endphp
                                            @if(!empty($itemSpec))
                                            <div class="font-normal italic text-[12px] text-black whitespace-pre-line mt-0.5 leading-snug">{!! e(ucfirst($itemSpec)) !!}</div>
                                            @endif
                                        </div>
                                        <div class="mt-4 mb-1">
                                            <div class="font-bold text-[14px] text-black mb-0.5">Note :</div>
                                            @php
                                                $customNotes = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $rfq->notes ?? ''))), function($line) {
                                                    $clean = strtolower(trim(ltrim($line, '- ')));
                                                    return $clean !== '' && !str_contains($clean, 'harga dapat berubah') && !str_contains($clean, 'tanyakan stok');
                                                });
                                            @endphp
                                            @foreach($customNotes as $cNote)
                                                @php
                                                    $formattedNote = str_starts_with($cNote, '-') ? $cNote : '- ' . $cNote;
                                                @endphp
                                                <div class="italic text-[#ff0000] font-bold text-[12px] leading-snug">{{ $formattedNote }}</div>
                                            @endforeach
                                            <div class="italic text-black text-[12px] leading-snug">- Harga dapat berubah tanpa pemberitahuan</div>
                                            <div class="italic text-black text-[12px] leading-snug">- Mohon tanyakan stok terlebih dahulu sebelum mengirim PO</div>
                                        </div>
                                    </div>
                                @else
                                    <div class="font-bold text-[14px] leading-snug text-black">{{ ucfirst($item->product_name) }}</div>
                                    @php $itemSpec = $item->description ?: $item->detail_item; @endphp
                                    @if(!empty($itemSpec))
                                    <div class="font-normal italic text-[12px] text-black whitespace-pre-line mt-0.5 leading-snug">{!! e(ucfirst($itemSpec)) !!}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($unitPrice, 0, ',', '.') }}</span></div>
                            </td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($rowTotal, 0, ',', '.') }}</span></div>
                            </td>
                        </tr>
                    @empty
                        <tr class="item-row">
                            <td></td>
                            <td>
                                <div style="min-height: 180px;" class="flex flex-col justify-end">
                                    <div class="font-bold text-[14px] text-black mb-0.5">Note :</div>
                                    <div class="italic text-black text-[12px] leading-snug">- Harga dapat berubah tanpa pemberitahuan</div>
                                    <div class="italic text-black text-[12px] leading-snug">- Mohon tanyakan stok terlebih dahulu sebelum mengirim PO</div>
                                </div>
                            </td>
                            <td></td>
                            <td></td>
                        </tr>
                    @endforelse

                    @php
                        $isIncludeTax = $rfq->isIncludeTax();
                        $ppn = round($grandSubtotal * 0.11);
                        $grandTotal = $isIncludeTax ? $grandSubtotal : ($grandSubtotal + $ppn);
                        $uniqueUnits = $quotationItems->map(fn($i) => ucwords(strtolower(trim($i->unit ?: 'Unit'))))->unique()->values();
                        $summaryUnitLabel = $uniqueUnits->count() > 1 ? 'Item' : ($uniqueUnits->first() ?: 'Unit');
                    @endphp

                    @if($isIncludeTax)
                        <!-- TOTAL HARGA (Include PPN / Perorangan) -->
                        <tr class="total-row">
                            <td class="font-bold !px-1">
                                <div class="flex w-full">
                                    <span class="w-[40%] text-right pr-1">{{ (int) $totalQty }}</span>
                                    <span class="w-[60%] text-left pl-0.5">{{ $summaryUnitLabel }}</span>
                                </div>
                            </td>
                            <td style="border-right: none;" class="text-right pr-2 uppercase font-bold">TOTAL HARGA</td>
                            <td style="border-left: none;"></td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($grandSubtotal, 0, ',', '.') }}</span></div>
                            </td>
                        </tr>
                    @else
                        <!-- 1. TOTAL -->
                        <tr class="total-row">
                            <td class="font-bold !px-1">
                                <div class="flex w-full">
                                    <span class="w-[40%] text-right pr-1">{{ (int) $totalQty }}</span>
                                    <span class="w-[60%] text-left pl-0.5">{{ $summaryUnitLabel }}</span>
                                </div>
                            </td>
                            <td style="border-right: none;" class="text-right pr-2 uppercase font-bold">TOTAL</td>
                            <td style="border-left: none;"></td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($grandSubtotal, 0, ',', '.') }}</span></div>
                            </td>
                        </tr>

                        <!-- 2. PPn -->
                        <tr class="total-row" style="border-top: none;">
                            <td colspan="2" style="border-right: none;" class="text-right pr-2 font-bold">PPn</td>
                            <td style="border-left: none;"></td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($ppn, 0, ',', '.') }}</span></div>
                            </td>
                        </tr>

                        <!-- 3. TOTAL HARGA -->
                        <tr class="total-row" style="border-top: none;">
                            <td colspan="2" style="border-right: none;" class="text-right pr-2 uppercase font-bold">TOTAL HARGA</td>
                            <td style="border-left: none;"></td>
                            <td class="font-bold">
                                <div class="flex justify-between px-0.5"><span>Rp</span><span>{{ number_format($grandTotal, 0, ',', '.') }}</span></div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            <!-- SYARAT & KETENTUAN (Dinamis Sesuai Payment Terms) -->
            <div class="text-[12px] text-black leading-snug my-2 space-y-1">
                @if($rfq->isPaymentTermCbdPersonal())
                    <div class="flex items-start text-[#ff0000] italic">
                        <span class="w-[4.6%] flex-shrink-0 text-center font-normal not-italic text-black">✓</span>
                        <div class="w-[95.4%] min-w-0 space-y-0.5 leading-snug break-words">
                            <div>Sistem pembayaran Cash Before Delivery</div>
                            <table class="border-none text-[12px] text-[#ff0000] italic">
                                <tr><td class="w-24 p-0">Nama Bank</td><td class="p-0">: BANK MANDIRI</td></tr>
                                <tr><td class="p-0">Cabang</td><td class="p-0">: KEMANG PRATAMA BEKASI</td></tr>
                                <tr><td class="p-0">Nama Akun</td><td class="p-0">: PEDIA TEKNOLOGI INDONESIA</td></tr>
                                <tr><td class="p-0">No. Akun</td><td class="p-0">: 1670024241514 (IDR)</td></tr>
                            </table>
                        </div>
                    </div>
                    <div class="flex items-start text-black italic">
                        <span class="w-[4.6%] flex-shrink-0 text-center font-normal not-italic text-black">✓</span>
                        <div class="w-[95.4%] min-w-0 space-y-0.5 break-words">
                            <div class="break-words">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku.<br>Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                            <div class="text-black not-italic break-words">本見積書へのご署名をもって、本見積内容および諸条件にご同意いただいたものとみなします。<br>なお、ご署名後のキャンセルはお受けできませんので、ご了承ください。</div>
                        </div>
                    </div>
                @elseif($rfq->isPaymentTermCbdB2b())
                    <div class="flex items-start">
                        <span class="w-[4.6%] flex-shrink-0 text-center text-black font-normal">※</span>
                        <div class="w-[95.4%] min-w-0 break-words">
                            <div class="italic">Sistem pembayaran Cash Before Delivery</div>
                            <div class="text-black">支払条件：前払い（納品前）</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="w-[4.6%] flex-shrink-0 text-center text-black font-normal">※</span>
                        <div class="w-[95.4%] min-w-0 space-y-0.5 break-words">
                            <div class="break-words">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku.<br>Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                            <div class="text-black break-words">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。<br>ご署名後はキャンセルできませんのでご了承ください。</div>
                        </div>
                    </div>
                @else
                    @php $termDays = $rfq->getPaymentTermDays(); @endphp
                    <div class="flex items-start">
                        <span class="w-[4.6%] flex-shrink-0 text-center text-black font-normal">✓</span>
                        <div class="w-[95.4%] min-w-0 break-words">
                            <div class="italic">Sistem pembayaran {{ $termDays }} hari setelah invoice diterima</div>
                            <div class="text-black">お支払い条件は、請求書受領後{{ $termDays }}日以内となります。</div>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <span class="w-[4.6%] flex-shrink-0 text-center text-black font-normal">✓</span>
                        <div class="w-[95.4%] min-w-0 space-y-0.5 break-words">
                            <div class="italic break-words">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku.<br>Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                            <div class="text-black break-words">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。<br>ご署名後はキャンセルできませんのでご了承ください。</div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Kalimat Penutup -->
            <p class="text-[12px] text-black mt-2 mb-4 leading-snug">
                Demikian penawaran ini kami kirimkan. Jika ada hal yang ingin ditanyakan, dapat menghubungi saya di nomor telp : <span class="font-normal">{{ $salesPhone }}</span> atau e-mail <span class="font-normal">{{ $salesEmail }}</span> . Atas perhatian dan kepercayaan nya kami ucapkan Terima Kasih.
            </p>

            <!-- TANDA TANGAN (Persis Sampling — Nama Sales & Nama Klien sejajar horizontal) -->
            <table class="w-full text-[12px] text-black mb-6 border-collapse">
                <tr>
                    <td class="w-[52%] align-top p-0">
                        <div>Hormat Kami</div>
                        <div class="relative w-[220px] h-[72px] my-1">
                            <!-- Stempel Resmi Perusahaan -->
                            <img src="{{ asset('images/pedia_company_stamp.png') }}" alt="Stempel Pedia" 
                                 class="absolute {{ $salesSignature ? 'left-6' : 'left-1' }} top-1 h-[54px] object-contain opacity-95 select-none pointer-events-none" style="z-index: 1;">
                            
                            <!-- Tanda Tangan Sales / Management -->
                            @if($salesSignature)
                                <img src="{{ $salesSignature }}" alt="Tanda Tangan" 
                                     class="absolute left-0 top-0 h-[70px] object-contain block" style="z-index: 2;">
                            @endif
                        </div>
                    </td>
                    <td class="w-[48%] align-top p-0">
                        <div>Kami menyetujui dengan qty dan harga yang ditawarkan di atas.</div>
                        <div class="text-[11.5px] text-black">上記の数量と価格に同意いたします。</div>
                        <div class="font-normal text-[12px] text-black mt-0.5">{{ $clientCompanyName }}</div>
                    </td>
                </tr>
                <tr>
                    <td class="align-bottom p-0 font-bold text-[12px] text-black">{{ $salesName }}</td>
                    <td class="align-bottom p-0 text-[12px] text-black">
                        <div class="flex"><span class="w-12">Nama</span><span>: </span></div>
                    </td>
                </tr>
                <tr>
                    <td class="align-top p-0 italic text-[11px] text-black">{{ $salesRole }}</td>
                    <td class="align-top p-0 text-[12px] text-black">
                        <div class="flex"><span class="w-12">Jabatan</span><span>: </span></div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- FOOTER (Persis Sampling — menggunakan banner footer resmi HD) -->
        <div class="mt-4 text-center text-black">
            <div class="font-bold text-[12px] text-black leading-tight uppercase">THANK YOU FOR YOUR BUSINESS!</div>
            <div class="font-bold text-[11.5px] text-black mb-1 leading-tight">お買い上げくださってありがとうございます！</div>
            <img src="{{ asset('images/pedia_footer_hd.png') }}" alt="Footer PT Pedia Teknologi Indonesia" class="w-[92%] mx-auto h-auto block">
        </div>

    </div>
    <script>
        async function downloadQuotationPdf(e, el) {
            e.preventDefault();
            const url = el.getAttribute('href');
            const labelSpan = el.querySelector('span');
            const origText = labelSpan ? labelSpan.textContent : '';
            if (labelSpan) labelSpan.textContent = 'Mengunduh...';
            el.style.pointerEvents = 'none';
            el.style.opacity = '0.75';
            try {
                const res = await fetch(url, { credentials: 'same-origin' });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const disp = res.headers.get('Content-Disposition') || '';
                let filename = '{{ $rfq->rfq_number }}_Penawaran.pdf';
                const match = disp.match(/filename\*?=(?:UTF-8'')?["']?([^"';\n]+)["']?/i);
                if (match && match[1]) filename = decodeURIComponent(match[1]);
                const blob = await res.blob();
                const blobUrl = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    window.URL.revokeObjectURL(blobUrl);
                    a.remove();
                }, 1500);
            } catch (err) {
                window.location.href = url;
            } finally {
                if (labelSpan) labelSpan.textContent = origText;
                el.style.pointerEvents = '';
                el.style.opacity = '';
            }
            return false;
        }
    </script>
</body>
</html>
