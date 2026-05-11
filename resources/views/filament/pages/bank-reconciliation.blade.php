<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @php $data = $this->getData(); @endphp

        @if(!empty($data) && isset($data['wallet']))
            {{-- Summary Cards --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Saldo Awal Sistem</div>
                    <div class="text-xl font-bold">{{ rupiah($data['opening_balance']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Mutasi Debit</div>
                    <div class="text-xl font-bold text-green-600">{{ rupiah($data['total_debit']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Mutasi Kredit</div>
                    <div class="text-xl font-bold text-red-600">{{ rupiah($data['total_credit']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                    <div class="text-sm text-gray-500">Saldo Akhir Sistem</div>
                    <div class="text-xl font-bold">{{ rupiah($data['ending_balance']) }}</div>
                </div>
            </div>

            {{-- Comparison --}}
            @if($data['statement_balance'] !== null)
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900 rounded-xl">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Saldo Bank</div>
                        <div class="text-2xl font-bold text-blue-600">{{ rupiah($data['statement_balance']) }}</div>
                    </div>
                    <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Saldo Sistem</div>
                        <div class="text-2xl font-bold">{{ rupiah($data['ending_balance']) }}</div>
                    </div>
                    <div class="p-4 {{ $data['is_balanced'] ? 'bg-green-50 dark:bg-green-900' : 'bg-red-50 dark:bg-red-900' }} rounded-xl">
                        <div class="text-sm text-gray-600 dark:text-gray-400">Selisih</div>
                        <div class="text-2xl font-bold {{ $data['is_balanced'] ? 'text-green-600' : 'text-red-600' }}">
                            {{ $data['difference'] !== null ? rupiah($data['difference']) : '-' }}
                        </div>
                        @if($data['is_balanced'])
                            <div class="text-xs text-green-600 mt-1">✓ Balance</div>
                        @elseif($data['difference'] !== null)
                            <div class="text-xs text-red-600 mt-1">✗ Ada selisih</div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Progress --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-sm font-semibold">Progress Rekonsiliasi</span>
                    <span class="text-sm">{{ $data['reconciled_count'] }} / {{ $data['total_count'] }} transaksi</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                    <div class="bg-primary-600 h-2.5 rounded-full transition-all"
                         style="width: {{ $data['total_count'] > 0 ? ($data['reconciled_count'] / $data['total_count']) * 100 : 0 }}%">
                    </div>
                </div>
            </div>

            {{-- Transactions Table --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                <div class="p-4 border-b dark:border-gray-700">
                    <h3 class="font-bold">Transaksi {{ $data['wallet']->name }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700 border-b">
                                <th class="p-2 text-left w-12">Cocok</th>
                                <th class="p-2 text-left">Tanggal</th>
                                <th class="p-2 text-left">No. Ref</th>
                                <th class="p-2 text-left">Akun</th>
                                <th class="p-2 text-left">Deskripsi</th>
                                <th class="p-2 text-right">Debit</th>
                                <th class="p-2 text-right">Kredit</th>
                                <th class="p-2 text-right">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="font-semibold bg-gray-50 dark:bg-gray-700">
                                <td colspan="5" class="p-2 text-right">Saldo Awal</td>
                                <td colspan="3" class="p-2 text-right">{{ rupiah($data['opening_balance']) }}</td>
                            </tr>
                            @foreach($data['lines'] as $line)
                                <tr class="border-b hover:bg-gray-50 dark:hover:bg-gray-700 {{ ($line['reconciled_at'] ?? false) ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                    <td class="p-2 text-center">
                                        <input type="checkbox"
                                               value="{{ $line['id'] }}"
                                               wire:model="reconciled_ids"
                                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 shadow-sm focus:ring-primary-500">
                                    </td>
                                    <td class="p-2">{{ \Carbon\Carbon::parse($line['journal']['date'])->format('d/m/Y') }}</td>
                                    <td class="p-2">{{ $line['journal']['document_number'] ?? $line['journal_id'] }}</td>
                                    <td class="p-2">{{ $line['account']['code'] ?? '' }} {{ $line['account']['name'] ?? '' }}</td>
                                    <td class="p-2 max-w-[200px] truncate">{{ $line['description'] ?? $line['journal']['description'] }}</td>
                                    <td class="p-2 text-right {{ $line['debit_amount'] > 0 ? 'font-semibold' : '' }}">
                                        {{ $line['debit_amount'] > 0 ? rupiah($line['debit_amount']) : '-' }}
                                    </td>
                                    <td class="p-2 text-right {{ $line['credit_amount'] > 0 ? 'font-semibold' : '' }}">
                                        {{ $line['credit_amount'] > 0 ? rupiah($line['credit_amount']) : '-' }}
                                    </td>
                                    <td class="p-2 text-right font-mono">{{ rupiah($line['running_balance']) }}</td>
                                </tr>
                            @endforeach
                            @if(count($data['lines']) === 0)
                                <tr>
                                    <td colspan="8" class="text-center p-4 text-gray-500">Tidak ada transaksi untuk periode ini</td>
                                </tr>
                            @endif
                        </tbody>
                        <tfoot>
                            <tr class="font-bold bg-gray-100 dark:bg-gray-700">
                                <td colspan="5" class="p-2 text-right">Saldo Akhir</td>
                                <td class="p-2 text-right text-green-600">{{ rupiah($data['total_debit']) }}</td>
                                <td class="p-2 text-right text-red-600">{{ rupiah($data['total_credit']) }}</td>
                                <td class="p-2 text-right">{{ rupiah($data['ending_balance']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-filament::button wire:click="saveReconciliation" icon="heroicon-o-check" color="success">
                    Simpan Rekonsiliasi
                </x-filament::button>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-8 text-center text-gray-500">
                Pilih periode dan dompet/bank untuk memulai rekonsiliasi.
            </div>
        @endif
    </div>
</x-filament-panels::page>
