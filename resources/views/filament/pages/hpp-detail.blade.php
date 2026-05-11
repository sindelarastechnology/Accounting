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
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">HPP PER PRODUK (FIFO)</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $data['period_name'] ?? 'Pilih periode' }}
            </p>
        </div>

        @if(count($data['products']) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-100 dark:bg-gray-800 border-b border-gray-300 dark:border-gray-600">
                            <th class="px-3 py-2 text-left font-semibold">Produk</th>
                            <th class="px-3 py-2 text-right font-semibold">Stok Awal</th>
                            <th class="px-3 py-2 text-right font-semibold">Pembelian</th>
                            <th class="px-3 py-2 text-right font-semibold">HPP</th>
                            <th class="px-3 py-2 text-right font-semibold">Stok Akhir</th>
                            <th class="px-3 py-2 text-right font-semibold">Nilai Stok Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($data['products'] as $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-3 py-2">
                                    <span class="font-medium">{{ $row['product']->name }}</span>
                                    <span class="text-xs text-gray-400 ml-1">({{ $row['product']->code }})</span>
                                </td>
                                <td class="px-3 py-2 text-right font-mono">{{ rupiah($row['opening_value']) }}</td>
                                <td class="px-3 py-2 text-right font-mono text-green-700 dark:text-green-400">{{ rupiah($row['purchases']) }}</td>
                                <td class="px-3 py-2 text-right font-mono font-semibold text-red-700 dark:text-red-400">{{ rupiah($row['cogs']) }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ number_format($row['closing_qty'], 0) }} {{ $row['product']->unit }}</td>
                                <td class="px-3 py-2 text-right font-mono">{{ rupiah($row['closing_value']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-gray-800/50 border-t-2 border-gray-400 dark:border-gray-500 font-bold">
                            <td class="px-3 py-3 text-right">TOTAL</td>
                            <td class="px-3 py-3 text-right font-mono">-</td>
                            <td class="px-3 py-3 text-right font-mono">-</td>
                            <td class="px-3 py-3 text-right font-mono text-red-700 dark:text-red-400">{{ rupiah($data['total_hpp']) }}</td>
                            <td class="px-3 py-3 text-right font-mono">-</td>
                            <td class="px-3 py-3 text-right font-mono">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="px-3 py-8 text-center text-sm text-gray-400 dark:text-gray-500 italic">
                Tidak ada data produk barang untuk periode ini
            </div>
        @endif
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
