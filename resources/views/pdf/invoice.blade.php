<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        .header { display: table; width: 100%; margin-bottom: 20px; }
        .header-left { display: table-cell; vertical-align: top; width: 50%; }
        .header-right { display: table-cell; text-align: right; vertical-align: top; width: 50%; }
        .company-name { font-size: 20px; font-weight: bold; color: #1a1a1a; }
        .company-address { font-size: 10px; color: #666; margin-top: 4px; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #1a1a1a; }
        .invoice-meta { margin-top: 6px; text-align: right; }
        .invoice-meta div { margin-bottom: 2px; }
        .invoice-meta .label { font-weight: bold; color: #555; }

        .address-block { margin-bottom: 20px; padding: 10px; background: #f9f9f9; border-left: 3px solid #4a90d9; }
        .address-block .label { font-size: 9px; color: #888; text-transform: uppercase; margin-bottom: 4px; }
        .address-block .name { font-size: 13px; font-weight: bold; }
        .address-block .detail { font-size: 10px; color: #555; margin-top: 2px; }

        table.items { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.items th { background: #4a90d9; color: #fff; padding: 8px 6px; text-align: left; font-size: 10px; }
        table.items th:nth-child(1),
        table.items th:nth-child(3),
        table.items th:nth-child(4) { text-align: center; width: 40px; }
        table.items th:nth-child(5),
        table.items th:nth-child(6),
        table.items th:nth-child(7) { text-align: right; width: 100px; }
        table.items td { padding: 6px; border-bottom: 1px solid #eee; }
        table.items td:nth-child(1),
        table.items td:nth-child(3),
        table.items td:nth-child(4) { text-align: center; }
        table.items td:nth-child(5),
        table.items td:nth-child(6),
        table.items td:nth-child(7) { text-align: right; }
        table.items tr:nth-child(even) { background: #fafafa; }

        .totals { margin-left: auto; width: 300px; }
        .totals .row { display: table; width: 100%; padding: 4px 0; }
        .totals .row .label { display: table-cell; text-align: left; font-size: 11px; }
        .totals .row .value { display: table-cell; text-align: right; font-size: 11px; }
        .totals .row.grand { border-top: 2px solid #333; margin-top: 4px; padding-top: 8px; }
        .totals .row.grand .label { font-size: 14px; font-weight: bold; }
        .totals .row.grand .value { font-size: 16px; font-weight: bold; color: #1a1a1a; }

        .payment-info { margin-top: 20px; padding: 10px; background: #f9f9f9; border-left: 3px solid #e67e22; }
        .payment-info .due { font-weight: bold; font-size: 12px; }
        .payment-info .notes { margin-top: 6px; font-size: 10px; color: #555; }

        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                @php
                    $logo = \App\Models\Setting::get('company_logo');
                    $companyName = \App\Models\Setting::get('company_name', 'Perusahaan');
                    $companyAddress = \App\Models\Setting::get('company_address', '');
                    $companyPhone = \App\Models\Setting::get('company_phone', '');
                    $companyTax = \App\Models\Setting::get('company_tax_number', '');
                @endphp
                @if($logo && file_exists(storage_path('app/public/' . $logo)))
                    <img src="{{ storage_path('app/public/' . $logo) }}" alt="Logo" style="max-height: 50px; margin-bottom: 5px;">
                @endif
                <div class="company-name">{{ $companyName }}</div>
                @if($companyAddress)
                    <div class="company-address">{{ $companyAddress }}</div>
                @endif
                @if($companyPhone)
                    <div class="company-address">Telp: {{ $companyPhone }}</div>
                @endif
                @if($companyTax)
                    <div class="company-address">NPWP: {{ $companyTax }}</div>
                @endif
            </div>
            <div class="header-right">
                <div class="invoice-title">FAKTUR PENJUALAN</div>
                <div class="invoice-meta">
                    <div><span class="label">No. Invoice:</span> {{ $invoice->number }}</div>
                    <div><span class="label">Tanggal:</span> {{ $invoice->date->format('d/m/Y') }}</div>
                    <div><span class="label">Jatuh Tempo:</span> {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</div>
                    @if($invoice->ref_number)
                        <div><span class="label">No. Referensi:</span> {{ $invoice->ref_number }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="address-block">
            <div class="label">Kepada Yth.</div>
            <div class="name">{{ $invoice->contact->name }}</div>
            @if($invoice->contact->address)
                <div class="detail">{{ $invoice->contact->address }}</div>
            @endif
            @if($invoice->contact->city)
                <div class="detail">{{ $invoice->contact->city }}</div>
            @endif
            @if($invoice->contact->tax_number)
                <div class="detail">NPWP: {{ $invoice->contact->tax_number }}</div>
            @endif
        </div>

        <table class="items">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Deskripsi</th>
                    <th>Qty</th>
                    <th>Satuan</th>
                    <th>Harga Satuan</th>
                    <th>Diskon</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php $totalDiscount = 0; @endphp
                @foreach($invoice->items as $i => $item)
                    @php
                        $lineDiscount = (float) $item->discount_amount;
                        $totalDiscount += $lineDiscount;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $item->description }}</td>
                        <td>{{ number_format((float) $item->qty, 0, ',', '.') }}</td>
                        <td>{{ $item->unit ?? 'pcs' }}</td>
                        <td>{{ rupiah((float) $item->unit_price, false) }}</td>
                        <td>{{ $lineDiscount > 0 ? rupiah($lineDiscount, false) : '-' }}</td>
                        <td>{{ rupiah((float) $item->subtotal, false) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <div class="row">
                <div class="label">Subtotal</div>
                <div class="value">{{ rupiah((float) $invoice->subtotal, false) }}</div>
            </div>
            @if($totalDiscount > 0)
                <div class="row">
                    <div class="label">Total Diskon</div>
                    <div class="value">({{ rupiah($totalDiscount, false) }})</div>
                </div>
            @endif
            @foreach($invoice->taxes as $tax)
                <div class="row">
                    <div class="label">{{ $tax->tax_name }} ({{ $tax->rate }}%)</div>
                    <div class="value">{{ rupiah((float) $tax->tax_amount, false) }}</div>
                </div>
            @endforeach
            <div class="row grand">
                <div class="label">TOTAL TAGIHAN</div>
                <div class="value">{{ rupiah((float) $invoice->total, false) }}</div>
            </div>
        </div>

        <div class="payment-info">
            <div class="due">Jatuh Tempo: {{ $invoice->due_date ? $invoice->due_date->format('d/m/Y') : '-' }}</div>
            @if($invoice->notes)
                <div class="notes"><strong>Catatan:</strong> {{ $invoice->notes }}</div>
            @endif
        </div>

        <div class="footer">
            Dokumen ini dicetak secara otomatis oleh sistem akuntansi.
        </div>
    </div>
</body>
</html>
