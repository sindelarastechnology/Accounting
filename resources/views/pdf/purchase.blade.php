<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order - {{ $purchase->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 3px solid #2563eb; }
        .company-info h1 { font-size: 20px; color: #2563eb; margin-bottom: 4px; }
        .company-info p { color: #666; font-size: 10px; }

        .po-title { text-align: right; }
        .po-title h2 { font-size: 22px; color: #1e40af; font-weight: bold; margin-bottom: 6px; letter-spacing: 2px; }
        .po-title .po-number { font-size: 13px; font-weight: bold; color: #333; }
        .po-title .po-date { font-size: 10px; color: #666; }

        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: bold; margin-top: 5px; }
        .status-draft { background: #f3f4f6; color: #6b7280; }
        .status-posted { background: #dbeafe; color: #1e40af; }
        .status-partially_paid { background: #fef3c7; color: #92400e; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .supplier-block { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .supplier-block h3 { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
        .supplier-block p { margin-bottom: 3px; }
        .supplier-block .name { font-size: 14px; font-weight: bold; color: #1e40af; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; }
        .info-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 12px; }
        .info-card h3 { font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .info-card .value { font-size: 13px; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table thead th { background: #1e40af; color: #fff; padding: 10px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        table thead th:first-child { border-radius: 6px 0 0 0; }
        table thead th:last-child { border-radius: 0 6px 0 0; text-align: right; }
        table tbody td { padding: 10px 8px; border-bottom: 1px solid #e5e7eb; font-size: 10px; }
        table tbody tr:nth-child(even) { background: #f9fafb; }
        table tbody td:last-child, table tbody td.text-right, table tfoot td:last-child, table tfoot td.text-right { text-align: right; }

        .summary-table { width: 300px; margin-left: auto; margin-bottom: 20px; }
        .summary-table td { padding: 6px 12px; border: none; font-size: 11px; }
        .summary-table tr.total td { font-size: 14px; font-weight: bold; border-top: 2px solid #1e40af; padding-top: 10px; }

        .notes { background: #fefce8; border: 1px solid #fef08a; border-radius: 8px; padding: 15px; margin-bottom: 20px; }
        .notes h3 { font-size: 11px; color: #854d0e; margin-bottom: 5px; }
        .notes p { color: #713f12; font-size: 10px; }

        .signature { display: flex; justify-content: space-between; margin-top: 40px; margin-bottom: 20px; }
        .signature-box { text-align: center; width: 45%; }
        .signature-box .line { border-top: 1px solid #333; margin-top: 60px; padding-top: 5px; }
        .signature-box p { font-size: 10px; color: #666; }

        .footer { text-align: center; padding-top: 15px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 9px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <h1>{{ config('app.name', 'Onezie Accounting') }}</h1>
                <p>Dokumen Purchase Order</p>
            </div>
            <div class="po-title">
                <h2>PURCHASE ORDER</h2>
                <div class="po-number">{{ $purchase->number }}</div>
                <div class="po-date">Tanggal: {{ $purchase->date ? $purchase->date->format('d/m/Y') : '-' }}</div>
                @if($purchase->due_date)
                    <div class="po-date">Jatuh Tempo: {{ $purchase->due_date->format('d/m/Y') }}</div>
                @endif
                <span class="status-badge status-{{ $purchase->status }}">
                    @switch($purchase->status)
                        @case('draft') Draft @break
                        @case('posted') Posted @break
                        @case('partially_paid') Sebagian Dibayar @break
                        @case('paid') Lunas @break
                        @case('cancelled') Dibatalkan @break
                    @endswitch
                </span>
            </div>
        </div>

        @if($purchase->contact)
        <div class="supplier-block">
            <h3>Kepada (Supplier):</h3>
            <p class="name">{{ $purchase->contact->name }}</p>
            @if($purchase->contact->address)
                <p>{{ $purchase->contact->address }}</p>
            @endif
            @if($purchase->contact->city)
                <p>{{ $purchase->contact->city }}</p>
            @endif
            @if($purchase->contact->npwp)
                <p>NPWP: {{ $purchase->contact->npwp }}</p>
            @endif
        </div>
        @endif

        <div class="info-grid">
            <div class="info-card">
                <h3>No. Faktur Supplier</h3>
                <div class="value">{{ $purchase->supplier_invoice_number ?? '-' }}</div>
            </div>
            <div class="info-card">
                <h3>Sisa Hutang</h3>
                <div class="value">Rp {{ number_format(max(0, (float) $purchase->total - (float) $purchase->paid_amount), 0, ',', '.') }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Deskripsi</th>
                    <th style="width: 60px; text-align: center;">Qty</th>
                    <th style="width: 50px;">Satuan</th>
                    <th style="width: 120px;">Harga Beli</th>
                    <th style="width: 80px;">Diskon</th>
                    <th style="width: 120px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($purchase->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td style="text-align: center;">{{ number_format($item->qty, 2, ',', '.') }}</td>
                    <td>{{ $item->unit ?? 'pcs' }}</td>
                    <td>Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td>
                        @if($item->discount_percent > 0)
                            Rp {{ number_format($item->qty * $item->unit_price * ($item->discount_percent / 100), 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </td>
                    <td>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <table class="summary-table">
            <tr>
                <td>Subtotal</td>
                <td class="text-right">Rp {{ number_format($purchase->subtotal, 0, ',', '.') }}</td>
            </tr>
            @if($purchase->discount_amount > 0)
            <tr>
                <td>Diskon</td>
                <td class="text-right">- Rp {{ number_format($purchase->discount_amount, 0, ',', '.') }}</td>
            </tr>
            @endif
            @if($purchase->tax_amount > 0)
            @foreach($purchase->taxes as $tax)
            <tr>
                <td>{{ $tax->tax_name }} ({{ $tax->rate }}%)</td>
                <td class="text-right">Rp {{ number_format($tax->tax_amount, 0, ',', '.') }}</td>
            </tr>
            @endforeach
            @endif
            <tr class="total">
                <td>TOTAL</td>
                <td class="text-right">Rp {{ number_format($purchase->total, 0, ',', '.') }}</td>
            </tr>
        </table>

        @if($purchase->notes)
        <div class="notes">
            <h3>Catatan</h3>
            <p>{{ $purchase->notes }}</p>
        </div>
        @endif

        <div class="signature">
            <div class="signature-box">
                <p>Disiapkan Oleh</p>
                <div class="line">
                    <p>(.............................)</p>
                </div>
            </div>
            <div class="signature-box">
                <p>Disetujui Oleh</p>
                <div class="line">
                    <p>(.............................)</p>
                </div>
            </div>
        </div>

        <div class="footer">
            Dokumen ini dicetak secara otomatis oleh sistem akuntansi.
        </div>
    </div>
</body>
</html>
