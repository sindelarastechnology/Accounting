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
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">BUKU KAS</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['date_from'] }} s/d {{ $data['date_to'] }}</p>
        </div>

        <div class="flex gap-6 text-sm mb-4 px-1">
            <span class="font-medium">Saldo Awal:</span>
            <span class="font-mono">{{ rupiah($data['opening_balance']) }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800/50 border-b text-xs uppercase text-gray-500 dark:text-gray-400">
                        <th class="px-3 py-2 text-left w-24">Tanggal</th>
                        <th class="px-3 py-2 text-left w-24">No. Jurnal</th>
                        <th class="px-3 py-2 text-left">Keterangan</th>
                        <th class="px-3 py-2 text-left">Akun Lawan</th>
                        <th class="px-3 py-2 text-left w-28">Wallet</th>
                        <th class="px-3 py-2 text-right w-28">Masuk (Rp)</th>
                        <th class="px-3 py-2 text-right w-28">Keluar (Rp)</th>
                        <th class="px-3 py-2 text-right w-28">Saldo (Rp)</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($data['rows'] as $row)
                    <tr>
                        <td class="px-3 py-1.5 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                        <td class="px-3 py-1.5 text-xs text-gray-400 dark:text-gray-500">{{ $row['journal_number'] }}</td>
                        <td class="px-3 py-1.5">{{ $row['description'] }}</td>
                        <td class="px-3 py-1.5 text-xs text-gray-600 dark:text-gray-400">{{ $row['counter_account'] }}</td>
                        <td class="px-3 py-1.5 text-xs font-medium">{{ $row['wallet_name'] }}</td>
                        <td class="px-3 py-1.5 text-right font-mono">@if($row['masuk'] > 0){{ rupiah($row['masuk'], false) }}@endif</td>
                        <td class="px-3 py-1.5 text-right font-mono text-danger-600 dark:text-red-400">@if($row['keluar'] > 0){{ rupiah($row['keluar'], false) }}@endif</td>
                        <td class="px-3 py-1.5 text-right font-mono">{{ rupiah($row['saldo'], false) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-3 py-4 text-center text-gray-400 dark:text-gray-500 italic">Tidak ada transaksi dalam periode ini</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-800/50 font-bold text-sm">
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600">
                        <td colspan="5" class="px-3 py-2 text-right">TOTAL</td>
                        <td class="px-3 py-2 text-right font-mono">{{ rupiah($data['total_masuk'], false) }}</td>
                        <td class="px-3 py-2 text-right font-mono text-danger-600 dark:text-red-400">{{ rupiah($data['total_keluar'], false) }}</td>
                        <td class="px-3 py-2 text-right font-mono"></td>
                    </tr>
                    <tr>
                        <td colspan="7" class="px-3 py-2 text-right">Saldo Akhir</td>
                        <td class="px-3 py-2 text-right font-mono">{{ rupiah($data['closing_balance'], false) }}</td>
                    </tr>
                </tfoot>
            </table>
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
