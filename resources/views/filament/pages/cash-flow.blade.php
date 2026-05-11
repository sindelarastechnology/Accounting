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
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">LAPORAN ARUS KAS</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Metode Tidak Langsung</p>
        </div>

        <div class="space-y-4">
            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">AKTIVITAS OPERASI</h3>
                <div class="border border-t-0 rounded-b divide-y">
                    <div class="flex justify-between px-3 py-2 font-semibold text-sm">
                        <span>Laba Bersih</span>
                        <span class="font-mono">{{ rupiah($data['operating']['net_income']) }}</span>
                    </div>
                    <div class="px-3 py-1 font-bold text-sm">Penyesuaian:</div>
                    @foreach($data['operating']['adjustments'] as $adj)
                        <div class="flex justify-between px-3 py-1.5 text-sm pl-6">
                            <span class="text-gray-600 dark:text-gray-400">{{ $adj['label'] }}</span>
                            <span class="font-mono @if($adj['amount'] < 0) text-danger-600 dark:text-red-400 @endif">
                                @if($adj['amount'] < 0)
                                    ({{ rupiah(abs($adj['amount'])) }})
                                @else
                                    {{ rupiah($adj['amount']) }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Kas dari Aktivitas Operasi</span>
                        <span class="font-mono">{{ rupiah($data['operating']['total']) }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">AKTIVITAS INVESTASI</h3>
                <div class="border border-t-0 rounded-b divide-y">
                    @forelse($data['investing']['items'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span>{{ $item['label'] }}</span>
                            <span class="font-mono">{{ rupiah($item['amount']) }}</span>
                        </div>
                    @empty
                        <div class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada aktivitas investasi</div>
                    @endforelse
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Kas dari Aktivitas Investasi</span>
                        <span class="font-mono">{{ rupiah($data['investing']['total']) }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-primary-500">AKTIVITAS PENDANAAN</h3>
                <div class="border border-t-0 rounded-b divide-y">
                    @forelse($data['financing']['items'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span>{{ $item['label'] }}</span>
                            <span class="font-mono @if($item['amount'] < 0) text-danger-600 dark:text-red-400 @endif">
                                @if($item['amount'] < 0)
                                    ({{ rupiah(abs($item['amount'])) }})
                                @else
                                    {{ rupiah($item['amount']) }}
                                @endif
                            </span>
                        </div>
                    @empty
                        <div class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada aktivitas pendanaan</div>
                    @endforelse
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Kas dari Aktivitas Pendanaan</span>
                        <span class="font-mono">{{ rupiah($data['financing']['total']) }}</span>
                    </div>
                </div>
            </div>

            <div class="border-t-2 border-primary-600 dark:border-primary-400 pt-3 space-y-2">
                <div class="flex justify-between px-3 py-2 font-bold text-base">
                    <span>Kenaikan/(Penurunan) Kas Bersih</span>
                    <span class="font-mono">{{ rupiah($data['net_change']) }}</span>
                </div>
                <div class="flex justify-between px-3 py-2 font-bold text-base">
                    <span>Saldo Kas Awal Periode</span>
                    <span class="font-mono">{{ rupiah($data['opening_cash']) }}</span>
                </div>
                <div class="flex justify-between px-3 py-3 font-bold text-lg border-t-2 border-double border-primary-600 dark:border-primary-400">
                    <span>SALDO KAS AKHIR PERIODE</span>
                    <span class="font-mono">{{ rupiah($data['closing_cash']) }}</span>
                </div>

                @if(abs($data['unclassified_amount'] ?? 0) > 0.01)
                <div class="flex justify-between px-4 py-2 bg-warning-50 dark:bg-warning-900/30 border border-warning-200 dark:border-warning-700 rounded mt-2">
                    <span class="text-warning-700 dark:text-warning-400 text-sm font-medium">
                        ⚠ Selisih Tidak Terklasifikasi
                    </span>
                    <span class="font-mono text-warning-700 dark:text-warning-400 text-sm">
                        {{ rupiah($data['unclassified_amount']) }}
                    </span>
                </div>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1 px-4">
                    Ada transaksi kas yang tidak terklasifikasi di aktivitas manapun.
                    Periksa jurnal manual yang melibatkan akun kas/bank.
                </p>
                @endif
            </div>
        </div>
    </x-filament::section>

    {{-- ===== KAS MASUK & KAS KELUAR ===== --}}
    @php $cashInOut = \App\Services\JournalService::getCashInOut($data['date_from'], $data['date_to']); @endphp

    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <span class="text-base font-bold">REKAPITULASI KAS MASUK & KAS KELUAR</span>
        </x-slot>

        <div class="space-y-3">
            <div>
                <h4 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-success-500 dark:border-green-500 text-success-700 dark:text-green-400">KAS MASUK</h4>
                <div class="border border-t-0 rounded-b divide-y">
                    @forelse($cashInOut['cash_in'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ $item['label'] }}</span>
                            <span class="font-mono text-success-600 dark:text-green-400">{{ rupiah($item['amount']) }}</span>
                        </div>
                    @empty
                        <div class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada kas masuk</div>
                    @endforelse
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Total Kas Masuk</span>
                        <span class="font-mono text-success-700 dark:text-green-400">{{ rupiah($cashInOut['cash_in_total']) }}</span>
                    </div>
                </div>
            </div>

            <div>
                <h4 class="text-sm font-bold bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded-t border-l-4 border-danger-500 dark:border-red-500 text-danger-700 dark:text-red-400">KAS KELUAR</h4>
                <div class="border border-t-0 rounded-b divide-y">
                    @forelse($cashInOut['cash_out'] as $item)
                        <div class="flex justify-between px-3 py-1.5 text-sm">
                            <span class="text-gray-600 dark:text-gray-400">{{ $item['label'] }}</span>
                            <span class="font-mono text-danger-600 dark:text-red-400">{{ rupiah($item['amount']) }}</span>
                        </div>
                    @empty
                        <div class="px-3 py-1.5 text-sm text-gray-400 dark:text-gray-500 italic">Tidak ada kas keluar</div>
                    @endforelse
                    <div class="flex justify-between px-3 py-2 font-bold border-t text-sm">
                        <span>Total Kas Keluar</span>
                        <span class="font-mono text-danger-700 dark:text-red-400">{{ rupiah($cashInOut['cash_out_total']) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between px-3 py-2 font-bold text-base border-t-2 border-gray-300 dark:border-gray-600">
                <span>KENAIKAN / (PENURUNAN) KAS BERSIH</span>
                <span class="font-mono @if($cashInOut['net'] < 0) text-danger-600 dark:text-red-400 @else text-success-600 dark:text-green-400 @endif">
                    @if($cashInOut['net'] < 0)
                        ({{ rupiah(abs($cashInOut['net'])) }})
                    @else
                        {{ rupiah($cashInOut['net']) }}
                    @endif
                </span>
            </div>

            @if(abs($cashInOut['net'] - ($data['net_change'] ?? 0)) > 0.01)
                <div class="px-4 py-2 bg-warning-50 dark:bg-warning-900/30 border border-warning-200 dark:border-warning-700 rounded mt-2">
                    <p class="text-warning-700 dark:text-warning-400 text-sm font-medium">
                        ⚠ Selisih: Kas Masuk - Kas Keluar ({{ rupiah($cashInOut['net']) }}) tidak sama dengan Kenaikan Kas Bersih ({{ rupiah($data['net_change'] ?? 0) }}).
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Periksa jurnal manual yang melibatkan akun kas/bank.
                    </p>
                </div>
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
