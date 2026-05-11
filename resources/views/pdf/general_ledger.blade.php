<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Besar</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; color: #1a1a1a; }
        .header { text-align: center; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #2563eb; }
        .header h1 { font-size: 14pt; color: #1e40af; }
        .header h2 { font-size: 11pt; margin-top: 3px; }
        .header p { font-size: 7pt; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th { background: #f3f4f6; border: 1px solid #d1d5db; padding: 3px 4px; text-align: left; font-size: 6.5pt; }
        td { border: 1px solid #d1d5db; padding: 2px 4px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        .acct-header { background: #f9fafb; font-weight: bold; }
        .total-row { font-weight: bold; background: #f3f4f6; }
        .opening-row { color: #666; font-style: italic; font-size: 6.5pt; }
        .footer { text-align: center; margin-top: 16px; padding-top: 8px; border-top: 1px solid #e5e7eb; color: #9ca3af; font-size: 6pt; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ config('app.name', 'Onezie Accounting') }}</h1>
        <h2>BUKU BESAR</h2>
        <p>Periode: {{ $data['date_from'] ?? '-' }} s/d {{ $data['date_to'] ?? '-' }}</p>
        <p>Dicetak: {{ $generated_at }}</p>
    </div>

    @forelse($data['accounts'] ?? [] as $item)
        @php $acct = $item['account']; @endphp
        <table>
            <tr class="acct-header">
                <td colspan="7" style="font-size: 8pt;">
                    {{ $acct->code }} - {{ $acct->name }}
                    <span style="color: #6b7280; font-weight: normal; font-size: 6.5pt;">
                        ({{ $item['category_label'] }})
                    </span>
                </td>
            </tr>
            <tr>
                <th style="width:12%">Tanggal</th>
                <th style="width:14%">No Transaksi</th>
                <th>Keterangan</th>
                <th style="width:10%">Sumber</th>
                <th style="width:14%" class="text-right">Debit (Rp)</th>
                <th style="width:14%" class="text-right">Kredit (Rp)</th>
                <th style="width:14%" class="text-right">Saldo (Rp)</th>
            </tr>
            <tr class="opening-row">
                <td colspan="4">Saldo Awal</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($item['opening_balance'], 0, ',', '.') }}</td>
            </tr>
            @forelse($item['transactions'] ?? [] as $tx)
            <tr>
                <td>{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/Y') }}</td>
                <td>{{ $tx['journal_number'] }}</td>
                <td>{{ $tx['description'] }}</td>
                <td>{{ \App\Services\LedgerService::sourceLabel($tx['source']) }}</td>
                <td class="text-right">@if($tx['debit'] > 0){{ number_format($tx['debit'], 0, ',', '.') }}@endif</td>
                <td class="text-right">@if($tx['credit'] > 0){{ number_format($tx['credit'], 0, ',', '.') }}@endif</td>
                <td class="text-right">{{ number_format($tx['balance'], 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">Tidak ada transaksi</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="4" class="text-right">TOTAL PERIODE</td>
                <td class="text-right">{{ number_format($item['total_debit'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item['total_credit'], 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format($item['closing_balance'], 0, ',', '.') }}</td>
            </tr>
        </table>
    @empty
        <p style="text-align:center;padding:20px;color:#9ca3af;">Tidak ada data buku besar untuk periode ini</p>
    @endforelse

    @if(count($data['accounts'] ?? []) > 0)
        @php
            $grandDebit = collect($data['accounts'])->sum('total_debit');
            $grandCredit = collect($data['accounts'])->sum('total_credit');
            $assets   = collect($data['accounts'])->where('account.category', 'asset')->sum('closing_balance');
            $liabs    = collect($data['accounts'])->where('account.category', 'liability')->sum('closing_balance');
            $equity   = collect($data['accounts'])->where('account.category', 'equity')->sum('closing_balance');
            $revenue  = collect($data['accounts'])->where('account.category', 'revenue')->sum('closing_balance');
            $expenses = collect($data['accounts'])->whereIn('account.category', ['expense', 'cogs'])->sum('closing_balance');
            $netIncome = $revenue - $expenses;
        @endphp
        <table style="width:60%;margin:0 auto;">
            <tr class="acct-header"><td colspan="2" style="text-align:center;">REKAPITULASI</td></tr>
            <tr><th>Kelompok Akun</th><th class="text-right">Saldo (Rp)</th></tr>
            <tr><td>Aset</td><td class="text-right">{{ number_format($assets, 0, ',', '.') }}</td></tr>
            <tr><td>Kewajiban</td><td class="text-right">{{ number_format($liabs, 0, ',', '.') }}</td></tr>
            <tr><td>Ekuitas</td><td class="text-right">{{ number_format($equity, 0, ',', '.') }}</td></tr>
            <tr><td>Pendapatan</td><td class="text-right">{{ number_format($revenue, 0, ',', '.') }}</td></tr>
            <tr><td>Biaya & HPP</td><td class="text-right">{{ number_format($expenses, 0, ',', '.') }}</td></tr>
            <tr class="total-row"><td>Laba Bersih Periode</td><td class="text-right">{{ number_format($netIncome, 0, ',', '.') }}</td></tr>
        </table>
        <p style="text-align:center;font-size:7pt;margin-top:8px;">
            Total Debit: Rp {{ number_format($grandDebit, 0, ',', '.') }}
            | Total Kredit: Rp {{ number_format($grandCredit, 0, ',', '.') }}
            | {{ abs($grandDebit - $grandCredit) < 1 ? 'Balance' : 'Selisih: Rp ' . number_format(abs($grandDebit - $grandCredit), 0, ',', '.') }}
        </p>
    @endif

    <div class="footer">Dicetak oleh sistem akuntansi pada {{ $generated_at }}.</div>
</body>
</html>
