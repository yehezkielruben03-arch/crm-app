<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Quotation {{ $rfq->quotation_number }}</title>
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

        $salesSigPath      = null;
        if ($sigUser && $sigUser->signature_path) {
            if (file_exists(storage_path('app/public/' . $sigUser->signature_path))) {
                $salesSigPath = storage_path('app/public/' . $sigUser->signature_path);
            } elseif (file_exists(public_path('storage/' . $sigUser->signature_path))) {
                $salesSigPath = public_path('storage/' . $sigUser->signature_path);
            } elseif (file_exists(public_path($sigUser->signature_path))) {
                $salesSigPath = public_path($sigUser->signature_path);
            }
        }
        $validityDays      = $rfq->items->max('validity_days') ?: 7;

        $romanMap = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X'];
        $revRoman = $romanMap[$revisionCount ?? 0] ?? ($revisionCount ?? 1);
        $formattedRevDate = isset($revisionDate) ? \Carbon\Carbon::parse($revisionDate)->format('d M Y') : date('d M Y');

        $quotationItems    = $rfq->customer_quotation_items;
        $itemCount         = count($quotationItems);
        $totalSpecLines    = 0;
        foreach ($quotationItems as $qItem) {
            $specText = trim((string) ($qItem->description ?: $qItem->detail_item));
            if ($specText !== '') {
                foreach (preg_split('/\r\n|\r|\n/', $specText) as $sLine) {
                    $sLine = trim($sLine);
                    if ($sLine !== '') {
                        $totalSpecLines += max(1, (int) ceil(mb_strlen($sLine) / 68));
                    }
                }
            }
        }

        $customNotes = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $rfq->notes ?? ''))), function($line) {
            $clean = strtolower(trim(ltrim($line, '- ')));
            return $clean !== '' && !str_contains($clean, 'harga dapat berubah') && !str_contains($clean, 'tanyakan stok');
        });
        $noteLineCount = count($customNotes) + 2;

        // Visual weight mengestimasi total baris vertikal di dalam tabel
        $visualWeight = ($itemCount * 2) + $totalSpecLines + $noteLineCount;

        $density = match(true) {
            $visualWeight <= 10 && $itemCount <= 2 => 'normal',
            $visualWeight <= 17 && $itemCount <= 3 => 'medium',
            default => 'compact',
        };

        // Hitung sisa ruang vertikal ideal untuk spasi di atas "Note :" agar 1 halaman A4 terisi proporsional tanpa pernah menabrak footer
        $freeLines = max(0, 14 - $visualWeight);
        $noteTopMarginPt = $density === 'compact' ? 2 : max(4, min(48, $freeLines * 4));
        $noteTopMargin = $noteTopMarginPt . 'pt';

        $pageMargin    = match($density) { 'compact' => '6mm 13mm 3mm 13mm', 'medium' => '7.5mm 13mm 3.5mm 13mm', default => '10mm 13mm 5mm 13mm' };
        $logoWidth     = match($density) { 'compact' => '114pt', 'medium' => '128pt', default => '145pt' };
        $quoTitleSize  = match($density) { 'compact' => '30pt', 'medium' => '34pt', default => '40pt' };
        $quoTitleMb    = match($density) { 'compact' => '2pt', 'medium' => '6pt', default => '11pt' };
        $itemTitleSize = match($density) { 'compact' => '7.8pt', 'medium' => '9.5pt', default => '10.5pt' };
        $itemSpecSize  = match($density) { 'compact' => '6.6pt', 'medium' => '8.0pt', default => '8.9pt' };
        $specLineH     = match($density) { 'compact' => '0.98', 'medium' => '1.02', default => '1.06' };
        $cellPad       = match($density) { 'compact' => '0.8pt 3pt', 'medium' => '2pt 3.5pt', default => '3pt 4pt' };
        $totPad        = match($density) { 'compact' => '1pt 3pt', 'medium' => '2pt 3.5pt', default => '3pt 4pt' };
        $totFontSize   = match($density) { 'compact' => '7.9pt', 'medium' => '9.5pt', default => '10.5pt' };
        $termFontSize  = match($density) { 'compact' => '7.2pt', 'medium' => '8.0pt', default => '8.7pt' };
        $termJpSize    = match($density) { 'compact' => '6.8pt', 'medium' => '7.6pt', default => '8.5pt' };
        $sigBoxHeight  = match($density) { 'compact' => '28pt', 'medium' => '38pt', default => '50pt' };
        $stampWidth    = match($density) { 'compact' => '68pt', 'medium' => '80pt', default => '94pt' };
        $sigImgHeight  = match($density) { 'compact' => '28pt', 'medium' => '38pt', default => '50pt' };
        $footerSpacer  = match($density) { 'compact' => '54pt', 'medium' => '60pt', default => '68pt' };
        $footerImgW    = match($density) { 'compact' => '78%', 'medium' => '85%', default => '92%' };
    @endphp
    <style>
        @page {
            size: A4 portrait;
            margin: {{ $pageMargin }};
        }
        @if(file_exists(public_path('fonts/calibril.ttf')))
        @font-face {
            font-family: 'CalibriLight';
            src: url('{{ str_replace('\\', '/', public_path('fonts/calibril.ttf')) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @endif
        @if(file_exists(public_path('fonts/calibri.ttf')))
        @font-face {
            font-family: 'CalibriFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/calibri.ttf')) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @endif
        @if(file_exists(public_path('fonts/calibrib.ttf')))
        @font-face {
            font-family: 'CalibriFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/calibrib.ttf')) }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @endif
        @if(file_exists(public_path('fonts/calibrii.ttf')))
        @font-face {
            font-family: 'CalibriFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/calibrii.ttf')) }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }
        @endif
        @if(file_exists(public_path('fonts/calibriz.ttf')))
        @font-face {
            font-family: 'CalibriFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/calibriz.ttf')) }}') format('truetype');
            font-weight: bold;
            font-style: italic;
        }
        @endif
        @if(file_exists(public_path('fonts/arial.ttf')))
        @font-face {
            font-family: 'ArialFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/arial.ttf')) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @endif
        @if(file_exists(public_path('fonts/arialbd.ttf')))
        @font-face {
            font-family: 'ArialFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/arialbd.ttf')) }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @endif
        @if(file_exists(public_path('fonts/ariali.ttf')))
        @font-face {
            font-family: 'ArialFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/ariali.ttf')) }}') format('truetype');
            font-weight: normal;
            font-style: italic;
        }
        @endif
        @if(file_exists(public_path('fonts/msgothic.ttf')))
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/msgothic.ttf')) }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'JapaneseFont';
            src: url('{{ str_replace('\\', '/', public_path('fonts/msgothic.ttf')) }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        @endif
        body {
            font-family: 'CalibriFont', 'Helvetica', 'Arial', sans-serif;
            font-size: 8.9pt;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.12;
        }
        .arial {
            font-family: 'ArialFont', 'Helvetica', 'Arial', sans-serif;
        }
        .jp {
            font-family: 'JapaneseFont', sans-serif;
            font-weight: normal;
            font-style: normal;
            word-break: break-all;
            word-wrap: break-word;
            overflow-wrap: anywhere;
        }
        .check {
            font-family: 'DejaVu Sans', sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .terms-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: none;
        }
        .terms-table td {
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .main-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #808080;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .italic { font-style: italic; }
    </style>
</head>
<body>

    <!-- HEADER TABLE (Grid 65% Kiri / 35% Kanan sejajar dengan kolom HARGA + JUMLAH HARGA) -->
    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 1pt;">
        <tr>
            <td style="width: 65%; vertical-align: top; border: none; padding: 0;">
                @if(file_exists(public_path('images/pedia_logo_hd.png')))
                    <img src="{{ public_path('images/pedia_logo_hd.png') }}" style="width: {{ $logoWidth }}; height: auto; display: block;">
                @elseif(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" style="width: {{ $logoWidth }}; height: auto; display: block;">
                @else
                    <strong style="font-size: 14pt; color: #000;">PEDIA TECHNOLOGY</strong>
                @endif
                <div style="font-weight: bold; font-size: 9.8pt; text-transform: uppercase; margin-top: 2pt; color: #000; line-height: 1.1;">PT. PEDIA TEKNOLOGI INDONESIA</div>
                <div style="font-weight: bold; font-style: italic; font-size: 7.6pt; color: #7f7f7f; line-height: 1.1; padding-left: 7pt;">With Our Experience Everything Is Possible</div>
            </td>
            <td style="width: 35%; vertical-align: top; text-align: right; border: none; padding: 0;">
                <div style="font-family: 'CalibriFont', sans-serif; font-size: {{ $quoTitleSize }}; color: #7f7f7f; line-height: 0.92; margin-top: 2pt; margin-bottom: {{ $quoTitleMb }}; font-weight: normal; text-align: right;">Quotation</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 8.9pt;">
                    <tr>
                        <td style="width: 50%; font-weight: bold; padding: 1pt 4pt 1pt 0; text-align: right; text-transform: uppercase; border: none;">TANGGAL</td>
                        <td style="width: 50%; text-align: right; padding: 1pt 0; border: none;">{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding: 1pt 4pt 1pt 0; text-align: right; border: none;">No. Penawaran</td>
                        <td style="text-align: right; padding: 1pt 0; border: none;">{{ $rfq->quotation_number }}</td>
                    </tr>
                    @if(isset($revisionCount) && $revisionCount > 0)
                    <tr>
                        <td colspan="2" style="color: #ff0000; font-weight: bold; text-align: right; padding: 1pt 0 0 0; border: none;">
                            REVISI {{ $revRoman }} : {{ $formattedRevDate }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- INFO KLIEN & BERLAKU S/D (Kiri 65% Arial, Kanan 35% sejajar kolom HARGA + JUMLAH HARGA) -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 1pt; margin-bottom: {{ $density === 'compact' ? '2pt' : '6pt' }}; border: none;">
        <tr>
            <td class="arial" style="width: 65%; vertical-align: bottom; font-size: {{ $density === 'compact' ? '8.4pt' : '8.9pt' }}; line-height: {{ $density === 'compact' ? '1.08' : '1.14' }}; border: none; padding: 0;">
                <div style="color: #000;">Kepada YTH</div>
                <div style="font-weight: bold; font-size: {{ $density === 'compact' ? '9.2pt' : '9.8pt' }}; color: #000; line-height: 1.15; margin-top: 1pt; margin-bottom: 1pt;">{{ $clientCompanyName }}</div>
                @if(!empty($custAddress))
                    @foreach(preg_split('/\r\n|\r|\n/', trim($custAddress)) as $addrLine)
                        @if(trim($addrLine) !== '')
                        <div style="color: #000;">{{ trim($addrLine) }}</div>
                        @endif
                    @endforeach
                @endif
                @if(!empty($custPhone))
                <div style="color: #000;">Telp : {{ $custPhone }}</div>
                @endif
                @if(!empty($custPic))
                <div style="color: #000;">Up. {{ $custPic }}</div>
                @endif
            </td>
            <td style="width: 35%; vertical-align: bottom; text-align: right; border: none; padding: 0;">
                <table style="width: 100%; border-collapse: collapse; background-color: #f2f2f2;">
                    <tr>
                        <td class="arial" style="width: 50%; padding: 2pt 4pt; font-size: 8.9pt; font-style: italic; color: #000; text-align: center; border: none;">Berlaku s/d tgl :</td>
                        <td style="width: 50%; padding: 2pt 2pt; font-size: 8.9pt; color: #000; text-align: right; border: none;">{{ $rfq->formatted_valid_until }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- KALIMAT PEMBUKA -->
    <div style="font-size: {{ $density === 'compact' ? '8.8pt' : '9.8pt' }}; margin-top: {{ $density === 'compact' ? '2pt' : '5pt' }}; margin-bottom: 2pt; color: #000; line-height: 1.1;">
        Berikut penawaran dari kami<span class="jp" style="font-size: {{ $density === 'compact' ? '8.5pt' : '9.5pt' }};">・下記の通り御見積申し上げます。</span>
    </div>

    <!-- TABEL PENAWARAN (PERSIS SAMPLING: 9.6% | 55.4% | 17.5% | 17.5%) -->
    <table class="main-table">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #808080; padding: {{ $density === 'compact' ? '2pt 2pt' : '3pt 2pt' }}; font-weight: bold; font-size: 8.7pt; width: 9.6%; text-align: center; text-transform: uppercase;">JUMLAH</th>
                <th style="border: 1px solid #808080; padding: {{ $density === 'compact' ? '2pt 4pt' : '3pt 4pt' }}; font-weight: bold; font-size: 8.7pt; width: 55.4%; text-align: center; text-transform: uppercase;">DESKRIPSI</th>
                <th style="border: 1px solid #808080; padding: {{ $density === 'compact' ? '2pt 4pt' : '3pt 4pt' }}; font-weight: bold; font-size: 8.7pt; width: 17.5%; text-align: center; text-transform: uppercase;">HARGA</th>
                <th style="border: 1px solid #808080; padding: {{ $density === 'compact' ? '2pt 4pt' : '3pt 4pt' }}; font-weight: bold; font-size: 8.7pt; width: 17.5%; text-align: center; text-transform: uppercase;">JUMLAH HARGA</th>
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
                <tr>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: {{ $cellPad }}; font-weight: bold; font-size: {{ $itemTitleSize }}; vertical-align: top; line-height: 1.08;">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 40%; text-align: right; padding: 0 2pt 0 0; border: none; font-weight: bold;">{{ (int) $item->qty }}</td>
                                <td style="width: 60%; text-align: left; padding: 0 0 0 2pt; border: none; font-weight: bold;">{{ ucwords(strtolower(trim($item->unit ?: 'Unit'))) }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: {{ $cellPad }}; font-size: 8.9pt; line-height: 1.08; vertical-align: top;">
                        <div style="font-weight: bold; font-size: {{ $itemTitleSize }}; color: #000; line-height: 1.08;">{{ ucfirst($item->product_name) }}</div>
                        @php $itemSpec = trim((string) ($item->description ?: $item->detail_item)); @endphp
                        @if($itemSpec !== '')
                        <div style="color: #000; margin-top: 1pt; font-size: {{ $itemSpecSize }}; font-style: italic;">
                            @foreach(preg_split('/\r\n|\r|\n/', ucfirst($itemSpec)) as $specLine)
                                @if(trim($specLine) !== '')
                                <div style="line-height: {{ $specLineH }};">{{ trim($specLine) }}</div>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        @if($loop->last)
                        <div style="margin-top: {{ $noteTopMargin }}; font-size: {{ $itemSpecSize }}; line-height: {{ $specLineH }};">
                            <div style="font-weight: bold; font-size: {{ $itemTitleSize }}; color: #000; margin-bottom: 1pt; line-height: 1.05;">Note :</div>
                            @foreach($customNotes as $cNote)
                                @php
                                    $formattedNote = str_starts_with($cNote, '-') ? $cNote : '- ' . $cNote;
                                @endphp
                                <div style="font-style: italic; color: #ff0000; line-height: {{ $specLineH }}; font-weight: bold;">{{ $formattedNote }}</div>
                            @endforeach
                            <div style="font-style: italic; color: #000; line-height: {{ $specLineH }};">- Harga dapat berubah tanpa pemberitahuan</div>
                            <div style="font-style: italic; color: #000; line-height: {{ $specLineH }};">- Mohon tanyakan stok terlebih dahulu sebelum mengirim PO</div>
                        </div>
                        @endif
                    </td>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: {{ $cellPad }}; font-size: {{ $itemTitleSize }}; font-weight: bold; vertical-align: top; line-height: 1.08;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($unitPrice, 0, ',', '.') }}</td></tr></table>
                    </td>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: {{ $cellPad }}; font-size: {{ $itemTitleSize }}; font-weight: bold; vertical-align: top; line-height: 1.08;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($rowTotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @empty
                <tr>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: 4pt; vertical-align: bottom;"></td>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: 50pt 4pt 4pt 4pt; font-size: 8.9pt; line-height: 1.1; vertical-align: bottom;">
                        <div style="font-weight: bold; font-size: 10.5pt; color: #000; margin-bottom: 2pt;">Note :</div>
                        <div style="font-style: italic; color: #000; line-height: 1.1;">- Harga dapat berubah tanpa pemberitahuan</div>
                        <div style="font-style: italic; color: #000; line-height: 1.1;">- Mohon tanyakan stok terlebih dahulu sebelum mengirim PO</div>
                    </td>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: 4pt; vertical-align: bottom;"></td>
                    <td style="border-left: 1px solid #808080; border-right: 1px solid #808080; border-bottom: 1px solid #808080; padding: 4pt; vertical-align: bottom;"></td>
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
                <!-- TOTAL HARGA ROW (Include PPN / Perorangan) -->
                <tr style="font-weight: bold; font-size: {{ $totFontSize }};">
                    <td style="border: 1px solid #808080; padding: {{ $totPad }};">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 40%; text-align: right; padding: 0 2pt 0 0; border: none; font-weight: bold;">{{ (int) $totalQty }}</td>
                                <td style="width: 60%; text-align: left; padding: 0 0 0 2pt; border: none; font-weight: bold;">{{ $summaryUnitLabel }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: 1px solid #808080; border-right: none; padding: {{ $totPad }}; padding-right: 6pt; text-align: right; text-transform: uppercase;">TOTAL HARGA</td>
                    <td style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: none; border-right: 1px solid #808080; padding: {{ $totPad }};"></td>
                    <td style="border: 1px solid #808080; padding: {{ $totPad }};">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($grandSubtotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @else
                <!-- TOTAL ROW (Sesuai Sampling: Label TOTAL di ujung kanan kolom DESKRIPSI, border horizontal utuh tanpa putus) -->
                <tr style="font-weight: bold; font-size: {{ $totFontSize }};">
                    <td style="border: 1px solid #808080; padding: {{ $totPad }};">
                        <table style="width: 100%; border: none;">
                            <tr>
                                <td style="width: 40%; text-align: right; padding: 0 2pt 0 0; border: none; font-weight: bold;">{{ (int) $totalQty }}</td>
                                <td style="width: 60%; text-align: left; padding: 0 0 0 2pt; border: none; font-weight: bold;">{{ $summaryUnitLabel }}</td>
                            </tr>
                        </table>
                    </td>
                    <td style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: 1px solid #808080; border-right: none; padding: {{ $totPad }}; padding-right: 6pt; text-align: right; text-transform: uppercase;">TOTAL</td>
                    <td style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: none; border-right: 1px solid #808080; padding: {{ $totPad }};"></td>
                    <td style="border: 1px solid #808080; padding: {{ $totPad }};">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($grandSubtotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>

                <!-- PPN ROW -->
                <tr style="font-weight: bold; font-size: {{ $totFontSize }};">
                    <td colspan="2" style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: 1px solid #808080; border-right: none; padding: {{ $totPad }}; padding-right: 6pt; text-align: right;">PPn</td>
                    <td style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: none; border-right: 1px solid #808080; padding: {{ $totPad }};"></td>
                    <td style="border: 1px solid #808080; padding: {{ $totPad }};">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($ppn, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>

                <!-- TOTAL HARGA ROW -->
                <tr style="font-weight: bold; font-size: {{ $totFontSize }};">
                    <td colspan="2" style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: 1px solid #808080; border-right: none; padding: {{ $totPad }}; padding-right: 6pt; text-align: right; text-transform: uppercase;">TOTAL HARGA</td>
                    <td style="border-top: 1px solid #808080; border-bottom: 1px solid #808080; border-left: none; border-right: 1px solid #808080; padding: {{ $totPad }};"></td>
                    <td style="border: 1px solid #808080; padding: {{ $totPad }};">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($grandTotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- BAGIAN BAWAH (SYARAT, PENUTUP, TTD) DIJAGA AGAR TIDAK TERPOTONG BURUK -->
    <div style="page-break-inside: avoid;">
        <!-- SYARAT & KETENTUAN (Dinamis Sesuai Payment Terms) -->
        <div style="font-size: {{ $termFontSize }}; line-height: 1.12; margin-top: {{ $density === 'compact' ? '2pt' : '4pt' }}; margin-bottom: {{ $density === 'compact' ? '3pt' : '6pt' }};">
            @if($rfq->isPaymentTermCbdPersonal())
                <table class="terms-table" style="width: 100%; table-layout: fixed; border: none;">
                    <tr>
                        <td class="check" style="width: 4.6%; vertical-align: top; font-size: {{ $termFontSize }}; border: none; text-align: center;">&#10003;</td>
                        <td style="width: 95.4%; border: none; color: #ff0000; font-style: italic; line-height: 1.12;">
                            <div style="line-height: 1.12;">Sistem pembayaran Cash Before Delivery</div>
                            <table style="border: none; border-collapse: collapse; margin: 0; padding: 0; color: #ff0000; font-style: italic; font-size: {{ $termFontSize }}; line-height: 1.12;">
                                <tr><td style="width: 65pt; border: none; padding: 0;">Nama Bank</td><td style="border: none; padding: 0;">: BANK MANDIRI</td></tr>
                                <tr><td style="border: none; padding: 0;">Cabang</td><td style="border: none; padding: 0;">: KEMANG PRATAMA BEKASI</td></tr>
                                <tr><td style="border: none; padding: 0;">Nama Akun</td><td style="border: none; padding: 0;">: PEDIA TEKNOLOGI INDONESIA</td></tr>
                                <tr><td style="border: none; padding: 0;">No. Akun</td><td style="border: none; padding: 0;">: 1670024241514 (IDR)</td></tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td class="check" style="width: 4.6%; vertical-align: top; font-size: {{ $termFontSize }}; padding-top: 2pt; border: none; text-align: center;">&#10003;</td>
                        <td style="width: 95.4%; padding-top: 2pt; border: none; color: #000; line-height: 1.12; word-wrap: break-word; overflow-wrap: break-word;">
                            <div style="font-style: italic; line-height: 1.12;">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku.<br>Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                            <div class="jp" style="color: #000; font-style: normal; font-size: {{ $termJpSize }}; line-height: 1.15; margin-top: 1pt;">本見積書へのご署名をもって、本見積内容および諸条件にご同意いただいたものとみなします。<br>なお、ご署名後のキャンセルはお受けできませんので、ご了承ください。</div>
                        </td>
                    </tr>
                </table>
            @elseif($rfq->isPaymentTermCbdB2b())
                <table class="terms-table" style="width: 100%; table-layout: fixed; border: none;">
                    <tr>
                        <td style="width: 4.6%; vertical-align: top; font-weight: normal; font-size: {{ $termFontSize }}; border: none; text-align: center;" class="jp">※</td>
                        <td style="width: 95.4%; border: none; line-height: 1.12;">
                            <div style="font-style: italic; line-height: 1.12;">Sistem pembayaran Cash Before Delivery</div>
                            <div style="color: #000; font-size: {{ $termJpSize }}; line-height: 1.15;" class="jp">支払条件：前払い（納品前）</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="width: 4.6%; vertical-align: top; font-weight: normal; font-size: {{ $termFontSize }}; padding-top: 2pt; border: none; text-align: center;" class="jp">※</td>
                        <td style="width: 95.4%; padding-top: 2pt; border: none; line-height: 1.12; word-wrap: break-word; overflow-wrap: break-word;">
                            <div style="line-height: 1.12;">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku.<br>Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                            <div style="color: #000; font-size: {{ $termJpSize }}; line-height: 1.15;" class="jp">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。<br>ご署名後はキャンセルできませんのでご了承ください。</div>
                        </td>
                    </tr>
                </table>
            @else
                @php $termDays = $rfq->getPaymentTermDays(); @endphp
                <table class="terms-table" style="width: 100%; table-layout: fixed; border: none;">
                    <tr>
                        <td class="check" style="width: 4.6%; vertical-align: top; font-size: {{ $termFontSize }}; border: none; text-align: center;">&#10003;</td>
                        <td style="width: 95.4%; border: none; line-height: 1.12;">
                            <div style="font-style: italic; line-height: 1.12;">Sistem pembayaran {{ $termDays }} hari setelah invoice diterima</div>
                            <div style="color: #000; font-size: {{ $termJpSize }}; line-height: 1.15;" class="jp">お支払い条件は、請求書受領後{{ $termDays }}日以内となります。</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="check" style="width: 4.6%; vertical-align: top; font-size: {{ $termFontSize }}; padding-top: 2pt; border: none; text-align: center;">&#10003;</td>
                        <td style="width: 95.4%; padding-top: 2pt; border: none; line-height: 1.12; word-wrap: break-word; overflow-wrap: break-word;">
                            <div style="font-style: italic; line-height: 1.12;">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku.<br>Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                            <div style="color: #000; font-size: {{ $termJpSize }}; line-height: 1.15;" class="jp">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。<br>ご署名後はキャンセルできませんのでご了承ください。</div>
                        </td>
                    </tr>
                </table>
            @endif
        </div>

        <!-- PENUTUP -->
        <div style="font-size: {{ $termFontSize }}; color: #000; margin-top: {{ $density === 'compact' ? '2pt' : '5pt' }}; margin-bottom: {{ $density === 'compact' ? '3pt' : '8pt' }}; line-height: 1.12;">
            Demikian penawaran ini kami kirimkan. Jika ada hal yang ingin ditanyakan, dapat menghubungi saya di nomor telp : {{ $salesPhone }} atau e-mail {{ $salesEmail }} . Atas perhatian dan kepercayaan nya kami ucapkan Terima Kasih.
        </div>

        <!-- TANDA TANGAN (Persis Sampling — Baris Nama Sales & Nama Klien sejajar horizontal 100%) -->
        <table style="width: 100%; font-size: {{ $termFontSize }}; margin-top: 1pt; margin-bottom: 2pt; border: none;">
            <tr>
                <td style="width: 53%; vertical-align: top; border: none; padding: 0;">
                    <div style="line-height: 1.15;">Hormat Kami</div>
                    <div style="position: relative; width: 180pt; height: {{ $sigBoxHeight }}; margin: 2pt 0;">
                        @php
                            $stampPath = public_path('images/pedia_company_stamp.png');
                            $hasSig = $salesSigPath && file_exists($salesSigPath);
                        @endphp
                        @if(file_exists($stampPath))
                            <img src="{{ $stampPath }}" style="position: absolute; left: {{ $hasSig ? '16pt' : '4pt' }}; top: 1pt; width: {{ $stampWidth }}; height: auto;">
                        @endif
                        @if($hasSig)
                            <img src="{{ $salesSigPath }}" style="position: absolute; left: 0pt; top: 0pt; height: {{ $sigImgHeight }}; width: auto;">
                        @endif
                    </div>
                </td>
                <td style="width: 47%; vertical-align: top; border: none; padding: 0;">
                    <div style="line-height: 1.15;">Kami menyetujui dengan qty dan harga yang ditawarkan di atas.</div>
                    <div style="font-size: {{ $termJpSize }}; color: #000; line-height: 1.15;" class="jp">上記の数量と価格に同意いたします。</div>
                    <div style="font-size: {{ $termFontSize }}; color: #000; line-height: 1.15; margin-top: 1pt;">{{ $clientCompanyName }}</div>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: bottom; border: none; padding: 0; font-weight: bold; font-size: {{ $termFontSize }}; line-height: 1.15;">
                    {{ $salesName }}
                </td>
                <td style="vertical-align: bottom; border: none; padding: 0; font-size: {{ $termFontSize }}; line-height: 1.15;">
                    <table style="width: 100%; border: none;">
                        <tr><td style="width: 34pt; border: none; padding: 0;">Nama</td><td style="border: none; padding: 0;">: </td></tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top; border: none; padding: 0; font-style: italic; font-size: {{ $density === 'compact' ? '7.5pt' : '8.2pt' }}; color: #000; line-height: 1.15;">
                    {{ $salesRole }}
                </td>
                <td style="vertical-align: top; border: none; padding: 0; font-size: {{ $termFontSize }}; line-height: 1.15;">
                    <table style="width: 100%; border: none;">
                        <tr><td style="width: 34pt; border: none; padding: 0;">Jabatan</td><td style="border: none; padding: 0;">: </td></tr>
                    </table>
                </td>
            </tr>
        </table>
        <!-- Spacer seukuran tinggi footer agar konten di atas tidak pernah menabrak area footer di bawah -->
        <div style="height: {{ $footerSpacer }};"></div>
    </div>

    <!-- FOOTER (Persis Sampling — menggunakan banner footer resmi HD dari sampling Excel/PDF) -->
    <div style="position: absolute; bottom: 0pt; left: 0pt; right: 0pt; text-align: center; color: #000;">
        <div style="font-weight: bold; font-size: {{ $density === 'compact' ? '8.2pt' : '8.9pt' }}; line-height: 1.12; text-transform: uppercase;">THANK YOU FOR YOUR BUSINESS!</div>
        <div style="font-size: {{ $density === 'compact' ? '7.8pt' : '8.5pt' }}; line-height: 1.12; margin-bottom: 2pt;" class="jp">お買い上げくださってありがとうございます！</div>
        @if(file_exists(public_path('images/pedia_footer_hd.png')))
            <img src="{{ public_path('images/pedia_footer_hd.png') }}" style="width: {{ $footerImgW }}; height: auto; display: block; margin: 0 auto;">
        @else
            <div class="arial" style="font-size: 10.5pt; color: #222; margin-bottom: 4pt;">
                <div style="line-height: 1.08;">Rukan Rose Garden Blok RRGB No. 93, Jl. Grand Galaxy City Central Park 3,</div>
                <div style="line-height: 1.08;">Kel. Jaka Setia, Kec. Bekasi Selatan, Kota Bekasi, Jawa Barat 17147</div>
                <div style="line-height: 1.08;">Telp : (+62) 021-3971-2155, Fax : (+62) 021-3970-0175</div>
            </div>
            <div class="arial" style="width: 76%; margin: 0 auto; background-color: #005496; color: #fff; padding: 2.5pt 0; font-size: 9.8pt; line-height: 1.1;">
                website : https://pedia-technology.co.id
            </div>
        @endif
    </div>

</body>
</html>
