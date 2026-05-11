<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mutasi Kas & Bank</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 8pt; background: #fff; }
        h2 { text-align: center; font-size: 14pt; margin-bottom: 4px; }
        .subtitle { text-align: center; font-size: 9pt; color: #666; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; font-size: 7pt; }
        td { border: 1px solid #d1d5db; padding: 3px 6px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: "Courier New", monospace; }
        .wallet-header { background: #e5e7eb; font-weight: bold; padding: 6px 8px; margin-top: 16px; }
        .total-row { font-weight: bold; background: #f9fafb; }
        .opening-row { color: #9ca3af; font-style: italic; }
        .danger { color: #dc2626; }
    </style>
</head>
<body>
    <h2>MUTASI KAS & BANK</h2>
    <p class="subtitle">Periode: {{ $data['date_from'] ?? '-' }} s/d {{ $data['date_to'] ?? '-' }}</p>

    @forelse($data['wallets'] ?? [] as $walletData)
        <div class="wallet-header">{{ $walletData['wallet']->name }} ({{ $walletData['wallet']->type }})</div>
        <table>
            <thead>
                <tr>
                    <th style="width:12%">Tanggal</th>
                    <th>Keterangan</th>
                    <th style="width:15%" class="text-right">Masuk (Rp)</th>
                    <th style="width:15%" class="text-right">Keluar (Rp)</th>
                    <th style="width:15%" class="text-right">Saldo (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @if($walletData['opening_balance'] != 0)
                <tr class="opening-row">
                    <td>{{ $data['date_from'] }}</td>
                    <td>Saldo Awal</td>
                    <td class="text-right">{{ number_format($walletData['opening_balance'], 0, ',', '.') }}</td>
                    <td class="text-right"></td>
                    <td class="text-right">{{ number_format($walletData['opening_balance'], 0, ',', '.') }}</td>
                </tr>
                @endif
                @forelse($walletData['rows'] as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                    <td>[{{ $row['journal_number'] }}] {{ $row['description'] }}</td>
                    <td class="text-right">@if($row['masuk'] > 0){{ number_format($row['masuk'], 0, ',', '.') }}@endif</td>
                    <td class="text-right danger">@if($row['keluar'] > 0){{ number_format($row['keluar'], 0, ',', '.') }}@endif</td>
                    <td class="text-right">{{ number_format($row['saldo'], 0, ',', '.') }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center">Tidak ada transaksi</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td class="text-right">{{ number_format($walletData['total_masuk'], 0, ',', '.') }}</td>
                    <td class="text-right danger">{{ number_format($walletData['total_keluar'], 0, ',', '.') }}</td>
                    <td class="text-right"></td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" class="text-right">Saldo Akhir</td>
                    <td class="text-right">{{ number_format($walletData['closing_balance'], 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <p class="text-center">Tidak ada wallet aktif</p>
    @endforelse

    <p style="text-align:center;font-size:7pt;color:#9ca3af;margin-top:20px;">
        Dicetak: {{ $generated_at ?? now()->format('d/m/Y H:i') }}
    </p>
</body>
</html>
