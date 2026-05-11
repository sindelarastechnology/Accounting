<x-filament-panels::page>
    <x-report-table-styles />
    <style>
        @media print {
            body * { visibility: hidden; }
            .report-content, .report-content * { visibility: visible; }
            .report-content { position: absolute; left: 0; top: 0; width: 100%; }
            .filament-sidebar, .filament-topbar, .filament-main-topbar { display: none !important; }
            .no-print { display: none !important; }
            @page { margin: 10mm; }
        }
    </style>
    <div class="report-content">
    <x-filament::section>
        <x-slot name="headerForm">
            {{ $this->form }}
        </x-slot>

        @php $data = $this->getData()['current']; @endphp

        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">LAPORAN LABA RUGI</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ config('app.name', 'Onezie Accounting') }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">Periode: {{ $data['period_label'] }}</p>
        </div>

        <div class="space-y-4">
            {{-- PENDAPATAN --}}
            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">PENDAPATAN</h3>
                <div class="border border-t-0 rounded-b divide-y">
                    @if(count($data['revenue_operating']) > 0)
                        <div class="px-3 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50">Pendapatan Usaha</div>
                        @foreach($data['revenue_operating'] as $item)
                            <div class="report-flex-row">
                                <span class="flex-code">{{ $item['account']->code }}</span>
                                <span class="flex-name">{{ $item['account']->name }}</span>
                                <span class="flex-amount">{{ rupiah($item['balance']) }}</span>
                            </div>
                        @endforeach
                    @endif
                    <div class="flex justify-between px-3 py-2 font-bold border-t">
                        <span>Total Pendapatan Usaha</span>
                        <span class="font-mono">{{ rupiah($data['total_revenue_operating']) }}</span>
                    </div>
                </div>
            </div>

            {{-- HPP + BREAKDOWN --}}
            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">HARGA POKOK PENJUALAN (HPP)</h3>
                <div class="border border-t-0 rounded-b divide-y">

                    {{-- HPP Breakdown Formula --}}
                    @php $hpp = $data['hpp_breakdown'] ?? null; @endphp
                    @if($hpp)
                        <div class="bg-gray-50 dark:bg-gray-800/30 px-3 py-2 text-sm border-b">
                            <div class="font-semibold text-xs text-gray-500 dark:text-gray-400 mb-1 uppercase tracking-wider">Breakdown HPP (FIFO)</div>
                            <div class="report-flex-row">
                                <span class="flex-name">Persediaan Awal</span>
                                <span class="flex-amount">{{ rupiah($hpp['opening_inventory']) }}</span>
                            </div>
                            <div class="report-flex-row">
                                <span class="flex-name">Pembelian Bersih</span>
                                <span class="flex-amount text-green-700 dark:text-green-400">+ {{ rupiah($hpp['net_purchases']) }}</span>
                            </div>
                            @if($hpp['purchase_returns'] > 0)
                            <div class="report-flex-row">
                                <span class="flex-name">Retur Pembelian</span>
                                <span class="flex-amount text-red-600 dark:text-red-400">- {{ rupiah($hpp['purchase_returns']) }}</span>
                            </div>
                            @endif
                            <div class="report-flex-row font-semibold border-t border-dashed border-gray-300 dark:border-gray-600 mt-1 pt-1">
                                <span class="flex-name">Barang Tersedia untuk Dijual</span>
                                <span class="flex-amount">{{ rupiah($hpp['goods_available']) }}</span>
                            </div>
                            <div class="report-flex-row">
                                <span class="flex-name">Persediaan Akhir (FIFO)</span>
                                <span class="flex-amount text-red-600 dark:text-red-400">- {{ rupiah($hpp['closing_inventory']) }}</span>
                            </div>
                            <div class="report-flex-row font-bold border-t border-gray-300 dark:border-gray-600 mt-1 pt-1" style="border-top-width: 2px;">
                                <span class="flex-name">HPP — Perhitungan FIFO</span>
                                <span class="flex-amount">{{ rupiah($hpp['hpp_calculated']) }}</span>
                            </div>
                            @if(abs($hpp['hpp_difference']) > 0.01)
                            <div class="report-flex-row text-xs text-gray-500 dark:text-gray-400 mt-1">
                                <span class="flex-name">HPP dari Jurnal</span>
                                <span class="flex-amount">{{ rupiah($hpp['hpp_from_journal']) }}</span>
                            </div>
                            @endif
                        </div>
                    @endif

                    {{-- Detail COGS per Account --}}
                    @forelse($data['cogs'] as $item)
                        <div class="report-flex-row">
                            <span class="flex-code">{{ $item['account']->code }}</span>
                            <span class="flex-name">{{ $item['account']->name }}</span>
                            <span class="flex-amount">{{ rupiah($item['balance']) }}</span>
                        </div>
                    @empty
                        <div class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada data HPP</div>
                    @endforelse
                    <div class="flex justify-between px-3 py-2 font-bold border-t">
                        <span>Total HPP</span>
                        <span class="font-mono">{{ rupiah($data['total_cogs']) }}</span>
                    </div>
                </div>
                <div class="flex justify-between px-3 py-3 font-bold text-base border-t-2 border-primary-600 dark:border-primary-400 mt-1">
                    <span>LABA KOTOR</span>
                    <span class="font-mono {{ $data['gross_profit'] < 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ rupiah($data['gross_profit']) }}</span>
                </div>
            </div>

            {{-- BEBAN OPERASIONAL --}}
            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">BEBAN OPERASIONAL</h3>
                <div class="border border-t-0 rounded-b divide-y">
                    @forelse($data['expense_operating'] as $item)
                        <div class="report-flex-row">
                            <span class="flex-code">{{ $item['account']->code }}</span>
                            <span class="flex-name">{{ $item['account']->name }}</span>
                            <span class="flex-amount">{{ rupiah($item['balance']) }}</span>
                        </div>
                    @empty
                        <div class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada data</div>
                    @endforelse
                    <div class="flex justify-between px-3 py-2 font-bold border-t">
                        <span>Total Beban Operasional</span>
                        <span class="font-mono">{{ rupiah($data['total_expense_operating']) }}</span>
                    </div>
                </div>
                <div class="flex justify-between px-3 py-3 font-bold text-base border-t-2 border-primary-600 dark:border-primary-400 mt-1">
                    <span>LABA USAHA</span>
                    <span class="font-mono {{ $data['operating_profit'] < 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ rupiah($data['operating_profit']) }}</span>
                </div>
            </div>

            {{-- BEBAN/PENDAPATAN LAIN-LAIN (show only if has data) --}}
            @if(count($data['revenue_other']) > 0 || count($data['expense_other']) > 0)
                <div>
                    <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-gray-400 dark:border-gray-500">BEBAN / PENDAPATAN LAIN-LAIN</h3>
                    <div class="border border-t-0 rounded-b divide-y">
                        @if(count($data['revenue_other']) > 0)
                            <div class="px-3 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50">Pendapatan Lain-lain</div>
                            @foreach($data['revenue_other'] as $item)
                                <div class="report-flex-row">
                                    <span class="flex-code">{{ $item['account']->code }}</span>
                                    <span class="flex-name">{{ $item['account']->name }}</span>
                                    <span class="flex-amount">{{ rupiah($item['balance']) }}</span>
                                </div>
                            @endforeach
                        @endif
                        @if(count($data['expense_other']) > 0)
                            <div class="px-3 py-1 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50">Beban Lain-lain</div>
                            @foreach($data['expense_other'] as $item)
                                <div class="report-flex-row">
                                    <span class="flex-code">{{ $item['account']->code }}</span>
                                    <span class="flex-name">{{ $item['account']->name }}</span>
                                    <span class="flex-amount">{{ rupiah($item['balance']) }}</span>
                                </div>
                            @endforeach
                        @endif
                        <div class="flex justify-between px-3 py-2 font-bold border-t">
                            <span>Total Lain-lain</span>
                            <span class="font-mono {{ $data['other_total'] < 0 ? 'text-red-600 dark:text-red-400' : '' }}">{{ rupiah($data['other_total']) }}</span>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAX EXPENSE (show only if has data) --}}
            @if($data['tax_expense'] > 0)
                <div>
                    <div class="flex justify-between px-3 py-2 text-sm border rounded">
                        <span>Beban Pajak Penghasilan</span>
                        <span class="font-mono text-red-600 dark:text-red-400">{{ rupiah($data['tax_expense']) }}</span>
                    </div>
                </div>
            @endif

            {{-- NET INCOME --}}
            <div class="flex justify-between px-3 py-4 font-bold text-lg border-t-2 border-primary-600 dark:border-primary-400 bg-primary-50 dark:bg-primary-900/30 rounded">
                <span>LABA / RUGI BERSIH</span>
                <span class="font-mono {{ $data['net_income'] < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-700 dark:text-green-400' }}">{{ rupiah($data['net_income']) }}</span>
            </div>
        </div>
    </x-filament::section>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-report', () => {
                window.print();
            });
        });
    </script>
</x-filament-panels::page>
