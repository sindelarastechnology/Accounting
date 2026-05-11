<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Buku Kas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 7pt; background: #fff; }
        h2 { text-align: center; font-size: 14pt; margin-bottom: 4px; }
        .subtitle { text-align: center; font-size: 9pt; color: #666; margin-bottom: 16px; }
        .info { font-size: 8pt; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 3px 5px; text-align: left; font-size: 6.5pt; }
        td { border: 1px solid #d1d5db; padding: 2px 5px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { font-weight: bold; background: #f9fafb; }
        .danger { color: #dc2626; }
    </style>
</head>
<body>
    <h2>BUKU KAS</h2>
    <p class="subtitle">Periode: {{ $data['date_from'] ?? '-' }} s/d {{ $data['date_to'] ?? '-' }}</p>
    <p class="info">Saldo Awal: <strong>Rp {{ number_format($data['opening_balance'] ?? 0, 0, ',', '.') }}</strong></p>

    <table>
        <thead>
            <tr>
                <th style="width:10%">Tanggal</th>
                <th style="width:12%">No. Jurnal</th>
                <th>Keterangan</th>
                <th>Akun Lawan</th>
                <th style="width:10%">Wallet</th>
                <th style="width:12%" class="text-right">Masuk (Rp)</th>
                <th style="width:12%" class="text-right">Keluar (Rp)</th>
                <th style="width:12%" class="text-right">Saldo (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['rows'] ?? [] as $row)
            <tr>
                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                <td>{{ $row['journal_number'] }}</td>
                <td>{{ $row['description'] }}</td>
                <td>{{ $row['counter_account'] }}</td>
                <td>{{ $row['wallet_name'] }}</td>
                <td class="text-right">@if($row['masuk'] > 0){{ number_format($row['masuk'], 0, ',', '.') }}@endif</td>
                <td class="text-right danger">@if($row['keluar'] > 0){{ number_format($row['keluar'], 0, ',', '.') }}@endif</td>
                <td class="text-right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center">Tidak ada transaksi</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL</td>
                <td class="text-right">{{ number_format($data['total_masuk'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right danger">{{ number_format($data['total_keluar'] ?? 0, 0, ',', '.') }}</td>
                <td class="text-right"></td>
            </tr>
            <tr class="total-row">
                <td colspan="7" class="text-right">Saldo Akhir</td>
                <td class="text-right">{{ number_format($data['closing_balance'] ?? 0, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <p style="text-align:center;font-size:7pt;color:#9ca3af;margin-top:20px;">
        Dicetak: {{ $generated_at ?? now()->format('d/m/Y H:i') }}
    </p>
</body>
</html>
