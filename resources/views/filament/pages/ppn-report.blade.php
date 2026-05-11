<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @php
            $data = $this->getData();
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <h3 class="text-lg font-bold mb-4 text-green-600">PPN Masukan (Pembelian)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Tanggal</th>
                                <th class="text-left p-2">No. Ref</th>
                                <th class="text-left p-2">Supplier</th>
                                <th class="text-right p-2">DPP</th>
                                <th class="text-right p-2">PPN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['ppn_masukan'] as $item)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="p-2">{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                                    <td class="p-2">{{ $item['number'] }}</td>
                                    <td class="p-2">{{ $item['contact'] }}</td>
                                    <td class="p-2 text-right">{{ rupiah($item['base_amount']) }}</td>
                                    <td class="p-2 text-right font-semibold">{{ rupiah($item['tax_amount']) }}</td>
                                </tr>
                            @endforeach
                            @if(count($data['ppn_masukan']) === 0)
                                <tr>
                                    <td colspan="5" class="text-center p-4 text-gray-500 dark:text-gray-400">Tidak ada data PPN Masukan</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-100 dark:bg-gray-700">
                                <td colspan="4" class="p-2 text-right">Total PPN Masukan</td>
                                <td class="p-2 text-right text-green-600">{{ rupiah($data['total_ppn_masukan']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                <h3 class="text-lg font-bold mb-4 text-blue-600">PPN Keluaran (Penjualan)</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left p-2">Tanggal</th>
                                <th class="text-left p-2">No. Ref</th>
                                <th class="text-left p-2">Customer</th>
                                <th class="text-right p-2">DPP</th>
                                <th class="text-right p-2">PPN</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data['ppn_keluaran'] as $item)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="p-2">{{ \Carbon\Carbon::parse($item['date'])->format('d/m/Y') }}</td>
                                    <td class="p-2">{{ $item['number'] }}</td>
                                    <td class="p-2">{{ $item['contact'] }}</td>
                                    <td class="p-2 text-right">{{ rupiah($item['base_amount']) }}</td>
                                    <td class="p-2 text-right font-semibold">{{ rupiah($item['tax_amount']) }}</td>
                                </tr>
                            @endforeach
                            @if(count($data['ppn_keluaran']) === 0)
                                <tr>
                                    <td colspan="5" class="text-center p-4 text-gray-500 dark:text-gray-400">Tidak ada data PPN Keluaran</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-100 dark:bg-gray-700">
                                <td colspan="4" class="p-2 text-right">Total PPN Keluaran</td>
                                <td class="p-2 text-right text-blue-600">{{ rupiah($data['total_ppn_keluaran']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h3 class="text-lg font-bold mb-4">Rekapitulasi PPN</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-green-50 dark:bg-green-900 rounded-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total PPN Masukan</div>
                    <div class="text-2xl font-bold text-green-600">{{ rupiah($data['total_ppn_masukan']) }}</div>
                </div>
                <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total PPN Keluaran</div>
                    <div class="text-2xl font-bold text-blue-600">{{ rupiah($data['total_ppn_keluaran']) }}</div>
                </div>
                <div class="p-4 {{ $data['ppn_kurang_bayar'] > 0 ? 'bg-red-50 dark:bg-red-900' : 'bg-purple-50 dark:bg-purple-900' }} rounded-lg">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $data['ppn_kurang_bayar'] > 0 ? 'PPN Kurang Bayar' : 'PPN Lebih Bayar' }}
                    </div>
                    <div class="text-2xl font-bold {{ $data['ppn_kurang_bayar'] > 0 ? 'text-red-600' : 'text-purple-600' }}">
                        {{ rupiah(max($data['ppn_kurang_bayar'], $data['ppn_lebih_bayar'])) }}
                    </div>
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
