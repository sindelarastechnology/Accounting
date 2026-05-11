<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan PPN</title>
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
        <h1>LAPORAN PPN (Masukan & Keluaran)</h1>
        @if($period)
            <p>Periode: {{ $period->name }}</p>
        @elseif($data['ppn_masukan'][0]['date'] ?? null)
            <p>Tanggal: {{ \Carbon\Carbon::parse($data['ppn_masukan'][0]['date'])->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($data['ppn_keluaran'][0]['date'] ?? $data['ppn_masukan'][0]['date'])->format('d/m/Y') }}</p>
        @endif
        <p>Digenerate pada: {{ $generated_at }}</p>
    </div>

    <div class="section">
        <div class="section-title" style="color: #16a34a;">PPN Masukan (Pembelian)</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Ref</th>
                    <th>Supplier</th>
                    <th class="text-right">DPP</th>
                    <th class="text-right">PPN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['ppn_masukan'] as $item)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                        <td>{{ $item['number'] }}</td>
                        <td>{{ $item['contact'] }}</td>
                        <td class="text-right">{{ rupiah($item['base_amount']) }}</td>
                        <td class="text-right font-bold">{{ rupiah($item['tax_amount']) }}</td>
                    </tr>
                @endforeach
                @if(count($data['ppn_masukan']) === 0)
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data PPN Masukan</td>
                    </tr>
                @endif
                <tr class="font-bold" style="background-color: #f0fdf4;">
                    <td colspan="4" class="text-right">Total PPN Masukan</td>
                    <td class="text-right" style="color: #16a34a;">{{ rupiah($data['total_ppn_masukan']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title" style="color: #2563eb;">PPN Keluaran (Penjualan)</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No. Ref</th>
                    <th>Customer</th>
                    <th class="text-right">DPP</th>
                    <th class="text-right">PPN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['ppn_keluaran'] as $item)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                        <td>{{ $item['number'] }}</td>
                        <td>{{ $item['contact'] }}</td>
                        <td class="text-right">{{ rupiah($item['base_amount']) }}</td>
                        <td class="text-right font-bold">{{ rupiah($item['tax_amount']) }}</td>
                    </tr>
                @endforeach
                @if(count($data['ppn_keluaran']) === 0)
                    <tr>
                        <td colspan="5" class="text-center">Tidak ada data PPN Keluaran</td>
                    </tr>
                @endif
                <tr class="font-bold" style="background-color: #eff6ff;">
                    <td colspan="4" class="text-right">Total PPN Keluaran</td>
                    <td class="text-right" style="color: #2563eb;">{{ rupiah($data['total_ppn_keluaran']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section summary">
        <div class="section-title">Rekapitulasi PPN</div>
        <div>
            <div class="summary-item">
                <div>Total PPN Masukan</div>
                <div class="summary-value" style="color: #16a34a;">{{ rupiah($data['total_ppn_masukan']) }}</div>
            </div>
            <div class="summary-item">
                <div>Total PPN Keluaran</div>
                <div class="summary-value" style="color: #2563eb;">{{ rupiah($data['total_ppn_keluaran']) }}</div>
            </div>
            @if($data['ppn_kurang_bayar'] > 0)
                <div class="summary-item">
                    <div>PPN Kurang Bayar</div>
                    <div class="summary-value" style="color: #dc2626;">{{ rupiah($data['ppn_kurang_bayar']) }}</div>
                </div>
            @else
                <div class="summary-item">
                    <div>PPN Lebih Bayar</div>
                    <div class="summary-value" style="color: #9333ea;">{{ rupiah($data['ppn_lebih_bayar']) }}</div>
                </div>
            @endif
        </div>
    </div>
</body>
</html>
