<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        @php $data = $this->getData(); @endphp

        @if(!empty($data))
            <div class="text-sm text-gray-500 mb-2">Periode: <strong>{{ $data['period_label'] }}</strong></div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach(['liquidity', 'profitability', 'solvency', 'efficiency'] as $category)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow overflow-hidden">
                        <div class="px-4 py-3 border-b dark:border-gray-700 flex items-center gap-2">
                            <x-filament::icon name="{{ $this->getCategoryIcon($category) }}" class="w-5 h-5 text-{{ $this->getCategoryColor($category) }}-500" />
                            <h3 class="font-bold text-lg">{{ $this->getCategoryLabel($category) }}</h3>
                        </div>
                        <div class="divide-y dark:divide-gray-700">
                            @foreach($data[$category] ?? [] as $key => $ratio)
                                <div class="px-4 py-3 flex justify-between items-center">
                                    <div>
                                        <div class="text-sm font-medium">{{ $ratio['label'] }}</div>
                                        @if($ratio['interpretation'])
                                            <div class="text-xs text-gray-500">{{ $ratio['interpretation'] }}</div>
                                        @endif
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-bold {{ $ratio['value'] !== null ? ($ratio['value'] > 0 ? 'text-green-600' : 'text-red-600') : 'text-gray-400' }}">
                                            {{ $ratio['value'] !== null ? number_format($ratio['value'], 2) . ' ' . $ratio['unit'] : 'N/A' }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Visual bar for key ratios --}}
            @if(isset($data['profitability']['gross_profit_margin']['value']))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
                    <h3 class="font-bold mb-4">Visualisasi Margin</h3>
                    <div class="space-y-4">
                        @foreach(['gross_profit_margin', 'net_profit_margin', 'operating_margin'] as $key)
                            @php $r = $data['profitability'][$key] ?? null; @endphp
                            @if($r && $r['value'] !== null)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span>{{ $r['label'] }}</span>
                                        <span class="font-semibold">{{ number_format($r['value'], 2) }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                        @php $pct = min(100, max(0, $r['value'])); @endphp
                                        <div class="bg-{{ $pct > 20 ? 'green' : ($pct > 10 ? 'yellow' : 'red') }}-500 h-3 rounded-full transition-all"
                                             style="width: {{ $pct }}%">
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow p-8 text-center text-gray-500">
                Pilih periode untuk melihat rasio keuangan.
            </div>
        @endif
    </div>
</x-filament-panels::page>
