<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $rfq->quotation_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td { border: none; vertical-align: top; }
        .company-info { text-align: right; font-size: 9px; line-height: 1.35; color: #475569; }
        .title-doc { text-align: center; font-size: 18px; font-weight: bold; margin: 10px 0 5px 0; letter-spacing: 2px; color: #1e293b; text-transform: uppercase; }
        .info-table { margin-bottom: 10px; }
        .info-table td { border: none; vertical-align: top; padding: 2px 0; }
        .info-table .label { width: 70px; font-weight: bold; color: #475569; }
        .info-table .colon { width: 10px; }
        
        .items-table { margin-top: 5px; width: 100%; border: 1px solid #334155; }
        .items-table th { background-color: #f1f5f9; font-weight: bold; text-align: center; padding: 6px 4px; border: 1px solid #334155; font-size: 9.5px; color: #1e293b; }
        .items-table td { border: 1px solid #cbd5e1; padding: 5px 4px; vertical-align: top; line-height: 1.35; font-size: 9px; }
        .items-table .text-center { text-align: center; }
        .items-table .text-right { text-align: right; }
        .items-table .group-row td { background-color: #e2e8f0; font-weight: bold; text-align: left; padding: 5px; color: #1e293b; border-top: 1px solid #334155; border-bottom: 1px solid #334155; }
        
        .summary-table td { border: none; padding: 4px; font-weight: bold; text-align: right; font-size: 9.5px; }
        .summary-table .summary-value { border: 1px solid #334155; width: 120px; text-align: right; padding-right: 5px; background-color: #f8fafc; }
        
        .notes { font-size: 8.5px; margin-top: 8px; line-height: 1.35; color: #334155; }
        .notes p { margin: 2px 0; }
        .bilingual { font-style: italic; color: #64748b; }
        
        .bank-table { width: 100%; margin-top: 8px; border: 1px solid #cbd5e1; background-color: #f8fafc; }
        .bank-table td { padding: 4px 8px; font-size: 8.5px; vertical-align: top; }
        
        .signature-table { margin-top: 15px; width: 100%; }
        .signature-table td { border: none; vertical-align: top; font-size: 9px; line-height: 1.35; }
        
        .revision-banner { color: #dc2626; font-size: 12px; font-weight: bold; text-align: center; margin-top: -5px; margin-bottom: 8px; }
        .footer-note { margin-top: 10px; text-align: center; border-top: 1px solid #cbd5e1; padding-top: 5px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="55%">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo Pedia" style="max-width: 170px; max-height: 45px;">
                @else
                    <strong style="font-size: 15px; color: #005a9c;">PT. PEDIA TEKNOLOGI INDONESIA</strong>
                @endif
                <div style="font-size: 9px; font-style: italic; color: #64748b; margin-top: 2px;">With Our Experience Everything Is Possible</div>
            </td>
            <td width="45%" class="company-info">
                <strong style="font-size: 11px; color: #005a9c;">PT. PEDIA TEKNOLOGI INDONESIA</strong><br>
                Rukan Rose Garden Blok RRGB No. 93, Grand Galaxy City<br>
                Kota Bekasi, Jawa Barat 17147<br>
                Telp: (+62) 021-3971-2155 | Email: sales@pedia-technology.co.id<br>
                Website: https://pedia-technology.co.id
            </td>
        </tr>
    </table>

    <div class="title-doc">QUOTATION</div>

    @if(isset($revisionCount) && $revisionCount > 0)
        <div class="revision-banner">REVISI {{ $revisionCount }} : {{ date('d F Y') }}</div>
    @endif

    <table class="info-table">
        <tr>
            <td width="55%">
                <table>
                    <tr><td class="label">Kepada / To</td><td class="colon">:</td><td><strong>{{ $rfq->customer->name ?? '-' }}</strong></td></tr>
                    <tr><td class="label">Attn / UP</td><td class="colon">:</td><td>{{ $rfq->customerContact->name ?? 'Bpk/Ibu' }}</td></tr>
                    <tr><td class="label">Alamat / Addr</td><td class="colon">:</td><td>{{ $rfq->customer->address ?? '-' }}</td></tr>
                    <tr><td class="label">Telepon</td><td class="colon">:</td><td>{{ $rfq->customer->phone ?? '-' }}</td></tr>
                </table>
            </td>
            <td width="45%">
                <table>
                    <tr><td class="label">Tanggal / Date</td><td class="colon">:</td><td>{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d F Y') }}</td></tr>
                    <tr><td class="label">No. Penawaran</td><td class="colon">:</td><td><strong>{{ $rfq->quotation_number }}</strong></td></tr>
                    <tr><td class="label">Berlaku s/d</td><td class="colon">:</td><td>{{ \Carbon\Carbon::parse($rfq->rfq_date)->addDays(7)->format('d F Y') }}</td></tr>
                    <tr><td class="label">Projek</td><td class="colon">:</td><td><strong>{{ $rfq->notes ?? 'Pengadaan Barang & Jasa' }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="47%">Description & Specification</th>
                <th width="7%">Qty</th>
                <th width="9%">Unit</th>
                <th width="15%">Unit Price (Rp)</th>
                <th width="17%">Total Price (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $grandSubtotal = 0;
                $no = 1;
                $groups = $rfq->items->groupBy('category');
            @endphp
            
            @foreach(['Hardware', 'Jasa Pemasangan', 'Material Support'] as $catName)
                @if(isset($groups[$catName]) && $groups[$catName]->count() > 0)
                    <tr class="group-row">
                        <td colspan="6">{{ strtoupper($catName) }}</td>
                    </tr>
                    @foreach($groups[$catName] as $item)
                        @php
                            $unitPrice = $item->price_after_margin;
                            $rowTotal = $unitPrice * $item->qty;
                            $grandSubtotal += $rowTotal;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $no++ }}</td>
                            <td>
                                <strong>{{ $item->product_name }}</strong><br>
                                {!! nl2br(e($item->detail_item ?? $item->description)) !!}
                            </td>
                            <td class="text-center">{{ (int) $item->qty }}</td>
                            <td class="text-center">{{ $item->unit }}</td>
                            <td class="text-right">{{ number_format($unitPrice, 0, ',', '.') }}</td>
                            <td class="text-right">{{ number_format($rowTotal, 0, ',', '.') }}</td>
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
                <tr class="group-row">
                    <td colspan="6">LAIN-LAIN / OTHER ITEMS</td>
                </tr>
                @foreach($otherItems as $item)
                    @php
                        $unitPrice = $item->price_after_margin;
                        $rowTotal = $unitPrice * $item->qty;
                        $grandSubtotal += $rowTotal;
                    @endphp
                    <tr>
                        <td class="text-center">{{ $no++ }}</td>
                        <td>
                            <strong>{{ $item->product_name }}</strong><br>
                            {!! nl2br(e($item->detail_item ?? $item->description)) !!}
                        </td>
                        <td class="text-center">{{ (int) $item->qty }}</td>
                        <td class="text-center">{{ $item->unit }}</td>
                        <td class="text-right">{{ number_format($unitPrice, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format($rowTotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    @php
        $ppn = $grandSubtotal * 0.11;
        $grandTotal = $grandSubtotal + $ppn;
    @endphp

    <table style="width: 100%; margin-top: 6px;">
        <tr>
            <td width="55%" style="vertical-align: top;">
                <div style="font-size: 8px; color: #dc2626; font-style: italic; line-height: 1.3;">
                    <strong>Catatan / Note:</strong><br>
                    &bull; Stok unit terbatas dan tidak mengikat sebelum terbit PO resmi / <em>Stock is limited and subject to prior sales</em><br>
                    &bull; Harga dapat berubah tanpa pemberitahuan / <em>Prices subject to change without prior notice</em>
                </div>
            </td>
            <td width="45%">
                <table class="summary-table">
                    <tr>
                        <td>SUBTOTAL</td>
                        <td class="summary-value">{{ number_format($grandSubtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>PPN 11%</td>
                        <td class="summary-value">{{ number_format($ppn, 0, ',', '.') }}</td>
                    </tr>
                    <tr style="font-size: 10.5px;">
                        <td style="color: #005a9c;">GRAND TOTAL</td>
                        <td class="summary-value" style="color: #005a9c;"><strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- TERMS & CONDITIONS (BILINGUAL ID & EN) -->
    <div class="notes">
        <strong style="color: #1e293b;">Syarat & Ketentuan / Terms & Conditions:</strong>
        <p>1. Sistem pembayaran 14 hari kalender setelah invoice diterima. <span class="bilingual">(Payment terms: 14 calendar days upon invoice receipt).</span></p>
        <p>2. Harga sudah termasuk PPn 11% dan berlaku selama 7 hari kalender. <span class="bilingual">(Price is inclusive of 11% VAT and valid for 7 calendar days).</span></p>
        <p>3. Ketersediaan stok tidak mengikat sebelum terbit PO resmi dari Pemesan. <span class="bilingual">(Stock is not binding prior to official PO).</span></p>
        <p>4. Dengan menandatangani penawaran ini, Pemesan menyetujui kuantiti, harga, dan ketentuan berlaku. <span class="bilingual">(By signing, Client accepts all items, prices, and terms).</span></p>
    </div>

    <!-- REKENING BANK RESMI PT -->
    <table class="bank-table">
        <tr>
            <td colspan="2" style="font-weight: bold; color: #1e293b; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px;">
                Pembayaran dapat ditransfer ke Rekening Resmi Perusahaan / Official Payment Bank Accounts:
            </td>
        </tr>
        <tr>
            <td width="50%" style="border-right: 1px dashed #cbd5e1; padding-top: 3px;">
                <strong style="color: #005a9c;">Bank Central Asia (BCA)</strong><br>
                No. Rekening: <strong>5415-888-999</strong><br>
                A/N: <strong>PT. PEDIA TEKNOLOGI INDONESIA</strong><br>
                <span style="color: #64748b; font-size: 8px;">KCP Grand Galaxy City Bekasi</span>
            </td>
            <td width="50%" style="padding-top: 3px; padding-left: 8px;">
                <strong style="color: #d97706;">Bank Mandiri</strong><br>
                No. Rekening: <strong>156-00-1789-8889</strong><br>
                A/N: <strong>PT. PEDIA TEKNOLOGI INDONESIA</strong><br>
                <span style="color: #64748b; font-size: 8px;">KC Bekasi Juanda</span>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td width="50%">
                Hormat Kami / <em>Sincerely,</em><br>
                <strong>PT. PEDIA TEKNOLOGI INDONESIA</strong>
                <div style="height: 50px;"></div>
                <strong style="text-decoration: underline;">{{ $rfq->sales->name ?? 'Sales Representative' }}</strong><br>
                {{ $rfq->sales->role ?? 'Account Manager' }}<br>
                <span style="color: #64748b; font-size: 8px;">{{ $rfq->sales->email ?? 'sales@pedia-technology.co.id' }}</span>
            </td>
            <td width="50%" style="padding-left: 30px;">
                Disetujui Oleh / <em>Accepted by:</em><br>
                <strong>{{ $rfq->customer->name ?? 'Klien' }}</strong>
                <div style="height: 50px;"></div>
                ___________________________________<br>
                Nama & Cap Perusahaan
            </td>
        </tr>
    </table>

    <div class="footer-note">
        <strong>PT. PEDIA TEKNOLOGI INDONESIA</strong> &bull; Rukan Rose Garden Blok RRGB No. 93, Jl. Grand Galaxy City Central Park 3, Kota Bekasi 17147<br>
        Telp: (+62) 021-3971-2155 | Fax: (+62) 021-3970-0175 | Website: https://pedia-technology.co.id
    </div>

</body>
</html>
