<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @php
            $data = $this->getData();
        @endphp

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-bold mb-4">Rekap Pemotongan PPh 23</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-gray-50 dark:bg-gray-700">
                            <th class="text-left p-2">No</th>
                            <th class="text-left p-2">Tanggal</th>
                            <th class="text-left p-2">No. Ref</th>
                            <th class="text-left p-2">Supplier</th>
                            <th class="text-left p-2">NPWP</th>
                            <th class="text-left p-2">Keterangan</th>
                            <th class="text-right p-2">DPP (Rp)</th>
                            <th class="text-center p-2">Tarif (%)</th>
                            <th class="text-right p-2">PPh 23 (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data['items'] as $index => $item)
                            <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="p-2">{{ $index + 1 }}</td>
                                <td class="p-2">{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                                <td class="p-2">{{ $item['number'] }}</td>
                                <td class="p-2">{{ $item['contact'] }}</td>
                                <td class="p-2">{{ $item['npwp'] }}</td>
                                <td class="p-2">{{ $item['description'] }}</td>
                                <td class="p-2 text-right">{{ rupiah($item['base_amount']) }}</td>
                                <td class="p-2 text-center">{{ $item['tax_rate'] }}%</td>
                                <td class="p-2 text-right font-semibold text-red-600 dark:text-red-400">{{ rupiah($item['tax_amount']) }}</td>
                            </tr>
                        @endforeach
                        @if(count($data['items']) === 0)
                            <tr>
                                <td colspan="9" class="text-center p-4 text-gray-500 dark:text-gray-400">Tidak ada data pemotongan PPh 23</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr class="font-bold bg-gray-100 dark:bg-gray-700">
                            <td colspan="8" class="p-2 text-right">Total PPh 23 Dipotong</td>
                            <td class="p-2 text-right text-red-600 dark:text-red-400">{{ rupiah($data['total']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-red-50 dark:bg-red-900 rounded-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total PPh 23 Dipotong</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ rupiah($data['total']) }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Wajib setor ke Kas Negara</div>
                </div>
                <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Jumlah Transaksi</div>
                    <div class="text-2xl font-bold text-blue-600">{{ count($data['items']) }} Transaksi</div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-2">
            <x-filament::button wire:click="exportPdf" icon="heroicon-o-document" color="danger">
                Export PDF
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
