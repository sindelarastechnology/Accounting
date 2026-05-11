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
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">MUTASI KAS & BANK</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['date_from'] }} s/d {{ $data['date_to'] }}</p>
        </div>

        @forelse($data['wallets'] as $walletData)
            @php $w = $walletData['wallet']; @endphp
            <div class="mb-6 border rounded-lg overflow-hidden">
                <div class="bg-gray-100 dark:bg-gray-800 px-4 py-3 font-bold text-sm flex items-center gap-2">
                    <x-filament::icon name="heroicon-o-banknotes" class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    {{ $w->name }}
                    <span class="text-xs text-gray-400 dark:text-gray-500 font-normal">
                        ({{ $w->type }})
                    </span>
                </div>

                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border-b text-sm flex gap-6">
                    <span class="font-medium">Saldo Awal Periode:</span>
                    <span class="font-mono">{{ rupiah($walletData['opening_balance']) }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b text-xs uppercase text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-2 text-left w-24">Tanggal</th>
                                <th class="px-3 py-2 text-left">Keterangan</th>
                                <th class="px-3 py-2 text-right w-28">Masuk (Rp)</th>
                                <th class="px-3 py-2 text-right w-28">Keluar (Rp)</th>
                                <th class="px-3 py-2 text-right w-28">Saldo (Rp)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @if($walletData['opening_balance'] != 0)
                            <tr class="text-xs text-gray-400 dark:text-gray-500">
                                <td class="px-3 py-1.5">{{ $data['date_from'] }}</td>
                                <td class="px-3 py-1.5 italic">Saldo Awal</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ rupiah($walletData['opening_balance'], false) }}</td>
                                <td class="px-3 py-1.5 text-right font-mono"></td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ rupiah($walletData['opening_balance'], false) }}</td>
                            </tr>
                            @endif
                            @forelse($walletData['rows'] as $row)
                            <tr>
                                <td class="px-3 py-1.5 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                                <td class="px-3 py-1.5">
                                    <span class="text-xs text-gray-400 dark:text-gray-500">[{{ $row['journal_number'] }}]</span>
                                    {{ $row['description'] }}
                                </td>
                                <td class="px-3 py-1.5 text-right font-mono">@if($row['masuk'] > 0){{ rupiah($row['masuk'], false) }}@endif</td>
                                <td class="px-3 py-1.5 text-right font-mono text-danger-600 dark:text-red-400">@if($row['keluar'] > 0){{ rupiah($row['keluar'], false) }}@endif</td>
                                <td class="px-3 py-1.5 text-right font-mono">{{ rupiah($row['saldo'], false) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-3 py-4 text-center text-gray-400 dark:text-gray-500 italic">Tidak ada transaksi dalam periode ini</td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-800/50 font-bold text-sm">
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                                <td colspan="2" class="px-3 py-2 text-right">TOTAL</td>
                                <td class="px-3 py-2 text-right font-mono">{{ rupiah($walletData['total_masuk'], false) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-danger-600 dark:text-red-400">{{ rupiah($walletData['total_keluar'], false) }}</td>
                                <td class="px-3 py-2 text-right font-mono"></td>
                            </tr>
                            <tr>
                                <td colspan="4" class="px-3 py-2 text-right">Saldo Akhir</td>
                                <td class="px-3 py-2 text-right font-mono">{{ rupiah($walletData['closing_balance'], false) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @empty
            <div class="text-center py-8 text-gray-400 dark:text-gray-500 italic">Tidak ada wallet aktif</div>
        @endforelse
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
