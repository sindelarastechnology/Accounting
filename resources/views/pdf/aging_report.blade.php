<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aging Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px solid #2563eb; }
        .header h1 { font-size: 16px; color: #1e40af; }
        .header h2 { font-size: 14px; margin-top: 4px; }
        .header p { font-size: 9px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        thead th { background: #1e40af; color: #fff; padding: 8px 4px; text-align: right; font-size: 9px; }
        thead th:first-child { text-align: left; }
        tbody td { padding: 6px 4px; border-bottom: 1px solid #e5e7eb; font-size: 9px; text-align: right; }
        tbody td:first-child { text-align: left; }
        tbody tr.warn-over-90 { background: #fef2f2; }
        tbody tr.warn-61-90 { background: #fefce8; }
        tfoot td { padding: 8px 4px; font-weight: bold; border-top: 2px solid #1e40af; font-size: 10px; text-align: right; }
        tfoot td:first-child { text-align: left; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name', 'Onezie Accounting') }}</h1>
            <h2>AGING {{ $data['type'] === 'receivable' ? 'PIUTANG' : 'HUTANG' }}</h2>
            <p>Per Tanggal: {{ \Carbon\Carbon::parse($data['as_of_date'])->format('d/m/Y') }}</p>
            <p>Dicetak: {{ $generated_at }}</p>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Kontak</th>
                    <th>Belum JT</th>
                    <th>1-30 Hari</th>
                    <th>31-60 Hari</th>
                    <th>61-90 Hari</th>
                    <th>>90 Hari</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items'] as $item)
                    @php
                        $rowClass = '';
                        if ($item['over_90'] > 0) $rowClass = 'warn-over-90';
                        elseif ($item['days_61_90'] > 0) $rowClass = 'warn-61-90';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>{{ $item['contact']->name }}</td>
                        <td>Rp {{ number_format($item['current'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item['days_1_30'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item['days_31_60'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item['days_61_90'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($item['over_90'], 0, ',', '.') }}</td>
                        <td><strong>Rp {{ number_format($item['total'], 0, ',', '.') }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>TOTAL</td>
                    <td>Rp {{ number_format($data['totals']['current'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($data['totals']['days_1_30'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($data['totals']['days_31_60'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($data['totals']['days_61_90'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($data['totals']['over_90'], 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($data['grand_total'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">Dicetak oleh sistem akuntansi pada {{ $generated_at }}.</div>
    </div>
</body>
</html>
