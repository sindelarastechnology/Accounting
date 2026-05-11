<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Neraca Saldo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px solid #2563eb; }
        .header h1 { font-size: 16px; color: #1e40af; }
        .header h2 { font-size: 14px; margin-top: 4px; }
        .header p { font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #1e40af; color: #fff; padding: 8px 6px; text-align: left; font-size: 9px; }
        thead th:last-child, thead th:nth-child(3), thead th:nth-child(4) { text-align: right; }
        tbody td { padding: 6px; border-bottom: 1px solid #e5e7eb; font-size: 9px; }
        tbody td:last-child, tbody td:nth-child(3), tbody td:nth-child(4) { text-align: right; }
        tbody tr.header-row { background: #f3f4f6; font-weight: bold; }
        tbody tr.header-row td { font-weight: bold; }
        tfoot td { padding: 8px 6px; font-weight: bold; border-top: 2px solid #1e40af; font-size: 10px; }
        tfoot td:last-child, tfoot td:nth-child(3), tfoot td:nth-child(4) { text-align: right; }
        .balance-badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 10px; font-weight: bold; margin-top: 10px; }
        .balance-ok { background: #d1fae5; color: #065f46; }
        .balance-fail { background: #fee2e2; color: #991b1b; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name', 'Onezie Accounting') }}</h1>
            <h2>NERACA SALDO</h2>
            @if($period)
                <p>Periode: {{ $period->name }}</p>
            @endif
            <p>Dicetak: {{ $generated_at }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Kode Akun</th>
                    <th>Nama Akun</th>
                    <th>Debit (Rp)</th>
                    <th>Kredit (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['rows'] as $groupId => $group)
                    @if($group['header'])
                        <tr class="header-row">
                            <td>{{ $group['header']->code }}</td>
                            <td colspan="3"><strong>{{ $group['header']->name }}</strong></td>
                        </tr>
                    @endif
                    @foreach($group['items'] as $item)
                        <tr>
                            <td>{{ $item['account']->code }}</td>
                            <td>{{ $item['account']->name }}</td>
                            <td>{{ number_format($item['debit'], 2, ',', '.') }}</td>
                            <td>{{ number_format($item['credit'], 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">TOTAL</td>
                    <td>{{ number_format($data['total_debit'], 2, ',', '.') }}</td>
                    <td>{{ number_format($data['total_credit'], 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div style="text-align: center; margin-top: 10px;">
            @if($data['is_balanced'])
                <span class="balance-badge balance-ok">✓ Balance</span>
            @else
                <span class="balance-badge balance-fail">✗ Tidak Balance — Selisih: Rp {{ number_format($data['difference'], 2, ',', '.') }}</span>
            @endif
        </div>

        <div class="footer">Dicetak oleh sistem akuntansi pada {{ $generated_at }}.</div>
    </div>
</body>
</html>
