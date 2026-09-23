<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Quotation {{ $rfq->quotation_number }}</title>
    <style>
        @page {
            margin: 25px 35px 20px 35px;
            size: a4 portrait;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/msgothic.ttf')) }}') format('truetype');
        }
        body {
            font-family: 'JapaneseFont', 'DejaVu Sans', sans-serif;
            font-size: 9px;
            color: #111;
            margin: 0;
            padding: 0;
            line-height: 1.3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .italic { font-style: italic; }
    </style>
</head>
<body>

    <!-- HEADER TABLE -->
    <table style="width: 100%; border: none;">
        <tr>
            <td style="width: 55%; vertical-align: top; border: none;">
                @if(file_exists(public_path('images/pedia_logo_hd.png')))
                    <img src="{{ public_path('images/pedia_logo_hd.png') }}" style="height: 48px;">
                @elseif(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" style="height: 48px;">
                @else
                    <strong style="font-size: 14px; color: #000;">PEDIA TECHNOLOGY</strong>
                @endif
                <div style="font-weight: bold; font-size: 11px; text-transform: uppercase; margin-top: 4px; color: #000;">PT. PEDIA TEKNOLOGI INDONESIA</div>
                <div style="font-style: italic; font-size: 9px; color: #555;">With Our Experience Everything Is Possible</div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right; border: none;">
                <div style="font-size: 36px; color: #666; font-weight: normal; line-height: 1; margin-bottom: 6px;">Quotation</div>
                <table style="width: auto; float: right; font-size: 9px; border-collapse: collapse;">
                    <tr>
                        <td style="font-weight: bold; padding-right: 12px; text-align: right; text-transform: uppercase;">TANGGAL</td>
                        <td style="text-align: right;">{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding-right: 12px; text-align: right;">No. Penawaran</td>
                        <td style="text-align: right;">{{ $rfq->quotation_number }}</td>
                    </tr>
                    @if(isset($revisionCount) && $revisionCount > 0)
                    <tr>
                        <td colspan="2" style="color: #dc2626; font-weight: bold; text-align: right; text-transform: uppercase; padding-top: 2px;">
                            REVISI {{ $revisionCount }} : {{ date('d M Y') }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    @php
        $clientCompanyName = $rfq->customer?->company_name ?: ($rfq->customer_name ?: 'Pelanggan');
        $custAddress       = trim($rfq->customer?->address ?? '');
        $custPhone         = $rfq->customer?->phone ?: ($rfq->customerContact?->phone ?: $rfq->customerContact?->office_phone);
        $custPic           = $rfq->customerContact?->name ?: ($rfq->customer?->cp_name ?: 'Bpk/Ibu');
        $salesPhone        = $rfq->sales?->phone ?: '021-3971-2155';
        $salesEmail        = $rfq->sales?->email ?: 'info@pedia-technology.co.id';
        $salesName         = $rfq->sales?->name ?: ($rfq->sales_name ?: 'Ade Zulvida');
        $salesRole         = $rfq->sales?->role ?: 'Sales Marketing';
        $validityDays      = $rfq->items->max('validity_days') ?: 7;
    @endphp

    <!-- INFO KLIEN & BERLAKU S/D -->
    <table style="width: 100%; margin-top: 10px; margin-bottom: 4px; border: none;">
        <tr>
            <td style="width: 60%; vertical-align: bottom; font-size: 9px; line-height: 1.35; border: none;">
                <div style="color: #111;">Kepada YTH</div>
                <div style="font-weight: bold; font-size: 11px; color: #000; margin-top: 1px;">{{ $clientCompanyName }}</div>
                @if(!empty($custAddress))
                <div style="color: #222;">{!! nl2br(e($custAddress)) !!}</div>
                @endif
                @if(!empty($custPhone))
                <div style="color: #222;">Telp : {{ $custPhone }}</div>
                @endif
                <div style="color: #222;">Up. {{ $custPic }}</div>
            </td>
            <td style="width: 40%; vertical-align: bottom; text-align: right; border: none;">
                <table style="width: auto; float: right; border-collapse: collapse;">
                    <tr>
                        <td style="background-color: #f2f2f2; padding: 4px 10px; font-size: 9px; width: 190px;">
                            <span style="font-style: italic; color: #333;">Berlaku s/d tgl :</span>
                            <span style="float: right; font-weight: bold; color: #111;">{{ \Carbon\Carbon::parse($rfq->rfq_date)->addDays($validityDays)->format('d M Y') }}</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- KALIMAT PEMBUKA -->
    <div style="font-size: 9px; margin-top: 10px; margin-bottom: 6px; color: #111;">
        Berikut penawaran dari kami ・ 下記の通り御見積申し上げます。
    </div>

    <!-- TABEL PENAWARAN -->
    <table style="width: 100%; border-collapse: collapse; border: 1px solid #888;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #888; padding: 5px; font-size: 9px; width: 55px; text-align: center; text-transform: uppercase;">JUMLAH</th>
                <th style="border: 1px solid #888; padding: 5px; font-size: 9px; text-align: center; text-transform: uppercase;">DESKRIPSI</th>
                <th style="border: 1px solid #888; padding: 5px; font-size: 9px; width: 95px; text-align: center; text-transform: uppercase;">HARGA</th>
                <th style="border: 1px solid #888; padding: 5px; font-size: 9px; width: 110px; text-align: center; text-transform: uppercase;">JUMLAH HARGA</th>
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
                <tr>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 5px; text-align: center; font-weight: bold; font-size: 9px; vertical-align: top;">
                        {{ (int) $item->qty }} {{ $item->unit ?: 'Unit' }}
                    </td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 5px 8px; font-size: 9px; line-height: 1.35; vertical-align: top;">
                        <div style="font-weight: bold; font-size: 9.5px;">{{ $item->product_name }}</div>
                        @if($item->detail_item || $item->description)
                        <div style="color: #333; margin-top: 2px;">{!! nl2br(e($item->detail_item ?? $item->description)) !!}</div>
                        @endif
                    </td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 5px; font-size: 9px; font-weight: bold; vertical-align: top;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none;">Rp</td><td style="text-align: right; padding: 0; border: none;">{{ number_format($unitPrice, 0, ',', '.') }}</td></tr></table>
                    </td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 5px; font-size: 9px; font-weight: bold; vertical-align: top;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none;">Rp</td><td style="text-align: right; padding: 0; border: none;">{{ number_format($rowTotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endforeach

            @if(!empty(trim($rfq->notes ?? '')))
            <tr>
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px;"></td>
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 6px 8px; font-size: 8.5px; line-height: 1.35;">
                    <div style="font-weight: bold; color: #000; margin-bottom: 2px;">Note :</div>
                    <div style="font-style: italic; color: #333;">{!! nl2br(e($rfq->notes)) !!}</div>
                </td>
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px;"></td>
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px;"></td>
            </tr>
            @endif

            @php
                $ppn = $grandSubtotal * 0.11;
                $grandTotal = $grandSubtotal + $ppn;
            @endphp

            <!-- TOTAL ROW -->
            <tr style="border-top: 1px solid #888; border-bottom: 1px solid #888; font-weight: bold; font-size: 9.5px;">
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px; text-align: center;">{{ (int) $totalQty }} Unit</td>
                <td colspan="2" style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px 12px; text-align: right; text-transform: uppercase;">TOTAL</td>
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px;">
                    <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none;">Rp</td><td style="text-align: right; padding: 0; border: none;">{{ number_format($grandSubtotal, 0, ',', '.') }}</td></tr></table>
                </td>
            </tr>

            <!-- PPN ROW -->
            <tr style="border-bottom: 1px solid #888; font-weight: bold; font-size: 9.5px;">
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px;"></td>
                <td colspan="2" style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px 12px; text-align: right;">PPn</td>
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px;">
                    <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none;">Rp</td><td style="text-align: right; padding: 0; border: none;">{{ number_format($ppn, 0, ',', '.') }}</td></tr></table>
                </td>
            </tr>

            <!-- TOTAL HARGA ROW -->
            <tr style="border-bottom: 1px solid #888; font-weight: bold; font-size: 9.5px;">
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px;"></td>
                <td colspan="2" style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px 12px; text-align: right; text-transform: uppercase;">TOTAL HARGA</td>
                <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 4px;">
                    <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none;">Rp</td><td style="text-align: right; padding: 0; border: none;">{{ number_format($grandTotal, 0, ',', '.') }}</td></tr></table>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- SYARAT & KETENTUAN (Persis Sampling) -->
    <div style="font-size: 8.5px; line-height: 1.4; margin-top: 10px; margin-bottom: 8px;">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 16px; vertical-align: top; font-weight: bold; border: none;">✓</td>
                <td style="border: none;">
                    <div>Sistem pembayaran 14 hari setelah invoice diterima</div>
                    <div style="color: #555;">お支払い条件は、請求書受領後14日以内となります。</div>
                </td>
            </tr>
            <tr>
                <td style="width: 16px; vertical-align: top; font-weight: bold; padding-top: 4px; border: none;">✓</td>
                <td style="padding-top: 4px; border: none;">
                    <div>Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku. Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                    <div style="color: #555;">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。ご署名後はキャンセルできませんのでご了承ください。</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- PENUTUP -->
    <div style="font-size: 8.5px; color: #222; margin-bottom: 12px; line-height: 1.35;">
        Demikian penawaran ini kami kirimkan. Jika ada hal yang ingin ditanyakan, dapat menghubungi saya di nomor telp : <strong>{{ $salesPhone }}</strong> atau e-mail <strong>{{ $salesEmail }}</strong>. Atas perhatian dan kepercayaan nya kami ucapkan Terima Kasih.
    </div>

    <!-- TANDA TANGAN (Persis Sampling) -->
    <table style="width: 100%; font-size: 9px; margin-top: 5px; margin-bottom: 15px; border: none;">
        <tr>
            <td style="width: 50%; vertical-align: top; border: none;">
                <div>Hormat Kami</div>
                <div style="margin: 2px 0;">
                    @if(file_exists(public_path('images/pedia_ttd_stamp.png')))
                        <img src="{{ public_path('images/pedia_ttd_stamp.png') }}" style="height: 52px;">
                    @else
                        <div style="height: 50px;"></div>
                    @endif
                </div>
                <div style="font-weight: bold; font-size: 9.5px; margin-top: 2px;">{{ $salesName }}</div>
                <div style="font-style: italic; color: #555;">{{ $salesRole }}</div>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 20px; border: none;">
                <div>Kami menyetujui dengan qty dan harga yang ditawarkan di atas.</div>
                <div style="font-size: 8px; color: #555; margin-bottom: 3px;">上記の数量と価格に同意いたします。</div>
                <div style="font-weight: bold; font-size: 9.5px; margin-bottom: 25px;">{{ $clientCompanyName }}</div>
                
                <table style="width: 100%; border: none;">
                    <tr><td style="width: 50px; font-size: 8.5px; border: none; padding: 1px 0;">Nama</td><td style="font-size: 8.5px; border: none; padding: 1px 0;">: _________________</td></tr>
                    <tr><td style="font-size: 8.5px; border: none; padding: 1px 0;">Jabatan</td><td style="font-size: 8.5px; border: none; padding: 1px 0;">: _________________</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- FOOTER (Persis Sampling) -->
    <div style="text-align: center; margin-top: 15px; font-size: 8px; color: #222;">
        <div style="font-weight: bold; font-size: 9.5px; letter-spacing: 0.5px; text-transform: uppercase;">THANK YOU FOR YOUR BUSINESS!</div>
        <div style="font-size: 8.5px; margin-bottom: 4px;">お買い上げくださってありがとうございます！</div>
        <div style="line-height: 1.35; color: #555; margin-bottom: 8px;">
            Rukan Rose Garden Blok RRGB No. 93, Jl. Grand Galaxy City Central Park 3,<br>
            Kel. Jaka Setia, Kec. Bekasi Selatan, Kota Bekasi, Jawa Barat 17147<br>
            Telp : (+62) 021-3971-2155, Fax : (+62) 021-3970-0175
        </div>
        <div style="background-color: #005a9c; color: #fff; padding: 5px; font-size: 9px; font-weight: bold; letter-spacing: 0.5px;">
            website : https://pedia-technology.co.id
        </div>
    </div>

</body>
</html>
