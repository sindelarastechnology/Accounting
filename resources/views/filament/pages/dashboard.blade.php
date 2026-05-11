<x-filament-panels::page>
<div style="font-family:inherit;">

    {{-- HEADER + PERIOD SELECTOR --}}
    <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:1.25rem; gap:1rem; flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.25rem; font-weight:600; margin:0 0 4px 0; color:var(--fi-color-gray-900, #111827);">
                Dasbor Keuangan
            </h1>
            <p style="font-size:0.75rem; color:var(--fi-color-gray-500, #6B7280); margin:0;">
                @if(isset($this->dashboardData['period']))
                    {{ $this->dashboardData['period']->start_date->format('d M Y') }}
                    — {{ $this->dashboardData['period']->end_date->format('d M Y') }}
                @endif
            </p>
        </div>
        <div style="min-width:200px; max-width:280px; flex:1;">
            {{ $this->form }}
        </div>
    </div>

    {{-- ALERT BAR --}}
    @if(!empty($this->dashboardData['alerts']))
    <div style="margin-bottom:1rem;">
        @foreach($this->dashboardData['alerts'] as $alert)
        <div style="display:flex; align-items:center; gap:10px; background:#EFF6FF; border:1px solid #BFDBFE; border-radius:8px; padding:10px 14px; margin-bottom:6px;">
            <x-heroicon-o-information-circle style="width:18px; height:18px; color:#3B82F6; flex-shrink:0;" />
            <span style="font-size:0.8125rem; color:#1E40AF;">{{ $alert }}</span>
        </div>
        @endforeach
    </div>
    @endif

    @if($this->dashboardData)

    {{-- KPI CARDS --}}
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px;">

        {{-- Pendapatan --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <div style="width:32px; height:32px; border-radius:8px; background:#D1FAE5; display:flex; align-items:center; justify-content:center;">
                    <x-heroicon-o-arrow-trending-up style="width:16px; height:16px; color:#065F46;" />
                </div>
                <span style="font-size:0.75rem; color:#6B7280; font-weight:500;">Total Pendapatan</span>
            </div>
            <div style="font-size:1.375rem; font-weight:700; color:#111827; margin-bottom:4px;">
                Rp {{ number_format($this->dashboardData['revenue'] ?? 0, 0, ',', '.') }}
            </div>
            <div style="font-size:0.7rem; color:#6B7280;">Periode ini</div>
        </div>

        {{-- Beban --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <div style="width:32px; height:32px; border-radius:8px; background:#FEE2E2; display:flex; align-items:center; justify-content:center;">
                    <x-heroicon-o-arrow-trending-down style="width:16px; height:16px; color:#991B1B;" />
                </div>
                <span style="font-size:0.75rem; color:#6B7280; font-weight:500;">Total Beban</span>
            </div>
            <div style="font-size:1.375rem; font-weight:700; color:#111827; margin-bottom:4px;">
                Rp {{ number_format($this->dashboardData['expense'] ?? 0, 0, ',', '.') }}
            </div>
            <div style="font-size:0.7rem; color:#6B7280;">Periode ini</div>
        </div>

        {{-- Laba Bersih --}}
        @php $profit = $this->dashboardData['netProfit'] ?? 0; @endphp
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <div style="width:32px; height:32px; border-radius:8px; background:{{ $profit >= 0 ? '#D1FAE5' : '#FEE2E2' }}; display:flex; align-items:center; justify-content:center;">
                    <x-heroicon-o-banknotes style="width:16px; height:16px; color:{{ $profit >= 0 ? '#065F46' : '#991B1B' }};" />
                </div>
                <span style="font-size:0.75rem; color:#6B7280; font-weight:500;">Laba Bersih</span>
            </div>
            <div style="font-size:1.375rem; font-weight:700; color:{{ $profit >= 0 ? '#065F46' : '#DC2626' }}; margin-bottom:4px;">
                @if($profit < 0)(Rp {{ number_format(abs($profit), 0, ',', '.') }})@else Rp {{ number_format($profit, 0, ',', '.') }}@endif
            </div>
            <div style="font-size:0.7rem; color:{{ $profit >= 0 ? '#1D9E75' : '#E24B4A' }};">
                {{ $profit >= 0 ? 'Untung' : 'Rugi' }}
            </div>
        </div>

        {{-- Total Kas --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
                <div style="width:32px; height:32px; border-radius:8px; background:#DBEAFE; display:flex; align-items:center; justify-content:center;">
                    <x-heroicon-o-building-library style="width:16px; height:16px; color:#1E40AF;" />
                </div>
                <span style="font-size:0.75rem; color:#6B7280; font-weight:500;">Total Kas & Bank</span>
            </div>
            <div style="font-size:1.375rem; font-weight:700; color:#111827; margin-bottom:4px;">
                Rp {{ number_format($this->dashboardData['totalCash'] ?? 0, 0, ',', '.') }}
            </div>
            <div style="font-size:0.7rem; color:#6B7280;">
                {{ count($this->dashboardData['walletBalances'] ?? []) }} rekening aktif
            </div>
        </div>
    </div>

    {{-- ROW 2: GRAFIK + BREAKDOWN KAS --}}
    <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:12px; margin-bottom:16px;">

        {{-- Chart --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-size:0.875rem; font-weight:600; color:#111827;">Tren Arus Kas 6 Bulan</span>
                <a href="{{ url('/admin/cash-flow') }}" style="font-size:0.75rem; color:#185FA5; text-decoration:none;">Lihat detail &rarr;</a>
            </div>
            <canvas id="cashTrendChart" style="width:100%; height:180px; max-height:180px;"></canvas>
        </div>

        {{-- Breakdown --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="font-size:0.875rem; font-weight:600; color:#111827; margin-bottom:12px;">Kas Masuk & Keluar</div>

            <div style="font-size:0.7rem; font-weight:600; color:#065F46; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px;">Masuk</div>
            @php $ci = $this->dashboardData['cashIn'] ?? []; $maxIn = max(array_column($ci, 'amount') ?: [1]); @endphp
            @forelse($ci as $item)
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                <div style="font-size:0.7rem; color:#6B7280; width:80px; text-align:right; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item['label'] }}</div>
                <div style="flex:1; height:7px; background:#F3F4F6; border-radius:4px; overflow:hidden;">
                    <div style="height:100%; border-radius:4px; background:#1D9E75; width:{{ min(100, ($item['amount']/$maxIn)*100) }}%;"></div>
                </div>
                <div style="font-size:0.7rem; color:#065F46; width:65px; text-align:right; flex-shrink:0; white-space:nowrap;">Rp {{ number_format($item['amount'], 0, ',', '.') }}</div>
            </div>
            @empty
            <div style="font-size:0.75rem; color:#9CA3AF; padding:4px 0;">Tidak ada pemasukan</div>
            @endforelse

            <div style="height:1px; background:#F3F4F6; margin:10px 0;"></div>

            <div style="font-size:0.7rem; font-weight:600; color:#991B1B; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:6px;">Keluar</div>
            @php $co = $this->dashboardData['cashOut'] ?? []; $maxOut = max(array_column($co, 'amount') ?: [1]); @endphp
            @forelse($co as $item)
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:5px;">
                <div style="font-size:0.7rem; color:#6B7280; width:80px; text-align:right; flex-shrink:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $item['label'] }}</div>
                <div style="flex:1; height:7px; background:#F3F4F6; border-radius:4px; overflow:hidden;">
                    <div style="height:100%; border-radius:4px; background:#E24B4A; width:{{ min(100, ($item['amount']/$maxOut)*100) }}%;"></div>
                </div>
                <div style="font-size:0.7rem; color:#991B1B; width:65px; text-align:right; flex-shrink:0; white-space:nowrap;">Rp {{ number_format($item['amount'], 0, ',', '.') }}</div>
            </div>
            @empty
            <div style="font-size:0.75rem; color:#9CA3AF; padding:4px 0;">Tidak ada pengeluaran</div>
            @endforelse
        </div>
    </div>

    {{-- ROW 3: SALDO + PIUTANG/HUTANG + TRANSAKSI --}}
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:16px;">

        {{-- Saldo Rekening --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-size:0.875rem; font-weight:600; color:#111827;">Saldo Rekening</span>
                <a href="{{ url('/admin/reports/wallet-mutation') }}" style="font-size:0.75rem; color:#185FA5; text-decoration:none;">Mutasi &rarr;</a>
            </div>
            @foreach($this->dashboardData['walletBalances'] ?? [] as $wallet)
            @php
                $iconBg = match($wallet['type']) { 'bank' => '#DBEAFE', 'cash' => '#D1FAE5', 'ewallet' => '#FEF3C7', default => '#F3F4F6' };
                $iconCol = match($wallet['type']) { 'bank' => '#1E40AF', 'cash' => '#065F46', 'ewallet' => '#92400E', default => '#6B7280' };
                $tLabel = match($wallet['type']) { 'bank' => 'Bank', 'cash' => 'Tunai', 'ewallet' => 'E-Wallet', default => $wallet['type'] };
            @endphp
            <div style="display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #F3F4F6;">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:32px; height:32px; border-radius:8px; background:{{ $iconBg }}; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                        @if($wallet['type'] === 'bank')
                            <x-heroicon-o-building-library style="width:15px; height:15px; color:{{ $iconCol }};" />
                        @elseif($wallet['type'] === 'cash')
                            <x-heroicon-o-wallet style="width:15px; height:15px; color:{{ $iconCol }};" />
                        @else
                            <x-heroicon-o-device-phone-mobile style="width:15px; height:15px; color:{{ $iconCol }};" />
                        @endif
                    </div>
                    <div>
                        <div style="font-size:0.8125rem; font-weight:500; color:#111827;">{{ $wallet['name'] }}</div>
                        <div style="font-size:0.7rem; color:#9CA3AF;">{{ $tLabel }}</div>
                    </div>
                </div>
                <div style="font-size:0.8125rem; font-weight:600; color:#111827;">Rp {{ number_format($wallet['balance'], 0, ',', '.') }}</div>
            </div>
            @endforeach
            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:10px; margin-top:2px;">
                <span style="font-size:0.75rem; color:#6B7280; font-weight:500;">Total</span>
                <span style="font-size:0.9375rem; font-weight:700; color:#111827;">Rp {{ number_format($this->dashboardData['totalCash'] ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- Piutang & Hutang --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-size:0.875rem; font-weight:600; color:#111827;">Piutang & Hutang</span>
                <a href="{{ url('/admin/reports/aging-report') }}" style="font-size:0.75rem; color:#185FA5; text-decoration:none;">Aging &rarr;</a>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:14px;">
                <div style="background:#F0FDF4; border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-size:0.7rem; color:#15803D; font-weight:500; margin-bottom:4px;">Piutang</div>
                    <div style="font-size:1rem; font-weight:700; color:#14532D;">Rp {{ number_format($this->dashboardData['totalAR'] ?? 0, 0, ',', '.') }}</div>
                    @if(($this->dashboardData['overdueAR'] ?? 0) > 0)
                    <div style="font-size:0.65rem; color:#DC2626; margin-top:2px;">Rp {{ number_format($this->dashboardData['overdueAR'], 0, ',', '.') }} overdue</div>
                    @endif
                </div>
                <div style="background:#FFFBEB; border-radius:8px; padding:12px; text-align:center;">
                    <div style="font-size:0.7rem; color:#B45309; font-weight:500; margin-bottom:4px;">Hutang</div>
                    <div style="font-size:1rem; font-weight:700; color:#78350F;">Rp {{ number_format($this->dashboardData['totalAP'] ?? 0, 0, ',', '.') }}</div>
                    @if(($this->dashboardData['overdueAP'] ?? 0) > 0)
                    <div style="font-size:0.65rem; color:#DC2626; margin-top:2px;">Rp {{ number_format($this->dashboardData['overdueAP'], 0, ',', '.') }} overdue</div>
                    @endif
                </div>
            </div>
            <div style="font-size:0.7rem;">
                @php
                    $arT = $this->dashboardData['totalAR'] ?? 0;
                    $apT = $this->dashboardData['totalAP'] ?? 0;
                    $arO = $this->dashboardData['overdueAR'] ?? 0;
                    $apO = $this->dashboardData['overdueAP'] ?? 0;
                @endphp
                <div style="display:flex; gap:4px; padding:4px 0; border-bottom:1px solid #F3F4F6; color:#9CA3AF; font-weight:500;">
                    <div style="flex:1;">Kategori</div>
                    <div style="width:60px; text-align:right;">Piutang</div>
                    <div style="width:60px; text-align:right;">Hutang</div>
                </div>
                <div style="display:flex; gap:4px; padding:5px 0; border-bottom:1px solid #F3F4F6;">
                    <div style="flex:1; color:#374151;">Current</div>
                    <div style="width:60px; text-align:right; color:#065F46;">Rp {{ number_format(max(0, $arT - $arO), 0, ',', '.') }}</div>
                    <div style="width:60px; text-align:right; color:#92400E;">Rp {{ number_format(max(0, $apT - $apO), 0, ',', '.') }}</div>
                </div>
                <div style="display:flex; gap:4px; padding:5px 0;">
                    <div style="flex:1; color:#DC2626;">> 30 hari</div>
                    <div style="width:60px; text-align:right; color:#DC2626; font-weight:500;">Rp {{ number_format($arO, 0, ',', '.') }}</div>
                    <div style="width:60px; text-align:right; color:#DC2626; font-weight:500;">Rp {{ number_format($apO, 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Transaksi Terbaru --}}
        <div style="background:#fff; border:1px solid #E5E7EB; border-radius:12px; padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <span style="font-size:0.875rem; font-weight:600; color:#111827;">Transaksi Terbaru</span>
                <a href="{{ url('/admin/journals') }}" style="font-size:0.75rem; color:#185FA5; text-decoration:none;">Semua &rarr;</a>
            </div>
            @foreach($this->dashboardData['recentJournals'] ?? [] as $jrn)
            @php
                $dot = match($jrn['source']) {
                    'sale' => '#1D9E75', 'purchase' => '#E24B4A', 'payment' => '#185FA5',
                    'expense' => '#F59E0B', 'other_receipt' => '#8B5CF6',
                    'transfer' => '#06B6D4', 'opening' => '#9CA3AF',
                    default => '#D1D5DB'
                };
            @endphp
            <div style="display:flex; align-items:center; gap:8px; padding:7px 0; border-bottom:1px solid #F9FAFB;">
                <div style="width:8px; height:8px; border-radius:50%; background:{{ $dot }}; flex-shrink:0;"></div>
                <div style="flex:1; font-size:0.775rem; color:#374151; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $jrn['description'] }}">{{ $jrn['description'] ?: $jrn['number'] }}</div>
                <div style="font-size:0.7rem; color:#9CA3AF; flex-shrink:0; margin-right:4px;">{{ $jrn['date'] }}</div>
                <div style="font-size:0.775rem; font-weight:500; color:#374151; flex-shrink:0;">Rp {{ number_format($jrn['total'], 0, ',', '.') }}</div>
            </div>
            @endforeach
            <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;">
                @foreach([['#1D9E75','Jual'],['#E24B4A','Beli'],['#185FA5','Bayar'],['#F59E0B','Beban'],['#8B5CF6','Kas masuk']] as $leg)
                <div style="display:flex; align-items:center; gap:4px;">
                    <div style="width:7px; height:7px; border-radius:50%; background:{{ $leg[0] }};"></div>
                    <span style="font-size:0.65rem; color:#9CA3AF;">{{ $leg[1] }}</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    @else
    <div style="text-align:center; padding:48px 0; color:#9CA3AF; font-style:italic; border:1px dashed #D1D5DB; border-radius:8px;">
        Pilih periode untuk menampilkan dashboard
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('livewire:navigated', initCharts);
document.addEventListener('DOMContentLoaded', initCharts);

function initCharts() {
    var canvas = document.getElementById('cashTrendChart');
    if (!canvas) return;
    if (canvas._chartInstance) { canvas._chartInstance.destroy(); }

    var data = @json($this->dashboardData['cashTrend'] ?? []);
    if (!data || data.length === 0) { canvas.style.display = 'none'; return; }
    canvas.style.display = 'block';

    canvas._chartInstance = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.map(function(d) { return d.label; }),
            datasets: [
                {
                    label: 'Kas Masuk',
                    data: data.map(function(d) { return d.cash_in; }),
                    backgroundColor: '#1D9E75',
                    borderRadius: 4,
                    borderSkipped: false,
                },
                {
                    label: 'Kas Keluar',
                    data: data.map(function(d) { return d.cash_out; }),
                    backgroundColor: '#E24B4A',
                    borderRadius: 4,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index' },
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 16, boxWidth: 12, boxHeight: 12 } },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID'); }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F3F4F6' },
                    ticks: {
                        font: { size: 10 },
                        callback: function(val) {
                            if (val >= 1000000) return 'Rp ' + (val/1000000).toFixed(1) + 'jt';
                            if (val >= 1000) return 'Rp ' + (val/1000).toFixed(0) + 'rb';
                            return 'Rp ' + val;
                        }
                    }
                },
                x: { grid: { display: false }, ticks: { font: { size: 11 } } }
            }
        }
    });
}
</script>
</x-filament-panels::page>
