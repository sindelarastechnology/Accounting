<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
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
        .section h4 { font-size: 10px; background: #f9fafb; padding: 4px 8px; border-left: 2px solid #9ca3af; margin-bottom: 2px; }
        .line { display: flex; justify-content: space-between; padding: 3px 8px; font-size: 10px; }
        .line.total { font-weight: bold; border-top: 1px solid #9ca3af; padding-top: 6px; margin-top: 4px; }
        .line.grand-total { font-size: 12px; font-weight: bold; border-top: 2px solid #1e40af; padding-top: 8px; margin-top: 6px; }
        .line .code { color: #6b7280; width: 130px; min-width: 130px; white-space: nowrap; font-family: 'DejaVu Sans Mono', monospace; display: inline-block; }
        .line .name { flex: 1; }
        .line .amount { text-align: right; width: 140px; white-space: nowrap; }
        .negative { color: #dc2626; }
        .footer { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ config('app.name', 'Onezie Accounting') }}</h1>
            <h2>LAPORAN LABA RUGI</h2>
            <p>Periode: {{ $data['current']['period_label'] }}</p>
            <p>Dicetak: {{ $generated_at }}</p>
        </div>

        {{-- PENDAPATAN --}}
        <div class="section">
            <h3>PENDAPATAN</h3>
            @if(count($data['current']['revenue_operating']) > 0)
                <h4>Pendapatan Usaha</h4>
                @foreach($data['current']['revenue_operating'] as $item)
                    <div class="line">
                        <span class="code">{{ $item['account']->code }}</span>
                        <span class="name">{{ $item['account']->name }}</span>
                        <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            @endif
            @if(count($data['current']['revenue_other']) > 0)
                <h4>Pendapatan Lain-lain</h4>
                @foreach($data['current']['revenue_other'] as $item)
                    <div class="line">
                        <span class="code">{{ $item['account']->code }}</span>
                        <span class="name">{{ $item['account']->name }}</span>
                        <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            @endif
            <div class="line total">
                <span class="name">Total Pendapatan</span>
                <span class="amount">Rp {{ number_format($data['current']['total_revenue'], 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- HPP --}}
        <div class="section">
            <h3>HARGA POKOK PENJUALAN</h3>
            @foreach($data['current']['cogs'] as $item)
                <div class="line">
                    <span class="code">{{ $item['account']->code }}</span>
                    <span class="name">{{ $item['account']->name }}</span>
                    <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                </div>
            @endforeach
            <div class="line total">
                <span class="name">Total HPP</span>
                <span class="amount">Rp {{ number_format($data['current']['total_cogs'], 0, ',', '.') }}</span>
            </div>
            <div class="line grand-total">
                <span class="name">LABA KOTOR</span>
                <span class="amount {{ $data['current']['gross_profit'] < 0 ? 'negative' : '' }}">Rp {{ number_format($data['current']['gross_profit'], 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- BEBAN OPERASIONAL --}}
        <div class="section">
            <h3>BEBAN OPERASIONAL</h3>
            @forelse($data['current']['expense_operating'] as $item)
                <div class="line">
                    <span class="code">{{ $item['account']->code }}</span>
                    <span class="name">{{ $item['account']->name }}</span>
                    <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                </div>
            @empty
                <div class="line"><span class="name" style="color: #9ca3af;">Tidak ada data</span></div>
            @endforelse
            <div class="line total">
                <span class="name">Total Beban Operasional</span>
                <span class="amount">Rp {{ number_format($data['current']['total_expense_operating'], 0, ',', '.') }}</span>
            </div>
            <div class="line grand-total">
                <span class="name">LABA USAHA</span>
                <span class="amount {{ $data['current']['operating_profit'] < 0 ? 'negative' : '' }}">Rp {{ number_format($data['current']['operating_profit'], 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- BEBAN/PENDAPATAN LAIN-LAIN --}}
        @if(count($data['current']['revenue_other']) > 0 || count($data['current']['expense_other']) > 0)
            <div class="section">
                <h3>BEBAN / PENDAPATAN LAIN-LAIN</h3>
                @if(count($data['current']['revenue_other']) > 0)
                    <h4>Pendapatan Lain-lain</h4>
                    @foreach($data['current']['revenue_other'] as $item)
                        <div class="line">
                            <span class="code">{{ $item['account']->code }}</span>
                            <span class="name">{{ $item['account']->name }}</span>
                            <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                @endif
                @if(count($data['current']['expense_other']) > 0)
                    <h4>Beban Lain-lain</h4>
                    @foreach($data['current']['expense_other'] as $item)
                        <div class="line">
                            <span class="code">{{ $item['account']->code }}</span>
                            <span class="name">{{ $item['account']->name }}</span>
                            <span class="amount">Rp {{ number_format($item['balance'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
                @endif
                <div class="line total">
                    <span class="name">Total Lain-lain</span>
                    <span class="amount {{ $data['current']['other_total'] < 0 ? 'negative' : '' }}">Rp {{ number_format($data['current']['other_total'], 0, ',', '.') }}</span>
                </div>
            </div>
        @endif

        {{-- BEBAN PAJAK --}}
        @if($data['current']['tax_expense'] > 0)
            <div class="line total" style="margin-top:8px;">
                <span class="name">Beban Pajak Penghasilan</span>
                <span class="amount negative">Rp {{ number_format($data['current']['tax_expense'], 0, ',', '.') }}</span>
            </div>
        @endif

        {{-- LABA BERSIH --}}
        <div class="line grand-total" style="margin-top:10px; border-top-color: #dc2626;">
            <span class="name">LABA / RUGI BERSIH</span>
            <span class="amount {{ $data['current']['net_income'] < 0 ? 'negative' : '' }}">Rp {{ number_format($data['current']['net_income'], 0, ',', '.') }}</span>
        </div>

        <div class="footer">Dicetak oleh sistem akuntansi pada {{ $generated_at }}.</div>
    </div>
</body>
</html>
