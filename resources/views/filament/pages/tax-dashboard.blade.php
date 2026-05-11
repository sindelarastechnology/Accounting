<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @php $data = $this->getData(); @endphp

        @if(!empty($data) && isset($data['period']))
            {{-- Tax Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-xl">
                    <div class="text-sm text-gray-600 dark:text-gray-400">PPN</div>
                    <div class="text-2xl font-bold text-blue-600">{{ rupiah($data['ppn']['total_ppn_keluaran'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">Keluaran — Masukan: {{ rupiah($data['ppn']['total_ppn_masukan'] ?? 0) }}</div>
                    @if(($data['ppn']['ppn_kurang_bayar'] ?? 0) > 0)
                        <div class="text-xs text-red-600 mt-1">KB: {{ rupiah($data['ppn']['ppn_kurang_bayar']) }}</div>
                    @elseif(($data['ppn']['ppn_lebih_bayar'] ?? 0) > 0)
                        <div class="text-xs text-purple-600 mt-1">LB: {{ rupiah($data['ppn']['ppn_lebih_bayar']) }}</div>
                    @endif
                </div>
                <div class="p-4 bg-green-50 dark:bg-green-900 rounded-xl">
                    <div class="text-sm text-gray-600 dark:text-gray-400">PPh 23</div>
                    <div class="text-2xl font-bold text-green-600">{{ rupiah($data['pph23']['total'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">{{ count($data['pph23']['items'] ?? []) }} transaksi</div>
                </div>
                <div class="p-4 bg-yellow-50 dark:bg-yellow-900 rounded-xl">
                    <div class="text-sm text-gray-600 dark:text-gray-400">PPh 21</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ rupiah($data['pph21']['total'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">{{ count($data['pph21']['items'] ?? []) }} transaksi</div>
                </div>
                <div class="p-4 bg-red-50 dark:bg-red-900 rounded-xl">
                    <div class="text-sm text-gray-600 dark:text-gray-400">PPh 4(2)</div>
                    <div class="text-2xl font-bold text-red-600">{{ rupiah($data['pph4a2']['total'] ?? 0) }}</div>
                    <div class="text-xs text-gray-500">{{ count($data['pph4a2']['items'] ?? []) }} transaksi</div>
                </div>
            </div>

            {{-- Tax Payments Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="px-4 py-3 border-b dark:border-gray-700">
                    <h3 class="font-bold">Setoran Pajak — {{ $data['period']->name }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b">
                                <th class="p-2 text-left">Tanggal</th>
                                <th class="p-2 text-left">Jenis Pajak</th>
                                <th class="p-2 text-left">No. Dokumen</th>
                                <th class="p-2 text-left">Referensi (SSP/NTPN)</th>
                                <th class="p-2 text-right">Jumlah</th>
                                <th class="p-2 text-left">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data['all_payments'] as $payment)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="p-2">{{ $payment->payment_date->format('d/m/Y') }}</td>
                                    <td class="p-2">
                                        <span class="px-2 py-1 rounded text-xs font-medium
                                            {{ $payment->tax_type === 'ppn' ? 'bg-blue-100 text-blue-700' : '' }}
                                            {{ $payment->tax_type === 'pph23' ? 'bg-green-100 text-green-700' : '' }}
                                            {{ $payment->tax_type === 'pph21' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                            {{ $payment->tax_type === 'pph4a2' ? 'bg-red-100 text-red-700' : '' }}">
                                            {{ \App\Filament\Pages\TaxDashboardPage::taxTypeLabel($payment->tax_type) }}
                                        </span>
                                    </td>
                                    <td class="p-2">{{ $payment->document_number ?? '-' }}</td>
                                    <td class="p-2">{{ $payment->reference ?? '-' }}</td>
                                    <td class="p-2 text-right font-semibold">{{ rupiah($payment->amount) }}</td>
                                    <td class="p-2 max-w-[200px] truncate">{{ $payment->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center p-4 text-gray-500">Belum ada setoran pajak untuk periode ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-100 dark:bg-gray-700">
                                <td colspan="4" class="p-2 text-right">Total Setoran Pajak</td>
                                <td class="p-2 text-right">{{ rupiah($data['all_payments']->sum('amount')) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- PPN Detail --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="px-4 py-3 border-b dark:border-gray-700">
                    <h3 class="font-bold">Rincian PPN</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                    <div>
                        <h4 class="font-semibold text-green-600 mb-2">PPN Masukan (Pembelian)</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-1">Ref</th>
                                    <th class="text-left p-1">Supplier</th>
                                    <th class="text-right p-1">DPP</th>
                                    <th class="text-right p-1">PPN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['ppn']['ppn_masukan'] ?? [] as $item)
                                    <tr class="border-b">
                                        <td class="p-1">{{ $item['number'] }}</td>
                                        <td class="p-1">{{ $item['contact'] }}</td>
                                        <td class="p-1 text-right">{{ rupiah($item['base_amount']) }}</td>
                                        <td class="p-1 text-right">{{ rupiah($item['tax_amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td colspan="3" class="p-1 text-right">Total</td>
                                    <td class="p-1 text-right text-green-600">{{ rupiah($data['ppn']['total_ppn_masukan'] ?? 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div>
                        <h4 class="font-semibold text-blue-600 mb-2">PPN Keluaran (Penjualan)</h4>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-1">Ref</th>
                                    <th class="text-left p-1">Customer</th>
                                    <th class="text-right p-1">DPP</th>
                                    <th class="text-right p-1">PPN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['ppn']['ppn_keluaran'] ?? [] as $item)
                                    <tr class="border-b">
                                        <td class="p-1">{{ $item['number'] }}</td>
                                        <td class="p-1">{{ $item['contact'] }}</td>
                                        <td class="p-1 text-right">{{ rupiah($item['base_amount']) }}</td>
                                        <td class="p-1 text-right">{{ rupiah($item['tax_amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td colspan="3" class="p-1 text-right">Total</td>
                                    <td class="p-1 text-right text-blue-600">{{ rupiah($data['ppn']['total_ppn_keluaran'] ?? 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- PPh Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                    <div class="px-4 py-3 border-b dark:border-gray-700">
                        <h3 class="font-bold">PPh 23</h3>
                    </div>
                    <div class="p-4">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-1">Supplier</th>
                                    <th class="text-right p-1">DPP</th>
                                    <th class="text-right p-1">PPh</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data['pph23']['items'] ?? [] as $item)
                                    <tr class="border-b">
                                        <td class="p-1">{{ $item['contact'] }}</td>
                                        <td class="p-1 text-right">{{ rupiah($item['base_amount']) }}</td>
                                        <td class="p-1 text-right">{{ rupiah($item['tax_amount']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="font-bold">
                                    <td class="p-1 text-right">Total</td>
                                    <td></td>
                                    <td class="p-1 text-right text-green-600">{{ rupiah($data['pph23']['total'] ?? 0) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="space-y-6">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                        <div class="px-4 py-3 border-b dark:border-gray-700">
                            <h3 class="font-bold">PPh 21</h3>
                        </div>
                        <div class="p-4">
                            @if(count($data['pph21']['items'] ?? []) > 0)
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-left p-1">Tanggal</th>
                                            <th class="text-right p-1">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['pph21']['items'] as $item)
                                            <tr class="border-b">
                                                <td class="p-1">{{ \Carbon\Carbon::parse($item['payment_date'])->format('d/m/Y') }}</td>
                                                <td class="p-1 text-right">{{ rupiah($item['amount']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-bold">
                                            <td class="p-1 text-right">Total</td>
                                            <td class="p-1 text-right text-yellow-600">{{ rupiah($data['pph21']['total']) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @else
                                <div class="text-center text-gray-500 py-4">Belum ada data PPh 21</div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                        <div class="px-4 py-3 border-b dark:border-gray-700">
                            <h3 class="font-bold">PPh 4(2)</h3>
                        </div>
                        <div class="p-4">
                            @if(count($data['pph4a2']['items'] ?? []) > 0)
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-left p-1">Tanggal</th>
                                            <th class="text-right p-1">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data['pph4a2']['items'] as $item)
                                            <tr class="border-b">
                                                <td class="p-1">{{ \Carbon\Carbon::parse($item['payment_date'])->format('d/m/Y') }}</td>
                                                <td class="p-1 text-right">{{ rupiah($item['amount']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-bold">
                                            <td class="p-1 text-right">Total</td>
                                            <td class="p-1 text-right text-red-600">{{ rupiah($data['pph4a2']['total']) }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            @else
                                <div class="text-center text-gray-500 py-4">Belum ada data PPh 4(2)</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-8 text-center text-gray-500">
                Pilih periode untuk melihat dashboard pajak.
            </div>
        @endif
    </div>
</x-filament-panels::page>
