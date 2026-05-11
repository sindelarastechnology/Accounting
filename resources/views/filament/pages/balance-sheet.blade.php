<x-filament-panels::page>
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

        @php $data = $this->getData(); @endphp

        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">NERACA</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Per Tanggal: {{ \Carbon\Carbon::parse($data['as_of_date'])->format('d/m/Y') }}</p>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">ASET</h3>
                <div class="border border-t-0 rounded-b divide-y">
                    <div class="px-3 py-1.5 font-semibold text-sm bg-gray-50 dark:bg-gray-800/50">Aset Lancar</div>
                    @foreach($data['assets']['current'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span>{{ $item['account']->name }}</span>
                            <span class="font-mono">{{ rupiah($item['balance']) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Total Aset Lancar</span>
                        <span class="font-mono">{{ rupiah($data['assets']['total_current']) }}</span>
                    </div>

                    <div class="px-3 py-1.5 font-semibold text-sm bg-gray-50 dark:bg-gray-800/50">Aset Tetap</div>
                    @foreach($data['assets']['fixed'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span>{{ $item['account']->name }}</span>
                            <span class="font-mono">{{ rupiah($item['balance']) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Total Aset Tetap</span>
                        <span class="font-mono">{{ rupiah($data['assets']['total_fixed']) }}</span>
                    </div>

                    <div class="flex justify-between px-3 py-3 font-bold text-base border-t-2 border-primary-600 dark:border-primary-400">
                        <span>TOTAL ASET</span>
                        <span class="font-mono">{{ rupiah($data['assets']['total']) }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">LIABILITAS & EKUITAS</h3>
                <div class="border border-t-0 rounded-b divide-y">
                    <div class="px-3 py-1.5 font-semibold text-sm bg-gray-50 dark:bg-gray-800/50">Liabilitas Jangka Pendek</div>
                    @foreach($data['liabilities']['current'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span>{{ $item['account']->name }}</span>
                            <span class="font-mono">{{ rupiah($item['balance']) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between px-3 py-1.5 font-bold text-sm border-t">
                        <span>Total Liabilitas Jangka Pendek</span>
                        <span class="font-mono">{{ rupiah($data['liabilities']['total_current']) }}</span>
                    </div>

                    @if(count($data['liabilities']['long']) > 0)
                        <div class="px-3 py-1.5 font-semibold text-sm bg-gray-50 dark:bg-gray-800/50">Liabilitas Jangka Panjang</div>
                        @foreach($data['liabilities']['long'] as $item)
                            <div class="flex justify-between px-3 py-1.5 text-sm">
                                <span>{{ $item['account']->name }}</span>
                                <span class="font-mono">{{ rupiah($item['balance']) }}</span>
                            </div>
                        @endforeach
                        <div class="flex justify-between px-3 py-1.5 font-bold text-sm border-t">
                            <span>Total Liabilitas Jangka Panjang</span>
                            <span class="font-mono">{{ rupiah($data['liabilities']['total_long']) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Total Liabilitas</span>
                        <span class="font-mono">{{ rupiah($data['liabilities']['total']) }}</span>
                    </div>

                    <div class="px-3 py-1.5 font-semibold text-sm bg-gray-50 dark:bg-gray-800/50">Ekuitas</div>
                    @foreach($data['equity']['accounts'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span>{{ $item['account']->name }}</span>
                            <span class="font-mono">{{ rupiah($item['balance']) }}</span>
                        </div>
                    @endforeach
                    <div class="flex justify-between px-3 py-1.5 text-sm">
                        <span>Laba Periode Ini</span>
                        <span class="font-mono">{{ rupiah($data['equity']['net_income']) }}</span>
                    </div>
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Total Ekuitas</span>
                        <span class="font-mono">{{ rupiah($data['equity']['total']) }}</span>
                    </div>

                    <div class="flex justify-between px-3 py-3 font-bold text-base border-t-2 border-primary-600 dark:border-primary-400">
                        <span>TOTAL LIAB + EKUITAS</span>
                        <span class="font-mono">{{ rupiah($data['liabilities']['total'] + $data['equity']['total']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4 text-center">
            @if($data['is_balanced'])
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                    ✓ Neraca Balance
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                    ✗ Tidak Balance — Selisih: {{ rupiah(abs($data['difference'])) }}
                </span>
            @endif
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
