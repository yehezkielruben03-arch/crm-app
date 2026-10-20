<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <title>Quotation {{ $rfq->quotation_number }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 11mm 14mm 10mm 14mm;
        }
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
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
            line-height: 1.15;
        }
        .jp {
            font-family: 'JapaneseFont', sans-serif;
            font-weight: normal;
            font-style: normal;
        }
        .check {
            font-family: 'DejaVu Sans', sans-serif;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .main-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #888;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .italic { font-style: italic; }
    </style>
</head>
<body>

    @php
        $clientCompanyName = $rfq->resolved_company_name;
        $custAddress       = $rfq->resolved_customer_address;
        $custPhone         = $rfq->resolved_customer_phone;
        $custPic           = $rfq->resolved_pic_name;
        $isHouseAccount    = $rfq->sales_name === 'Pedia' || ($rfq->sales && $rfq->sales->isAdminOrAbove());
        $salesPhone        = $rfq->sales?->phone ?: '021-3971-2155';
        $salesEmail        = $rfq->sales?->email ?: 'info@pedia-technology.co.id';
        if ($isHouseAccount) {
            $salesName     = 'PT Pedia Technology Indonesia';
            $salesRole     = 'Management';
        } else {
            $salesName     = $rfq->sales?->name ?: ($rfq->sales_name ?: 'Ade Zulvida');
            $salesRole     = $rfq->sales?->effective_job_title ?: (in_array($rfq->sales?->role, ['Sales', 'Sales Marketing']) ? 'Account Manager' : ($rfq->sales?->role ?: 'Account Manager'));
        }
        $salesSigPath      = null;
        if (!$isHouseAccount && $rfq->sales?->signature_path) {
            if (file_exists(storage_path('app/public/' . $rfq->sales->signature_path))) {
                $salesSigPath = storage_path('app/public/' . $rfq->sales->signature_path);
            } elseif (file_exists(public_path('storage/' . $rfq->sales->signature_path))) {
                $salesSigPath = public_path('storage/' . $rfq->sales->signature_path);
            } elseif (file_exists(public_path($rfq->sales->signature_path))) {
                $salesSigPath = public_path($rfq->sales->signature_path);
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
                $totalSpecLines += max(1, substr_count($specText, "\n") + (int) ceil(strlen($specText) / 70));
            }
        }
        $noteTopMargin = match(true) {
            $itemCount <= 1 && $totalSpecLines <= 4 => '105px',
            $itemCount <= 1 && $totalSpecLines <= 8 => '65px',
            $itemCount <= 1 => '35px',
            $itemCount <= 2 && $totalSpecLines <= 6 => '70px',
            $itemCount <= 2 => '30px',
            $itemCount <= 3 => '45px',
            $itemCount <= 4 => '20px',
            default => '10px'
        };
    @endphp

    <!-- HEADER TABLE -->
    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 2px;">
        <tr>
            <td style="width: 55%; vertical-align: top; border: none; padding: 0;">
                @if(file_exists(public_path('images/pedia_logo_hd.png')))
                    <img src="{{ public_path('images/pedia_logo_hd.png') }}" style="width: 175px; height: auto; display: block;">
                @elseif(file_exists(public_path('images/logo.png')))
                    <img src="{{ public_path('images/logo.png') }}" style="width: 175px; height: auto; display: block;">
                @else
                    <strong style="font-size: 16px; color: #000;">PEDIA TECHNOLOGY</strong>
                @endif
                <div style="font-weight: bold; font-size: 11px; text-transform: uppercase; margin-top: 4px; color: #000;">PT. PEDIA TEKNOLOGI INDONESIA</div>
                <div style="font-style: italic; font-size: 8.5px; color: #595959;">With Our Experience Everything Is Possible</div>
            </td>
            <td style="width: 45%; vertical-align: top; text-align: right; border: none; padding: 0;">
                <div style="font-size: 42px; color: #7f7f7f; line-height: 1; margin-top: 4px; margin-bottom: 18px; font-weight: bold; text-align: right;">Quotation</div>
                <table align="right" style="width: 215px; border-collapse: collapse; font-size: 10px;">
                    <tr>
                        <td style="font-weight: bold; padding: 1.5px 6px 1.5px 0; text-align: right; text-transform: uppercase; border: none; width: 95px;">TANGGAL</td>
                        <td style="text-align: right; padding: 1.5px 0; border: none; width: 120px;">{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold; padding: 1.5px 6px 1.5px 0; text-align: right; border: none;">No. Penawaran</td>
                        <td style="text-align: right; padding: 1.5px 0; border: none;">{{ $rfq->quotation_number }}</td>
                    </tr>
                    @if(isset($revisionCount) && $revisionCount > 0)
                    <tr>
                        <td colspan="2" style="color: #c00000; font-weight: bold; text-align: right; padding: 2px 0 0 0; border: none;">
                            REVISI {{ $revRoman }} : {{ $formattedRevDate }}
                        </td>
                    </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- INFO KLIEN & BERLAKU S/D -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 6px; border: none;">
        <tr>
            <td style="width: 58%; vertical-align: bottom; font-size: 10px; line-height: 1.05; border: none; padding: 0;">
                <div style="color: #000; line-height: 1.05;">Kepada YTH</div>
                <div style="font-weight: bold; font-size: 11.5px; color: #000; line-height: 1.1; margin-top: 1px; margin-bottom: 1px;">{{ $clientCompanyName }}</div>
                @if(!empty($custAddress))
                    @foreach(preg_split('/\r\n|\r|\n/', trim($custAddress)) as $addrLine)
                        @if(trim($addrLine) !== '')
                        <div style="color: #000; line-height: 1.05;">{{ trim($addrLine) }}</div>
                        @endif
                    @endforeach
                @endif
                @if(!empty($custPhone))
                <div style="color: #000; line-height: 1.05;">Telp : {{ $custPhone }}</div>
                @endif
                @if(!empty($custPic))
                <div style="color: #000; line-height: 1.05;">Up. {{ $custPic }}</div>
                @endif
            </td>
            <td style="width: 42%; vertical-align: bottom; text-align: right; border: none; padding: 0;">
                <table align="right" style="width: 215px; border-collapse: collapse; background-color: #f2f2f2;">
                    <tr>
                        <td style="padding: 3px 8px; font-size: 10px; font-style: italic; color: #000; text-align: left; border: none;">Berlaku s/d tgl :</td>
                        <td style="padding: 3px 8px; font-size: 10px; color: #000; text-align: right; border: none;">{{ $rfq->formatted_valid_until }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- KALIMAT PEMBUKA -->
    <div style="font-size: 10px; margin-top: 6px; margin-bottom: 3px; color: #000;">
        Berikut penawaran dari kami<span class="jp" style="font-size: 9.5px;">・下記の通り御見積申し上げます。</span>
    </div>

    <!-- TABEL PENAWARAN (PERSIS SAMPLING) -->
    <table class="main-table">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th style="border: 1px solid #888; padding: 4px 4px; font-size: 9.5px; width: 11%; text-align: center; text-transform: uppercase;">JUMLAH</th>
                <th style="border: 1px solid #888; padding: 4px 4px; font-size: 9.5px; width: 53%; text-align: center; text-transform: uppercase;">DESKRIPSI</th>
                <th style="border: 1px solid #888; padding: 4px 4px; font-size: 9.5px; width: 17%; text-align: center; text-transform: uppercase;">HARGA</th>
                <th style="border: 1px solid #888; padding: 4px 4px; font-size: 9.5px; width: 19%; text-align: center; text-transform: uppercase;">JUMLAH HARGA</th>
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
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px 5px; text-align: right; font-weight: bold; font-size: 11px; vertical-align: top;">
                        {{ (int) $item->qty }} {{ ucwords(strtolower(trim($item->unit ?: 'Unit'))) }}
                    </td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px 6px; font-size: 10px; line-height: 1.08; vertical-align: top;">
                        <div style="font-weight: bold; font-size: 11px; color: #000; line-height: 1.1;">{{ ucfirst($item->product_name) }}</div>
                        @php $itemSpec = trim((string) ($item->description ?: $item->detail_item)); @endphp
                        @if($itemSpec !== '')
                        <div style="color: #111; margin-top: 2px; font-size: 9.5px; font-style: italic;">
                            @foreach(preg_split('/\r\n|\r|\n/', ucfirst($itemSpec)) as $specLine)
                                @if(trim($specLine) !== '')
                                <div style="line-height: 1.08;">{{ trim($specLine) }}</div>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        @if($loop->last)
                        <div style="margin-top: {{ $noteTopMargin }}; font-size: 9.5px; line-height: 1.08;">
                            <div style="font-weight: bold; font-size: 10.5px; color: #000; margin-bottom: 1px; line-height: 1.1;">Note :</div>
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
                                <div style="font-style: italic; color: #c00000; line-height: 1.08; font-weight: bold;">{{ $formattedNote }}</div>
                            @endforeach
                            <div style="font-style: italic; color: #000; line-height: 1.08;">- Harga dapat berubah tanpa pemberitahuan</div>
                            <div style="font-style: italic; color: #000; line-height: 1.08;">- Mohon tanyakan stok terlebih dahulu sebelum mengirim PO</div>
                        </div>
                        @endif
                    </td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px 6px; font-size: 11px; font-weight: bold; vertical-align: top;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none;">Rp</td><td style="text-align: right; padding: 0; border: none;">{{ number_format($unitPrice, 0, ',', '.') }}</td></tr></table>
                    </td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px 6px; font-size: 11px; font-weight: bold; vertical-align: top;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none;">Rp</td><td style="text-align: right; padding: 0; border: none;">{{ number_format($rowTotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @empty
                <tr>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px; vertical-align: bottom;"></td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 60px 6px 4px 6px; font-size: 9.5px; line-height: 1.28; vertical-align: bottom;">
                        <div style="font-weight: bold; font-size: 10.5px; color: #000; margin-bottom: 2px;">Note :</div>
                        <div style="font-style: italic; color: #000; line-height: 1.28;">- Harga dapat berubah tanpa pemberitahuan</div>
                        <div style="font-style: italic; color: #000; line-height: 1.28;">- Mohon tanyakan stok terlebih dahulu sebelum mengirim PO</div>
                    </td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px; vertical-align: bottom;"></td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; border-bottom: 1px solid #888; padding: 4px; vertical-align: bottom;"></td>
                </tr>
            @endforelse

            @php
                $isIncludeTax = $rfq->isIncludeTax();
                $ppn = round($grandSubtotal * 0.11);
                $grandTotal = $isIncludeTax ? $grandSubtotal : ($grandSubtotal + $ppn);
                $firstUnit = $quotationItems->first()?->unit ?: 'Unit';
            @endphp

            @if($isIncludeTax)
                <!-- TOTAL HARGA ROW (Include PPN / Perorangan) -->
                <tr style="border-top: 1px solid #888; border-bottom: 1px solid #888; font-weight: bold; font-size: 10.5px;">
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 5px; text-align: right;">{{ (int) $totalQty }} {{ ucwords(strtolower(trim($firstUnit))) }}</td>
                    <td colspan="2" style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 10px; text-align: right; text-transform: uppercase;">TOTAL HARGA</td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 6px;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($grandSubtotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @else
                <!-- TOTAL ROW -->
                <tr style="border-top: 1px solid #888; border-bottom: 1px solid #888; font-weight: bold; font-size: 10.5px;">
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 5px; text-align: right;">{{ (int) $totalQty }} {{ ucwords(strtolower(trim($firstUnit))) }}</td>
                    <td colspan="2" style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 10px; text-align: right; text-transform: uppercase;">TOTAL</td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 6px;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($grandSubtotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>

                <!-- PPN ROW -->
                <tr style="border-bottom: 1px solid #888; font-weight: bold; font-size: 10.5px;">
                    <td colspan="3" style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 10px; text-align: right;">PPn</td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 6px;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($ppn, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>

                <!-- TOTAL HARGA ROW -->
                <tr style="border-bottom: 1px solid #888; font-weight: bold; font-size: 10.5px;">
                    <td colspan="3" style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 10px; text-align: right; text-transform: uppercase;">TOTAL HARGA</td>
                    <td style="border-left: 1px solid #888; border-right: 1px solid #888; padding: 3.5px 6px;">
                        <table style="width: 100%; border: none;"><tr><td style="text-align: left; padding: 0; border: none; font-weight: bold;">Rp</td><td style="text-align: right; padding: 0; border: none; font-weight: bold;">{{ number_format($grandTotal, 0, ',', '.') }}</td></tr></table>
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- SYARAT & KETENTUAN (Dinamis Sesuai Payment Terms) -->
    <div style="font-size: 9px; line-height: 1.08; margin-top: 5px; margin-bottom: 6px;">
        @if($rfq->isPaymentTermCbdPersonal())
            <table style="width: 100%; border: none;">
                <tr>
                    <td class="check" style="width: 18px; vertical-align: top; font-size: 9px; border: none; padding-left: 4px;">&#10003;</td>
                    <td style="border: none; color: #c00000; font-style: italic; line-height: 1.08;">
                        <div style="line-height: 1.08;">Sistem pembayaran Cash Before Delivery</div>
                        <table style="border: none; border-collapse: collapse; margin: 0; padding: 0; color: #c00000; font-style: italic; font-size: 9px; line-height: 1.08;">
                            <tr><td style="width: 68px; border: none; padding: 0;">Nama Bank</td><td style="border: none; padding: 0;">: BANK MANDIRI</td></tr>
                            <tr><td style="border: none; padding: 0;">Cabang</td><td style="border: none; padding: 0;">: KEMANG PRATAMA BEKASI</td></tr>
                            <tr><td style="border: none; padding: 0;">Nama Akun</td><td style="border: none; padding: 0;">: PEDIA TEKNOLOGI INDONESIA</td></tr>
                            <tr><td style="border: none; padding: 0;">No. Akun</td><td style="border: none; padding: 0;">: 1670024241514 (IDR)</td></tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="check" style="width: 18px; vertical-align: top; font-size: 9px; padding-top: 3px; border: none; padding-left: 4px;">&#10003;</td>
                    <td style="padding-top: 3px; border: none; color: #000; line-height: 1.08;">
                        <div style="font-style: italic; line-height: 1.08;">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku. Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                        <div class="jp" style="color: #000; font-style: normal; font-size: 8.5px; line-height: 1.15; margin-top: 1px;">本見積書へのご署名をもって、本見積内容および諸条件にご同意いただいたものとみなします。なお、ご署名後のキャンセルはお受けできませんので、ご了承ください。</div>
                    </td>
                </tr>
            </table>
        @elseif($rfq->isPaymentTermCbdB2b())
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 18px; vertical-align: top; font-weight: normal; font-size: 9px; border: none; padding-left: 4px;" class="jp">※</td>
                    <td style="border: none; line-height: 1.08;">
                        <div style="font-style: italic; line-height: 1.08;">Sistem pembayaran Cash Before Delivery</div>
                        <div style="color: #000; font-size: 8.5px; line-height: 1.15;" class="jp">支払条件：前払い（納品前）</div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 18px; vertical-align: top; font-weight: normal; font-size: 9px; padding-top: 3px; border: none; padding-left: 4px;" class="jp">※</td>
                    <td style="padding-top: 3px; border: none; line-height: 1.08;">
                        <div style="line-height: 1.08;">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku. Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                        <div style="color: #000; font-size: 8.5px; line-height: 1.15;" class="jp">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。ご署名後はキャンセルできませんのでご了承ください。</div>
                    </td>
                </tr>
            </table>
        @else
            @php $termDays = $rfq->getPaymentTermDays(); @endphp
            <table style="width: 100%; border: none;">
                <tr>
                    <td class="check" style="width: 18px; vertical-align: top; font-size: 9px; border: none; padding-left: 4px;">&#10003;</td>
                    <td style="border: none; line-height: 1.08;">
                        <div style="font-style: italic; line-height: 1.08;">Sistem pembayaran {{ $termDays }} hari setelah invoice diterima</div>
                        <div style="color: #000; font-size: 8.5px; line-height: 1.15;" class="jp">お支払い条件は、請求書受領後{{ $termDays }}日以内となります。</div>
                    </td>
                </tr>
                <tr>
                    <td class="check" style="width: 18px; vertical-align: top; font-size: 9px; padding-top: 3px; border: none; padding-left: 4px;">&#10003;</td>
                    <td style="padding-top: 3px; border: none; line-height: 1.08;">
                        <div style="font-style: italic; line-height: 1.08;">Dengan menandatangani penawaran ini, pihak Pemesan menyetujui harga, qty, dan seluruh ketentuan yang berlaku. Setelah ditandatangani, penawaran tidak dapat dibatalkan.</div>
                        <div style="color: #000; font-size: 8.5px; line-height: 1.15;" class="jp">本見積書にご署名いただくことで、発注内容および条件に同意されたものといたします。ご署名後はキャンセルできませんのでご了承ください。</div>
                    </td>
                </tr>
            </table>
        @endif
    </div>

    <!-- PENUTUP -->
    <div style="font-size: 9px; color: #000; margin-top: 6px; margin-bottom: 10px; line-height: 1.08;">
        Demikian penawaran ini kami kirimkan. Jika ada hal yang ingin ditanyakan, dapat menghubungi saya di nomor telp : {{ $salesPhone }} atau e-mail {{ $salesEmail }} . Atas perhatian dan kepercayaan nya kami ucapkan Terima Kasih.
    </div>

    <!-- TANDA TANGAN (Persis Sampling) -->
    <table style="width: 100%; font-size: 9.5px; margin-top: 4px; margin-bottom: 6px; border: none;">
        <tr>
            <td style="width: 52%; vertical-align: top; border: none; padding: 0;">
                <div>Hormat Kami</div>
                <div style="position: relative; width: 220px; height: 66px; margin: 2px 0;">
                    @php $stampPath = public_path('images/pedia_company_stamp.png'); @endphp
                    @if(file_exists($stampPath))
                        <img src="{{ $stampPath }}" style="position: absolute; left: {{ $isHouseAccount ? '6px' : '22px' }}; top: 4px; width: 118px; height: auto;">
                    @endif
                    @if(!$isHouseAccount && $salesSigPath && file_exists($salesSigPath))
                        <img src="{{ $salesSigPath }}" style="position: absolute; left: 0px; top: 0px; height: 64px; width: auto;">
                    @endif
                </div>
                <div style="font-weight: bold; font-size: 10px; margin-top: 1px;">{{ $salesName }}</div>
                <div style="font-style: italic; font-size: 9px; color: #111;">{{ $salesRole }}</div>
            </td>
            <td style="width: 48%; vertical-align: top; padding-left: 10px; border: none;">
                <div>Kami menyetujui dengan qty dan harga yang ditawarkan di atas.</div>
                <div style="font-size: 8.5px; color: #000; margin-bottom: 2px;" class="jp">上記の数量と価格に同意いたします。</div>
                <div style="font-size: 9.5px; color: #000; margin-bottom: 36px;">{{ $clientCompanyName }}</div>
                
                <table style="width: 100%; border: none;">
                    <tr><td style="width: 48px; font-size: 9.5px; border: none; padding: 1px 0;">Nama</td><td style="font-size: 9.5px; border: none; padding: 1px 0;">: </td></tr>
                    <tr><td style="font-size: 9.5px; border: none; padding: 1px 0;">Jabatan</td><td style="font-size: 9.5px; border: none; padding: 1px 0;">: </td></tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- FOOTER (Persis Sampling) -->
    <div style="position: fixed; bottom: 0px; left: 0px; right: 0px; text-align: center; color: #000;">
        <div style="font-weight: bold; font-size: 9.5px; line-height: 1.1; text-transform: uppercase;">THANK YOU FOR YOUR BUSINESS!</div>
        <div style="font-size: 8.5px; line-height: 1.15; margin-bottom: 5px;" class="jp">お買い上げくださってありがとうございます！</div>
        <div style="font-size: 11px; color: #222; margin-bottom: 5px;">
            <div style="line-height: 1.02;">Rukan Rose Garden Blok RRGB No. 93, Jl. Grand Galaxy City Central Park 3,</div>
            <div style="line-height: 1.02;">Kel. Jaka Setia, Kec. Bekasi Selatan, Kota Bekasi, Jawa Barat 17147</div>
            <div style="line-height: 1.02;">Telp : (+62) 021-3971-2155, Fax : (+62) 021-3970-0175</div>
        </div>
        <div style="width: 76%; margin: 0 auto; background-color: #005496; color: #fff; padding: 3px 0; font-size: 10.5px; line-height: 1.1;">
            website : https://pedia-technology.co.id
        </div>
    </div>

</body>
</html>
