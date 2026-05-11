<x-filament-panels::page>

    <style>
        @media print {
            body * { visibility: hidden; }
            .report-content, .report-content * { visibility: visible; }
            .report-content { position: absolute; left: 0; top: 0; width: 100%; }
            .filament-sidebar, .filament-topbar, .report-actions, .filament-main-topbar { display: none !important; }
            .no-print { display: none !important; }
            @page { margin: 10mm; }
        }
    </style>

    <x-filament::section>
        <x-slot name="headerForm">
            {{ $this->form }}
        </x-slot>

        <div class="report-content">
            {{-- Tabs --}}
            <div class="border-b border-gray-200 dark:border-gray-700 mb-6 no-print">
                <nav class="flex gap-1 -mb-px overflow-x-auto" role="tablist">
                    <button wire:click="setActiveTab('general')" role="tab"
                        class="whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeTab === 'general'
                            ? 'border-primary-600 dark:border-primary-400 text-primary-700 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <span class="inline-flex items-center gap-2">
                            <x-heroicon-o-book-open class="w-4 h-4" />
                            Buku Besar Umum
                        </span>
                    </button>
                    <button wire:click="setActiveTab('receivable')" role="tab"
                        class="whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeTab === 'receivable'
                            ? 'border-primary-600 dark:border-primary-400 text-primary-700 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <span class="inline-flex items-center gap-2">
                            <x-heroicon-o-arrow-left-on-rectangle class="w-4 h-4" />
                            Buku Pembantu Piutang
                        </span>
                    </button>
                    <button wire:click="setActiveTab('payable')" role="tab"
                        class="whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeTab === 'payable'
                            ? 'border-primary-600 dark:border-primary-400 text-primary-700 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <span class="inline-flex items-center gap-2">
                            <x-heroicon-o-arrow-right-on-rectangle class="w-4 h-4" />
                            Buku Pembantu Hutang
                        </span>
                    </button>
                    <button wire:click="setActiveTab('inventory')" role="tab"
                        class="whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeTab === 'inventory'
                            ? 'border-primary-600 dark:border-primary-400 text-primary-700 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <span class="inline-flex items-center gap-2">
                            <x-heroicon-o-cube class="w-4 h-4" />
                            Buku Pembantu Persediaan
                        </span>
                    </button>
                </nav>
            </div>

            @php $data = $this->getData(); @endphp

            {{-- ============================================================ --}}
            {{-- TAB: BUKU BESAR UMUM --}}
            {{-- ============================================================ --}}
            @if ($activeTab === 'general')
                <div>
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">BUKU BESAR UMUM</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['date_from'] }} s/d {{ $data['date_to'] }}</p>
                    </div>

                    @forelse($data['accounts'] as $item)
                        @php
                            $acct = $item['account'];
                            $catLabel = $item['category_label'];
                            $catColor = $item['category_color'];
                        @endphp
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg mb-6 overflow-hidden">
                            {{-- Account Header --}}
                            <div class="flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $catColor }}">
                                        {{ $catLabel }}
                                    </span>
                                    <span class="font-mono text-sm text-gray-500 dark:text-gray-400">{{ $acct->code }}</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $acct->name }}</span>
                                </div>
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    Posisi Normal: <span class="font-medium {{ $acct->normal_balance === 'debit' ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400' }}">{{ ucfirst($acct->normal_balance) }}</span>
                                </span>
                            </div>

                            {{-- Transactions Table --}}
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800/30 border-b text-xs uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <th class="px-3 py-2.5 text-left w-24">Tanggal</th>
                                            <th class="px-3 py-2.5 text-left w-32">No Transaksi</th>
                                            <th class="px-3 py-2.5 text-left">Keterangan</th>
                                            <th class="px-3 py-2.5 text-center w-24">Sumber</th>
                                            <th class="px-3 py-2.5 text-right w-28">Debit (Rp)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Kredit (Rp)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Saldo (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        {{-- Opening Balance Row --}}
                                        @php
                                            $openBal = $item['opening_balance'];
                                            $openDisplay = $openBal < 0 ? '(' . rupiah(abs($openBal), false) . ')' : rupiah($openBal, false);
                                            $openClass = $openBal < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400';
                                            $openShowBal = $openBal != 0 ? $openDisplay : '-';
                                        @endphp
                                        <tr class="bg-gray-50/30 dark:bg-gray-800/20 text-xs text-gray-500 dark:text-gray-400">
                                            <td class="px-3 py-2" colspan="4">
                                                <span class="font-medium">Saldo Awal</span>
                                                @if($openBal == 0)
                                                    <span class="italic ml-1">(Rp 0)</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono font-medium {{ $openClass }}">{{ $openShowBal }}</td>
                                        </tr>

                                        {{-- Transaction Rows --}}
                                        @forelse($item['transactions'] as $tx)
                                            @php
                                                $bal = $tx['balance'];
                                                $balDisplay = $bal < 0 ? '(' . rupiah(abs($bal), false) . ')' : rupiah($bal, false);
                                                $balClass = $bal < 0 ? 'text-red-600 dark:text-red-400' : '';
                                                $sourceColorClass = \App\Services\LedgerService::sourceColor($tx['source']);
                                                $sourceLabel = \App\Services\LedgerService::sourceLabel($tx['source']);
                                            @endphp
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
                                                <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/Y') }}</td>
                                                <td class="px-3 py-2">
                                                    <a href="/admin/journals/{{ $tx['journal_id'] }}" class="text-primary-600 dark:text-primary-400 hover:underline font-mono text-xs" target="_blank">
                                                        {{ $tx['journal_number'] }}
                                                    </a>
                                                </td>
                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $tx['description'] }}</td>
                                                <td class="px-3 py-2 text-center">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $sourceColorClass }}">
                                                        {{ $sourceLabel }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">
                                                    @if($tx['debit'] > 0){{ rupiah($tx['debit'], false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">
                                                    @if($tx['credit'] > 0){{ rupiah($tx['credit'], false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono font-medium {{ $balClass }}">
                                                    {{ $balDisplay }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="px-3 py-6 text-center text-gray-400 dark:text-gray-500 italic">
                                                    Tidak ada transaksi dalam periode ini
                                                </td>
                                            </tr>
                                        @endforelse

                                        {{-- Footer: TOTAL PERIODE --}}
                                        @php
                                            $td = $item['total_debit'];
                                            $tc = $item['total_credit'];
                                            $closeBal = $item['closing_balance'];
                                            $closeDisplay = $closeBal < 0 ? '(' . rupiah(abs($closeBal), false) . ')' : rupiah($closeBal, false);
                                        @endphp
                                        <tr class="bg-gray-50 dark:bg-gray-800/40 border-t-2 border-gray-200 dark:border-gray-700">
                                            <td class="px-3 py-2.5 font-semibold text-gray-700 dark:text-gray-300 text-xs" colspan="4">TOTAL PERIODE</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-success-600 dark:text-green-400">
                                                @if($td > 0){{ rupiah($td, false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-blue-600 dark:text-blue-400">
                                                @if($tc > 0){{ rupiah($tc, false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold {{ $closeBal < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' }}">
                                                {{ $closeDisplay }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-400 dark:text-gray-500 italic border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                            Tidak ada data buku besar untuk periode ini
                        </div>
                    @endforelse

                    {{-- GRAND RECAPITULATION --}}
                    @if(count($data['accounts']) > 0)
                        @php
                            $grandDebit = collect($data['accounts'])->sum('total_debit');
                            $grandCredit = collect($data['accounts'])->sum('total_credit');
                            $diff = abs($grandDebit - $grandCredit);
                            $isBalanced = $diff < 1;

                            $assets   = collect($data['accounts'])->where('account.category', 'asset')->sum('closing_balance');
                            $liabs    = collect($data['accounts'])->where('account.category', 'liability')->sum('closing_balance');
                            $equity   = collect($data['accounts'])->where('account.category', 'equity')->sum('closing_balance');
                            $revenue  = collect($data['accounts'])->where('account.category', 'revenue')->sum('closing_balance');
                            $expenses = collect($data['accounts'])->whereIn('account.category', ['expense', 'cogs'])->sum('closing_balance');
                            $netIncome = $revenue - $expenses;
                        @endphp
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden mt-6">
                            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700">
                                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rekapitulasi</span>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                    <div class="bg-white dark:bg-gray-800/40 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mb-1">Total Debit Periode</div>
                                        <div class="font-mono font-bold text-success-600 dark:text-green-400">{{ rupiah($grandDebit) }}</div>
                                    </div>
                                    <div class="bg-white dark:bg-gray-800/40 rounded-lg p-3 border border-gray-100 dark:border-gray-700">
                                        <div class="text-xs text-gray-400 dark:text-gray-500 mb-1">Total Kredit Periode</div>
                                        <div class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ rupiah($grandCredit) }}</div>
                                    </div>
                                </div>

                                <div class="mt-3 text-xs flex items-center gap-2 {{ $isBalanced ? 'text-success-600 dark:text-green-400' : 'text-danger-600 dark:text-red-400' }}">
                                    @if($isBalanced)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Double-entry balance: Debit = Kredit
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                        Selisih Debit & Kredit: {{ rupiah($diff) }}
                                    @endif
                                </div>

                                <div class="mt-4">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-200 dark:border-gray-700 text-xs uppercase text-gray-500 dark:text-gray-400">
                                                <th class="px-3 py-2 text-left font-semibold">Kelompok Akun</th>
                                                <th class="px-3 py-2 text-right font-semibold">Saldo (Rp)</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                            <tr>
                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">Aset</span>
                                                    Harta
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono font-medium">{{ rupiah($assets) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">Kewajiban</span>
                                                    Utang
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono font-medium">{{ rupiah($liabs) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-400">Modal</span>
                                                    Ekuitas
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono font-medium">{{ rupiah($equity) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">Pendapatan</span>
                                                    Penjualan & Pendapatan
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono font-medium text-green-600 dark:text-green-400">{{ rupiah($revenue) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400">Biaya</span>
                                                    Beban & HPP
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono font-medium text-orange-600 dark:text-orange-400">{{ rupiah($expenses) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/40">
                                                <td class="px-3 py-2.5 font-bold text-gray-800 dark:text-gray-200 text-sm">Laba Bersih Periode</td>
                                                <td class="px-3 py-2.5 text-right font-mono font-bold text-sm {{ $netIncome >= 0 ? 'text-success-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    @if($netIncome < 0)
                                                        ({{ rupiah(abs($netIncome)) }})
                                                    @else
                                                        {{ rupiah($netIncome) }}
                                                    @endif
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- TAB: PIUTANG --}}
            {{-- ============================================================ --}}
            @if ($activeTab === 'receivable')
                <div>
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">BUKU BESAR PEMBANTU PIUTANG</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['date_from'] }} s/d {{ $data['date_to'] }}</p>
                    </div>

                    @forelse($data['contacts'] as $item)
                        @php
                            $saldoAkhir = $item['closing_balance'];
                            $tambah = collect($item['transactions'])->sum('debit');
                            $kurang = collect($item['transactions'])->sum('credit');
                            $saldoColor = $saldoAkhir > 0 ? 'text-success-600 dark:text-green-400' : ($saldoAkhir < 0 ? 'text-danger-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400');
                        @endphp
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg mb-6 overflow-hidden">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['contact']->name }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">Saldo Awal: <span class="font-mono font-medium">{{ rupiah($item['opening_balance']) }}</span></span>
                                <span class="text-xs text-success-600 dark:text-green-400">Penambahan: <span class="font-mono font-medium">{{ rupiah($tambah) }}</span></span>
                                <span class="text-xs text-danger-600 dark:text-red-400">Pengurangan: <span class="font-mono font-medium">{{ rupiah($kurang) }}</span></span>
                                <span class="text-xs">Saldo Akhir: <span class="font-mono font-medium {{ $saldoColor }}">{{ rupiah($saldoAkhir) }}</span></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800/50 border-b text-xs uppercase text-gray-500 dark:text-gray-400">
                                            <th class="px-3 py-2.5 text-left w-24">Tanggal</th>
                                            <th class="px-3 py-2.5 text-left w-32">No Dokumen</th>
                                            <th class="px-3 py-2.5 text-left">Keterangan</th>
                                            <th class="px-3 py-2.5 text-right w-28">Debit (Rp)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Kredit (Rp)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Saldo (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        <tr class="bg-gray-50/30 dark:bg-gray-800/20 text-xs text-gray-500 dark:text-gray-400">
                                            <td class="px-3 py-2" colspan="3"><span class="font-medium">Saldo Awal</span></td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono font-medium">{{ rupiah($item['opening_balance'], false) }}</td>
                                        </tr>
                                        @forelse($item['transactions'] as $tx)
                                            @php
                                                $bal = $tx['balance'] ?? 0;
                                                $docUrl = match($tx['ref_type'] ?? '') {
                                                    'invoice' => '/admin/invoices/' . $tx['ref_id'],
                                                    'payment' => '/admin/payments/' . $tx['ref_id'],
                                                    'credit_note' => '/admin/credit-notes/' . $tx['ref_id'],
                                                    default => null,
                                                };
                                            @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
                                            <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/Y') }}</td>
                                            <td class="px-3 py-2">
                                                @if($docUrl)
                                                    <a href="{{ $docUrl }}" class="text-primary-600 dark:text-primary-400 hover:underline font-mono text-xs" target="_blank">{{ $tx['document'] }}</a>
                                                @else
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $tx['document'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $tx['description'] }}</td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">@if($tx['debit'] > 0){{ rupiah($tx['debit'], false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">@if($tx['credit'] > 0){{ rupiah($tx['credit'], false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2 text-right font-mono font-medium">{{ rupiah($bal, false) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-gray-400 dark:text-gray-500 italic">Tidak ada transaksi dalam periode ini</td>
                                        </tr>
                                        @endforelse
                                        <tr class="bg-gray-50 dark:bg-gray-800/40 border-t-2 border-gray-200 dark:border-gray-700">
                                            <td class="px-3 py-2.5 font-semibold text-gray-700 dark:text-gray-300 text-xs" colspan="3">TOTAL PERIODE</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-success-600 dark:text-green-400">@if($tambah > 0){{ rupiah($tambah, false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-blue-600 dark:text-blue-400">@if($kurang > 0){{ rupiah($kurang, false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold {{ $saldoColor }}">{{ rupiah($saldoAkhir, false) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-400 dark:text-gray-500 italic border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                            Tidak ada data piutang untuk periode ini
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- TAB: HUTANG --}}
            {{-- ============================================================ --}}
            @if ($activeTab === 'payable')
                <div>
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">BUKU BESAR PEMBANTU HUTANG</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['date_from'] }} s/d {{ $data['date_to'] }}</p>
                    </div>

                    @forelse($data['contacts'] as $item)
                        @php
                            $saldoAkhir = $item['closing_balance'];
                            $tambah = collect($item['transactions'])->sum('credit');
                            $kurang = collect($item['transactions'])->sum('debit');
                            $saldoColor = $saldoAkhir > 0 ? 'text-success-600 dark:text-green-400' : ($saldoAkhir < 0 ? 'text-danger-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400');
                        @endphp
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg mb-6 overflow-hidden">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['contact']->name }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">Saldo Awal: <span class="font-mono font-medium">{{ rupiah($item['opening_balance']) }}</span></span>
                                <span class="text-xs text-danger-600 dark:text-red-400">Penambahan: <span class="font-mono font-medium">{{ rupiah($tambah) }}</span></span>
                                <span class="text-xs text-success-600 dark:text-green-400">Pengurangan: <span class="font-mono font-medium">{{ rupiah($kurang) }}</span></span>
                                <span class="text-xs">Saldo Akhir: <span class="font-mono font-medium {{ $saldoColor }}">{{ rupiah($saldoAkhir) }}</span></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800/50 border-b text-xs uppercase text-gray-500 dark:text-gray-400">
                                            <th class="px-3 py-2.5 text-left w-24">Tanggal</th>
                                            <th class="px-3 py-2.5 text-left w-32">No Dokumen</th>
                                            <th class="px-3 py-2.5 text-left">Keterangan</th>
                                            <th class="px-3 py-2.5 text-right w-28">Debit (Rp)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Kredit (Rp)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Saldo (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        <tr class="bg-gray-50/30 dark:bg-gray-800/20 text-xs text-gray-500 dark:text-gray-400">
                                            <td class="px-3 py-2" colspan="3"><span class="font-medium">Saldo Awal</span></td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono font-medium">{{ rupiah($item['opening_balance'], false) }}</td>
                                        </tr>
                                        @forelse($item['transactions'] as $tx)
                                            @php
                                                $bal = $tx['balance'] ?? 0;
                                                $docUrl = match($tx['ref_type'] ?? '') {
                                                    'purchase' => '/admin/purchases/' . $tx['ref_id'],
                                                    'payment' => '/admin/payments/' . $tx['ref_id'],
                                                    'debit_note' => '/admin/debit-notes/' . $tx['ref_id'],
                                                    default => null,
                                                };
                                            @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
                                            <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/Y') }}</td>
                                            <td class="px-3 py-2">
                                                @if($docUrl)
                                                    <a href="{{ $docUrl }}" class="text-primary-600 dark:text-primary-400 hover:underline font-mono text-xs" target="_blank">{{ $tx['document'] }}</a>
                                                @else
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $tx['document'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $tx['description'] }}</td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">@if($tx['debit'] > 0){{ rupiah($tx['debit'], false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2 text-right font-mono text-gray-800 dark:text-gray-200">@if($tx['credit'] > 0){{ rupiah($tx['credit'], false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2 text-right font-mono font-medium">{{ rupiah($bal, false) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-gray-400 dark:text-gray-500 italic">Tidak ada transaksi dalam periode ini</td>
                                        </tr>
                                        @endforelse
                                        <tr class="bg-gray-50 dark:bg-gray-800/40 border-t-2 border-gray-200 dark:border-gray-700">
                                            <td class="px-3 py-2.5 font-semibold text-gray-700 dark:text-gray-300 text-xs" colspan="3">TOTAL PERIODE</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-success-600 dark:text-green-400">@if($kurang > 0){{ rupiah($kurang, false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-blue-600 dark:text-blue-400">@if($tambah > 0){{ rupiah($tambah, false) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold {{ $saldoColor }}">{{ rupiah($saldoAkhir, false) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-400 dark:text-gray-500 italic border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                            Tidak ada data hutang untuk periode ini
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- ============================================================ --}}
            {{-- TAB: PERSEDIAAN --}}
            {{-- ============================================================ --}}
            @if ($activeTab === 'inventory')
                <div>
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2 mb-4">
                        <h2 class="text-lg font-bold text-gray-800 dark:text-gray-100">BUKU BESAR PEMBANTU PERSEDIAAN</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $data['date_from'] }} s/d {{ $data['date_to'] }}</p>
                    </div>

                    @forelse($data['products'] as $item)
                        @php
                            $totalIn = collect($item['transactions'])->sum('qty_in');
                            $totalOut = collect($item['transactions'])->sum('qty_out');
                            $unit = $item['product']->unit ?? 'pcs';
                        @endphp
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg mb-6 overflow-hidden">
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 bg-gray-50 dark:bg-gray-800/60 border-b border-gray-200 dark:border-gray-700 text-sm">
                                <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $item['product']->code }} — {{ $item['product']->name }}</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">Stok Awal: <span class="font-mono font-medium">{{ number_format($item['opening_stock'], 2) }}</span></span>
                                <span class="text-xs text-success-600 dark:text-green-400">Masuk: <span class="font-mono font-medium">{{ number_format($totalIn, 2) }} {{ $unit }}</span></span>
                                <span class="text-xs text-danger-600 dark:text-red-400">Keluar: <span class="font-mono font-medium">{{ number_format($totalOut, 2) }} {{ $unit }}</span></span>
                                <span class="text-xs">Stok Akhir: <span class="font-mono font-medium">{{ number_format($item['closing_stock'], 2) }} {{ $unit }}</span></span>
                                <span class="text-xs">Nilai HPP: <span class="font-mono font-medium">{{ rupiah($item['closing_value'] ?? 0) }}</span></span>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-gray-50 dark:bg-gray-800/50 border-b text-xs uppercase text-gray-500 dark:text-gray-400">
                                            <th class="px-3 py-2.5 text-left w-24">Tanggal</th>
                                            <th class="px-3 py-2.5 text-left w-32">No Dokumen</th>
                                            <th class="px-3 py-2.5 text-left">Keterangan</th>
                                            <th class="px-3 py-2.5 text-right w-20">Masuk</th>
                                            <th class="px-3 py-2.5 text-right w-20">Keluar</th>
                                            <th class="px-3 py-2.5 text-right w-20">Stok</th>
                                            <th class="px-3 py-2.5 text-right w-28">Nilai (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        <tr class="bg-gray-50/30 dark:bg-gray-800/20 text-xs text-gray-500 dark:text-gray-400">
                                            <td class="px-3 py-2" colspan="3"><span class="font-medium">Saldo Awal</span></td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono">-</td>
                                            <td class="px-3 py-2 text-right font-mono font-medium">{{ number_format($item['opening_stock'], 2) }}</td>
                                            <td class="px-3 py-2 text-right font-mono">{{ rupiah($item['opening_value'] ?? 0, false) }}</td>
                                        </tr>
                                        @forelse($item['transactions'] as $tx)
                                            @php
                                                $docUrl = match($tx['ref_type'] ?? '') {
                                                    'purchases' => $tx['ref_id'] ? '/admin/purchases/' . $tx['ref_id'] : null,
                                                    'invoices' => $tx['ref_id'] ? '/admin/invoices/' . $tx['ref_id'] : null,
                                                    'credit_notes' => $tx['ref_id'] ? '/admin/credit-notes/' . $tx['ref_id'] : null,
                                                    'debit_notes' => $tx['ref_id'] ? '/admin/debit-notes/' . $tx['ref_id'] : null,
                                                    default => $tx['journal_id'] ? '/admin/journals/' . $tx['journal_id'] : null,
                                                };
                                            @endphp
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/20 transition-colors">
                                            <td class="px-3 py-2 whitespace-nowrap font-mono text-xs">{{ \Carbon\Carbon::parse($tx['date'])->format('d/m/Y') }}</td>
                                            <td class="px-3 py-2">
                                                @if($docUrl)
                                                    <a href="{{ $docUrl }}" class="text-primary-600 dark:text-primary-400 hover:underline font-mono text-xs" target="_blank">{{ $tx['document'] }}</a>
                                                @else
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $tx['document'] }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $tx['description'] }}</td>
                                            <td class="px-3 py-2 text-right font-mono text-success-600 dark:text-green-400">@if($tx['qty_in'] > 0){{ number_format($tx['qty_in'], 2) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2 text-right font-mono text-danger-600 dark:text-red-400">@if($tx['qty_out'] > 0){{ number_format($tx['qty_out'], 2) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2 text-right font-mono font-medium">{{ number_format($tx['stock_balance'], 2) }}</td>
                                            <td class="px-3 py-2 text-right font-mono">{{ rupiah($tx['value_balance'] ?? 0) }}</td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="7" class="px-3 py-6 text-center text-gray-400 dark:text-gray-500 italic">Tidak ada transaksi dalam periode ini</td>
                                        </tr>
                                        @endforelse
                                        <tr class="bg-gray-50 dark:bg-gray-800/40 border-t-2 border-gray-200 dark:border-gray-700">
                                            <td class="px-3 py-2.5 font-semibold text-gray-700 dark:text-gray-300 text-xs" colspan="3">TOTAL</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-success-600 dark:text-green-400">@if($totalIn > 0){{ number_format($totalIn, 2) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold text-danger-600 dark:text-red-400">@if($totalOut > 0){{ number_format($totalOut, 2) }}@else<span class="text-gray-300 dark:text-gray-600">-</span>@endif</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold">{{ number_format($item['closing_stock'], 2) }}</td>
                                            <td class="px-3 py-2.5 text-right font-mono font-bold">{{ rupiah($item['closing_value'] ?? 0, false) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 text-gray-400 dark:text-gray-500 italic border border-dashed border-gray-300 dark:border-gray-700 rounded-lg">
                            Tidak ada data persediaan untuk periode ini
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </x-filament::section>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('print-report', () => {
                window.print();
            });
        });
    </script>
</x-filament-panels::page>
