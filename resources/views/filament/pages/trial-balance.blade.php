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

        <div class="overflow-x-auto">
            <table class="report-table">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-800">
                        <th class="col-code" style="text-align:left;font-weight:600;border-bottom:1px solid #374151;">Kode Akun</th>
                        <th class="col-name" style="font-weight:600;border-bottom:1px solid #374151;">Nama Akun</th>
                        <th class="col-amount" style="font-weight:600;border-bottom:1px solid #374151;">Debit (Rp)</th>
                        <th class="col-amount" style="font-weight:600;border-bottom:1px solid #374151;">Kredit (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->getData()['rows'] as $groupId => $group)
                        @if($group['header'])
                            <tr class="bg-gray-50 dark:bg-gray-800/50 font-bold">
                                <td class="col-code">{{ $group['header']->code }}</td>
                                <td class="col-name" colspan="3">{{ $group['header']->name }}</td>
                            </tr>
                        @endif
                        @foreach($group['items'] as $item)
                            <tr class="border-b border-gray-100 dark:border-gray-700">
                                <td class="col-code">{{ $item['account']->code }}</td>
                                <td class="col-name">{{ $item['account']->name }}</td>
                                <td class="col-amount">{{ rupiah($item['debit']) }}</td>
                                <td class="col-amount">{{ rupiah($item['credit']) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="font-bold border-t-2 border-primary-600 dark:border-primary-400">
                        <td class="col-name" colspan="2">TOTAL</td>
                        <td class="col-amount">{{ rupiah($this->getData()['total_debit']) }}</td>
                        <td class="col-amount">{{ rupiah($this->getData()['total_credit']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 text-center">
            @if($this->getData()['is_balanced'])
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400">
                    ✓ Balance
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400">
                    ✗ Tidak Balance — Selisih: {{ rupiah($this->getData()['difference']) }}
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
