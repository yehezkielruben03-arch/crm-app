<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation {{ str_replace('RFQ', 'QUO', $rfq->rfq_number) }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td { border: none; vertical-align: top; }
        .company-info { text-align: right; font-size: 10px; line-height: 1.4; }
        .title-doc { text-align: center; font-size: 20px; font-weight: bold; margin: 15px 0; letter-spacing: 2px; text-decoration: underline; }
        .info-table { margin-bottom: 15px; }
        .info-table td { border: none; vertical-align: top; padding: 3px 0; }
        .info-table .label { width: 80px; font-weight: bold; }
        .info-table .colon { width: 10px; }
        
        .items-table { margin-top: 10px; width: 100%; border: 1px solid #000; }
        .items-table th { background-color: #f2f2f2; font-weight: bold; text-align: center; padding: 8px 4px; border: 1px solid #000; }
        .items-table td { border: 1px solid #000; padding: 6px 4px; vertical-align: top; line-height: 1.4; }
        .items-table .text-center { text-align: center; }
        .items-table .text-right { text-align: right; }
        .items-table .group-row td { background-color: #e8eaed; font-weight: bold; text-align: left; padding: 6px; }
        
        .summary-table td { border: none; padding: 5px; font-weight: bold; text-align: right; }
        .summary-table .summary-value { border: 1px solid #000; width: 120px; text-align: right; padding-right: 5px; }
        
        .notes { font-size: 10px; margin-top: 15px; line-height: 1.5; }
        .notes p { margin: 2px 0; }
        .bilingual { font-style: italic; color: #555; }
        
        .signature-table { margin-top: 30px; width: 100%; }
        .signature-table td { border: none; text-align: right; vertical-align: bottom; }
        
        .revision-banner { color: #d9534f; font-size: 14px; font-weight: bold; text-align: center; margin-top: -10px; margin-bottom: 15px; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td width="50%">
                <!-- Pedia Logo Placeholder -->
                <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="max-width: 180px; max-height: 60px;">
            </td>
            <td width="50%" class="company-info">
                <strong style="font-size: 14px;">PT. PEDIA TEKNOLOGI INDONESIA</strong><br>
                Gedung Pedia, Jl. Teknologi No. 1, Jakarta<br>
                Phone: (021) 1234567 | Email: info@pedia-group.com<br>
                Website: www.pedia-group.com
            </td>
        </tr>
    </table>

    <div class="title-doc">QUOTATION</div>

    @if(isset($revisionCount) && $revisionCount > 0)
        <div class="revision-banner">REVISI {{ $revisionCount }} : {{ date('d F Y') }}</div>
    @endif

    <table class="info-table">
        <tr>
            <td width="50%">
                <table>
                    <tr><td class="label">To</td><td class="colon">:</td><td>{{ $rfq->customer->name ?? '-' }}</td></tr>
                    <tr><td class="label">Attn</td><td class="colon">:</td><td>{{ $rfq->customerContact->name ?? 'Bpk/Ibu' }}</td></tr>
                    <tr><td class="label">Address</td><td class="colon">:</td><td>{{ $rfq->customer->address ?? '-' }}</td></tr>
                </table>
            </td>
            <td width="50%">
                <table>
                    <tr><td class="label">Date</td><td class="colon">:</td><td>{{ \Carbon\Carbon::parse($rfq->rfq_date)->format('d F Y') }}</td></tr>
                    <tr><td class="label">Quote No</td><td class="colon">:</td><td>{{ str_replace('RFQ', 'QUO', $rfq->rfq_number) }}</td></tr>
                    <tr><td class="label">Project</td><td class="colon">:</td><td><strong>{{ $rfq->notes ?? 'Pengadaan Barang & Jasa' }}</strong></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Description & Specification</th>
                <th width="8%">Qty</th>
                <th width="10%">Unit</th>
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

    <table style="width: 100%; margin-top: 10px;">
        <tr>
            <td width="60%"></td>
            <td width="40%">
                <table class="summary-table">
                    <tr>
                        <td>SUBTOTAL</td>
                        <td class="summary-value">{{ number_format($grandSubtotal, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>PPN 11%</td>
                        <td class="summary-value">{{ number_format($ppn, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>GRAND TOTAL</td>
                        <td class="summary-value">{{ number_format($grandTotal, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="notes">
        <strong>Terms and Conditions (条件):</strong><br>
        <p>1. Prices are valid for 7 days. <span class="bilingual">(見積もりの有効期限は7日間です)</span></p>
        <p>2. Payment terms: 50% Down Payment, 50% Before Delivery. <span class="bilingual">(支払い条件：前払い50％、納品前50％)</span></p>
        <p>3. Stock is subject to availability. <span class="bilingual">(在庫状況により変動する場合があります)</span></p>
        <p>4. Delivery schedule to be confirmed. <span class="bilingual">(納期は要相談)</span></p>
    </div>

    <table class="signature-table">
        <tr>
            <td>
                Best Regards,<br>
                <div style="height: 80px;"></div>
                <strong>{{ $rfq->sales->name ?? 'Sales Representative' }}</strong><br>
                {{ $rfq->sales->role ?? 'Sales Executive' }}<br>
                {{ $rfq->sales->email ?? 'sales@pedia-group.com' }}
            </td>
        </tr>
    </table>

</body>
</html>
