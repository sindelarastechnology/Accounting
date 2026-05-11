<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan PPh 23</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; color: #333; }
        .summary { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }
        .summary-item { display: inline-block; margin-right: 30px; }
        .summary-value { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAP PEMOTONGAN PPh 23</h1>
        @if($period)
            <p>Periode: {{ $period->name }}</p>
        @elseif($data['items'][0]['date'] ?? null)
            <p>Tanggal: {{ \Carbon\Carbon::parse($data['items'][0]['date'])->format('d/m/Y') }}</p>
        @endif
        <p>Digenerate pada: {{ $generated_at }}</p>
    </div>

    <div class="section">
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>No. Ref</th>
                    <th>Supplier</th>
                    <th>NPWP</th>
                    <th>Keterangan</th>
                    <th class="text-right">DPP (Rp)</th>
                    <th class="text-center">Tarif (%)</th>
                    <th class="text-right">PPh 23 (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['items'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                        <td>{{ $item['number'] }}</td>
                        <td>{{ $item['contact'] }}</td>
                        <td>{{ $item['npwp'] }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td class="text-right">{{ rupiah($item['base_amount']) }}</td>
                        <td class="text-center">{{ $item['tax_rate'] }}%</td>
                        <td class="text-right font-bold" style="color: #dc2626;">{{ rupiah($item['tax_amount']) }}</td>
                    </tr>
                @endforeach
                @if(count($data['items']) === 0)
                    <tr>
                        <td colspan="9" class="text-center">Tidak ada data pemotongan PPh 23</td>
                    </tr>
                @endif
                <tr class="font-bold" style="background-color: #fef2f2;">
                    <td colspan="8" class="text-right">Total PPh 23 Dipotong</td>
                    <td class="text-right" style="color: #dc2626;">{{ rupiah($data['total']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section summary">
        <div class="section-title">Ringkasan</div>
        <div>
            <div class="summary-item">
                <div>Total PPh 23 Dipotong</div>
                <div class="summary-value" style="color: #dc2626;">{{ rupiah($data['total']) }}</div>
                <div style="font-size: 10px; color: #666;">Wajib setor ke Kas Negara</div>
            </div>
            <div class="summary-item">
                <div>Jumlah Transaksi</div>
                <div class="summary-value" style="color: #2563eb;">{{ count($data['items']) }} Transaksi</div>
            </div>
        </div>
    </div>
</body>
</html>
