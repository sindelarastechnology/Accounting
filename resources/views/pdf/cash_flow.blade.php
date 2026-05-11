<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Arus Kas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px solid #2563eb; }
        .header h1 { font-size: 16px; color: #1e40af; }
        .header h2 { font-size: 14px; margin-top: 4px; }
        .header p { font-size: 9px; color: #666; }
        .section { margin-top: 12px; }
        .section h3 { font-size: 11px; background: #f3f4f6; padding: 6px 8px; border-left: 3px solid #2563eb; margin-bottom: 4px; }
        .line { display: flex; justify-content: space-between; padding: 3px 8px; font-size: 10px; }
        .line.indent { padding-left: 24px; color: #4b5563; }
        .line.total { font-weight: bold; border-top: 1px solid #9ca3af; padding-top: 6px; margin-top: 4px; }
        .line.grand-total { font-size: 12px; font-weight: bold; border-top: 2px solid #1e40af; padding-top: 8px; margin-top: 6px; }
        .line .name { flex: 1; }
        .line .amount { text-align: right; width: 120px; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name', 'Onezie Accounting') }}</h1>
            <h2>LAPORAN ARUS KAS</h2>
            <p>Metode Tidak Langsung</p>
            <p>Periode: {{ \Carbon\Carbon::parse($data['date_from'])->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($data['date_to'])->format('d/m/Y') }}</p>
            <p>Dicetak: {{ $generated_at }}</p>
        </div>

        <div class="section">
            <h3>AKTIVITAS OPERASI</h3>
            <div class="line">
                <span class="name">Laba Bersih</span>
                <span class="amount">Rp {{ number_format($data['operating']['net_income'], 0, ',', '.') }}</span>
            </div>
            <div class="line" style="font-weight: bold; margin-top: 4px;">
                <span class="name">Penyesuaian:</span>
            </div>
            @foreach($data['operating']['adjustments'] as $adj)
                <div class="line indent">
                    <span class="name">{{ $adj['label'] }}</span>
                    <span class="amount">{{ $adj['amount'] < 0 ? '(' : '' }}Rp {{ number_format(abs($adj['amount']), 0, ',', '.') }}{{ $adj['amount'] < 0 ? ')' : '' }}</span>
                </div>
            @endforeach
            <div class="line total">
                <span class="name">Kas dari Aktivitas Operasi</span>
                <span class="amount">Rp {{ number_format($data['operating']['total'], 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="section">
            <h3>AKTIVITAS INVESTASI</h3>
            @foreach($data['investing']['items'] as $item)
                <div class="line">
                    <span class="name">{{ $item['label'] }}</span>
                    <span class="amount">{{ $item['amount'] < 0 ? '(' : '' }}Rp {{ number_format(abs($item['amount']), 0, ',', '.') }}{{ $item['amount'] < 0 ? ')' : '' }}</span>
                </div>
            @endforeach
            @if(empty($data['investing']['items']))
                <div class="line indent"><span class="name">Tidak ada aktivitas investasi</span></div>
            @endif
            <div class="line total">
                <span class="name">Kas dari Aktivitas Investasi</span>
                <span class="amount">Rp {{ number_format($data['investing']['total'], 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="section">
            <h3>AKTIVITAS PENDANAAN</h3>
            @foreach($data['financing']['items'] as $item)
                <div class="line">
                    <span class="name">{{ $item['label'] }}</span>
                    <span class="amount">{{ $item['amount'] < 0 ? '(' : '' }}Rp {{ number_format(abs($item['amount']), 0, ',', '.') }}{{ $item['amount'] < 0 ? ')' : '' }}</span>
                </div>
            @endforeach
            @if(empty($data['financing']['items']))
                <div class="line indent"><span class="name">Tidak ada aktivitas pendanaan</span></div>
            @endif
            <div class="line total">
                <span class="name">Kas dari Aktivitas Pendanaan</span>
                <span class="amount">Rp {{ number_format($data['financing']['total'], 0, ',', '.') }}</span>
            </div>
        </div>

        <div style="margin-top: 15px; border-top: 2px solid #1e40af; padding-top: 8px;">
            <div class="line grand-total">
                <span class="name">Kenaikan/(Penurunan) Kas Bersih</span>
                <span class="amount">Rp {{ number_format($data['net_change'], 0, ',', '.') }}</span>
            </div>
            <div class="line grand-total">
                <span class="name">Saldo Kas Awal Periode</span>
                <span class="amount">Rp {{ number_format($data['opening_cash'], 0, ',', '.') }}</span>
            </div>
            <div class="line grand-total" style="border-top: 3px double #1e40af;">
                <span class="name">SALDO KAS AKHIR PERIODE</span>
                <span class="amount">Rp {{ number_format($data['closing_cash'], 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="footer">Dicetak oleh sistem akuntansi pada {{ $generated_at }}.</div>
    </div>
</body>
</html>
