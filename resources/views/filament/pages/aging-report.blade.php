<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="headerForm">
            {{ $this->form }}
        </x-slot>

        @php $data = $this->getData(); @endphp

        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">AGING {{ $data['type'] === 'receivable' ? 'PIUTANG' : 'HUTANG' }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Per Tanggal: {{ \Carbon\Carbon::parse($data['as_of_date'])->format('d/m/Y') }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-800">
                        <th class="px-3 py-2 text-left font-semibold">Kontak</th>
                        <th class="px-3 py-2 text-right font-semibold">Belum JT</th>
                        <th class="px-3 py-2 text-right font-semibold">1-30 Hari</th>
                        <th class="px-3 py-2 text-right font-semibold">31-60 Hari</th>
                        <th class="px-3 py-2 text-right font-semibold">61-90 Hari</th>
                        <th class="px-3 py-2 text-right font-semibold">>90 Hari</th>
                        <th class="px-3 py-2 text-right font-semibold">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['items'] as $item)
                        @php
                            $rowClass = '';
                            if ($item['over_90'] > 0) $rowClass = 'bg-red-50 dark:bg-red-900/30';
                            elseif ($item['days_61_90'] > 0) $rowClass = 'bg-yellow-50 dark:bg-yellow-900/30';
                        @endphp
                        <tr class="border-b border-gray-100 dark:border-gray-700 {{ $rowClass }}">
                            <td class="px-3 py-2 font-medium">{{ $item['contact']->name }}</td>
                            <td class="px-3 py-2 text-right">{{ rupiah($item['current']) }}</td>
                            <td class="px-3 py-2 text-right">{{ rupiah($item['days_1_30']) }}</td>
                            <td class="px-3 py-2 text-right">{{ rupiah($item['days_31_60']) }}</td>
                            <td class="px-3 py-2 text-right">{{ rupiah($item['days_61_90']) }}</td>
                            <td class="px-3 py-2 text-right">{{ rupiah($item['over_90']) }}</td>
                            <td class="px-3 py-2 text-right font-bold">{{ rupiah($item['total']) }}</td>
                        </tr>
                        @if(!empty($item['details']))
                            <tr class="bg-gray-50 dark:bg-gray-800/50">
                                <td colspan="7" class="px-3 py-2">
                                    <details class="text-xs">
                                        <summary class="cursor-pointer text-primary-600 dark:text-primary-400 font-medium">Detail ({{ count($item['details']) }} dokumen)</summary>
                                        <table class="w-full mt-2 border border-gray-200 dark:border-gray-700 rounded">
                                            <thead class="bg-gray-100 dark:bg-gray-800">
                                                <tr>
                                                    <th class="px-2 py-1 text-left">No. Dokumen</th>
                                                    <th class="px-2 py-1 text-left">Jatuh Tempo</th>
                                                    <th class="px-2 py-1 text-right">Sisa</th>
                                                    <th class="px-2 py-1 text-right">Overdue (hari)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($item['details'] as $detail)
                                                    <tr class="border-t border-gray-100 dark:border-gray-700">
                                                        <td class="px-2 py-1">{{ $detail['number'] }}</td>
                                                        <td class="px-2 py-1">{{ \Carbon\Carbon::parse($detail['due_date'])->format('d/m/Y') }}</td>
                                                        <td class="px-2 py-1 text-right">{{ rupiah($detail['amount']) }}</td>
                                                        <td class="px-2 py-1 text-right">{{ $detail['days_overdue'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </details>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold border-t-2 border-primary-600 dark:border-primary-400">
                        <td class="px-3 py-2">TOTAL</td>
                        <td class="px-3 py-2 text-right">{{ rupiah($data['totals']['current']) }}</td>
                        <td class="px-3 py-2 text-right">{{ rupiah($data['totals']['days_1_30']) }}</td>
                        <td class="px-3 py-2 text-right">{{ rupiah($data['totals']['days_31_60']) }}</td>
                        <td class="px-3 py-2 text-right">{{ rupiah($data['totals']['days_61_90']) }}</td>
                        <td class="px-3 py-2 text-right">{{ rupiah($data['totals']['over_90']) }}</td>
                        <td class="px-3 py-2 text-right">{{ rupiah($data['grand_total']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-filament::section>

    <div class="flex gap-2 mt-4">
        <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-arrow-down-tray">
            Export Excel
        </x-filament::button>
        <x-filament::button wire:click="exportPdf" color="secondary" icon="heroicon-o-printer">
            Cetak PDF
        </x-filament::button>
    </div>
</x-filament-panels::page>
