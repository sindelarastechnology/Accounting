<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Neraca</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1a1a1a; line-height: 1.4; }
        .container { max-width: 750px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 3px solid #2563eb; }
        .header h1 { font-size: 16px; color: #1e40af; }
        .header h2 { font-size: 14px; margin-top: 4px; }
        .header p { font-size: 9px; color: #666; }
        .two-col { display: flex; gap: 20px; }
        .col { flex: 1; }
        .section { margin-top: 10px; }
        .section h3 { font-size: 11px; background: #f3f4f6; padding: 5px 8px; border-left: 3px solid #2563eb; margin-bottom: 4px; }
        .line { display: flex; justify-content: space-between; padding: 3px 8px; font-size: 9px; }
        .line.total { font-weight: bold; border-top: 1px solid #9ca3af; padding-top: 5px; margin-top: 3px; }
        .line.grand-total { font-size: 11px; font-weight: bold; border-top: 2px solid #1e40af; padding-top: 6px; margin-top: 6px; }
        .line .name { flex: 1; }
        .line .amount { text-align: right; width: 100px; }
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
            <h2>NERACA</h2>
            <p>Per Tanggal: {{ \Carbon\Carbon::parse($data['as_of_date'])->format('d/m/Y') }}</p>
            <p>Dicetak: {{ $generated_at }}</p>
        </div>

        <div class="two-col">
            <div class="col">
                <div class="section">
                    <h3>ASET</h3>
                    <h4 style="font-size: 10px; margin: 6px 0 3px; color: #4b5563;">Aset Lancar</h4>
                    @foreach($data['assets']['current'] as $item)
                        <div class="line">
                            <span class="name">{{ $item['account']->name }}</span>
                            <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="line total">
                        <span class="name">Total Aset Lancar</span>
                        <span class="amount">Rp {{ number_format($data['assets']['total_current'], 0, ',', '.') }}</span>
                    </div>

                    <h4 style="font-size: 10px; margin: 10px 0 3px; color: #4b5563;">Aset Tetap</h4>
                    @foreach($data['assets']['fixed'] as $item)
                        <div class="line">
                            <span class="name">{{ $item['account']->name }}</span>
                            <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="line total">
                        <span class="name">Total Aset Tetap</span>
                        <span class="amount">Rp {{ number_format($data['assets']['total_fixed'], 0, ',', '.') }}</span>
                    </div>
                    <div class="line grand-total">
                        <span class="name">TOTAL ASET</span>
                        <span class="amount">Rp {{ number_format($data['assets']['total'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            <div class="col">
                <div class="section">
                    <h3>LIABILITAS & EKUITAS</h3>
                    <h4 style="font-size: 10px; margin: 6px 0 3px; color: #4b5563;">Liabilitas Jangka Pendek</h4>
                    @foreach($data['liabilities']['current'] as $item)
                        <div class="line">
                            <span class="name">{{ $item['account']->name }}</span>
                            <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="line total">
                        <span class="name">Total Liabilitas</span>
                        <span class="amount">Rp {{ number_format($data['liabilities']['total'], 0, ',', '.') }}</span>
                    </div>

                    <h4 style="font-size: 10px; margin: 10px 0 3px; color: #4b5563;">Ekuitas</h4>
                    @foreach($data['equity']['accounts'] as $item)
                        <div class="line">
                            <span class="name">{{ $item['account']->name }}</span>
                            <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                    <div class="line">
                        <span class="name">Laba Periode Ini</span>
                        <span class="amount">Rp {{ number_format($data['equity']['net_income'], 0, ',', '.') }}</span>
                    </div>
                    <div class="line total">
                        <span class="name">Total Ekuitas</span>
                        <span class="amount">Rp {{ number_format($data['equity']['total'], 0, ',', '.') }}</span>
                    </div>
                    <div class="line grand-total">
                        <span class="name">TOTAL LIAB + EKUITAS</span>
                        <span class="amount">Rp {{ number_format($data['liabilities']['total'] + $data['equity']['total'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 15px;">
            @if($data['is_balanced'])
                <span class="balance-badge balance-ok">✓ Neraca Balance</span>
            @else
                <span class="balance-badge balance-fail">✗ Tidak Balance — Selisih: Rp {{ number_format(abs($data['difference']), 2, ',', '.') }}</span>
            @endif
        </div>

        <div class="footer">Dicetak oleh sistem akuntansi pada {{ $generated_at }}.</div>
    </div>
</body>
</html>
