<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filter Periode --}}
        <div class="filament-forms-field-wrapper">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Untuk Periode
            </label>
            <select
                wire:model.live="periodId"
                wire:change="loadOpeningBalances"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            >
                <option value="">-- Pilih Periode --</option>
                @php
                    $periods = \App\Models\Period::orderBy('start_date', 'asc')->get();
                @endphp
                @foreach($periods as $period)
                    <option value="{{ $period->id }}" {{ $periodId == $period->id ? 'selected' : '' }}>
                        {{ $period->name }} ({{ $period->start_date->format('d/m/Y') }} - {{ $period->end_date->format('d/m/Y') }})
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tabel Input Saldo Awal --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Kode Akun</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Nama Akun</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Kategori</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-500 dark:text-gray-400">Normal Balance</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Saldo Debit</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-500 dark:text-gray-400">Saldo Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accounts as $account)
                        @if($account['is_header'])
                            {{-- Header Row --}}
                            <tr class="bg-gray-100 dark:bg-gray-800">
                                <td class="px-4 py-2 font-bold text-gray-700 dark:text-gray-300">{{ $account['code'] }}</td>
                                <td class="px-4 py-2 font-bold text-gray-700 dark:text-gray-300" colspan="5">{{ $account['name'] }}</td>
                            </tr>
                        @else
                            {{-- Detail Row --}}
                            <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                <td class="px-4 py-2 font-mono text-gray-600 dark:text-gray-400">{{ $account['code'] }}</td>
                                <td class="px-4 py-2 text-gray-700 dark:text-gray-300">{{ $account['name'] }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $account['category_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $account['normal_balance'] === 'debit' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' }}">
                                        {{ $account['normal_balance'] === 'debit' ? 'Debit' : 'Kredit' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        wire:model.live="balanceInputs.{{ $account['id'] }}.debit"
                                        class="w-full text-right rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        @if($balanceInputs[$account['id']]['credit'] > 0) disabled @endif
                                    />
                                </td>
                                <td class="px-4 py-2">
                                    <input
                                        type="number"
                                        step="0.0001"
                                        min="0"
                                        wire:model.live="balanceInputs.{{ $account['id'] }}.credit"
                                        class="w-full text-right rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        @if($balanceInputs[$account['id']]['debit'] > 0) disabled @endif
                                    />
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
                {{-- Footer Totals --}}
                <tfoot>
                    <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800">
                        <td class="px-4 py-3 font-bold text-gray-700 dark:text-gray-300" colspan="4">Total</td>
                        <td class="px-4 py-3 text-right font-bold
                            {{ abs($totalDebit - $totalCredit) > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            Rp {{ number_format($totalDebit, 0, ',', '.') }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold
                            {{ abs($totalDebit - $totalCredit) > 0.01 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            Rp {{ number_format($totalCredit, 0, ',', '.') }}
                        </td>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-gray-800">
                        <td colspan="6" class="px-4 py-2">
                            <span class="inline-flex items-center gap-2 text-sm font-medium
                                {{ abs($totalDebit - $totalCredit) <= 0.01 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                <span class="w-3 h-3 rounded-full
                                    {{ abs($totalDebit - $totalCredit) <= 0.01 ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                {{ abs($totalDebit - $totalCredit) <= 0.01 ? 'BALANCE' : 'TIDAK BALANCE — Selisih: Rp ' . number_format(abs($totalDebit - $totalCredit), 0, ',', '.') }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</x-filament-panels::page>
