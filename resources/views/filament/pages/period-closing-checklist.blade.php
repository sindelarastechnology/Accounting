<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="headerForm">
            {{ $this->form }}
        </x-slot>

        @php $period = \App\Models\Period::find($period_id); @endphp

        <div class="text-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">CLOSING CHECKLIST</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $period ? $period->name : 'Pilih periode terlebih dahulu' }}
            </p>
        </div>

        <div class="space-y-2">
            @foreach($steps as $index => $step)
                @php
                    $status = $stepStatus[$step['id']] ?? 'pending';
                    $isCompleted = $status === 'completed';
                    $isPending = $status === 'pending';
                    $isDisabled = $period && $period->is_closed && in_array($step['id'], ['close_period', 'open_next_period']);
                @endphp

                <div class="flex items-start gap-4 p-4 border rounded-lg
                    {{ $isCompleted ? 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800' : 'bg-white dark:bg-gray-900/50 border-gray-200 dark:border-gray-700' }}">

                    {{-- Step Number --}}
                    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $isCompleted
                            ? 'bg-green-500 text-white'
                            : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300' }}">
                        @if($isCompleted)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        @else
                            {{ $index + 1 }}
                        @endif
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $step['label'] }}
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $step['description'] }}
                        </p>
                    </div>

                    {{-- Action --}}
                    <div class="flex-shrink-0 flex gap-2">
                        @php
                            $isLink = $step['action_type'] === 'link';
                            $requiresConfirm = $step['requires_confirmation'] ?? false;
                            $hasSecondary = !empty($step['secondary_action_url']);
                            $btnColor = $isCompleted ? 'success' : 'primary';
                            $btnLabel = $isCompleted ? '✓ Selesai' : $step['action_label'];
                        @endphp
                        @if($isLink)
                            <x-filament::button
                                tag="a"
                                href="{{ $step['action_url'] }}"
                                color="gray"
                                size="sm"
                                outlined>
                                {{ $step['action_label'] }}
                            </x-filament::button>
                        @else
                            @if($hasSecondary)
                                <x-filament::button
                                    tag="a"
                                    href="{{ $step['secondary_action_url'] }}"
                                    color="gray"
                                    size="sm"
                                    outlined>
                                    {{ $step['secondary_action_label'] ?? 'Buka Halaman' }}
                                </x-filament::button>
                            @endif
                            <x-filament::button
                                wire:click="{{ $step['action_method'] }}"
                                wire:confirm="{{ $requiresConfirm ? 'Apakah Anda yakin?' : '' }}"
                                color="{{ $btnColor }}"
                                size="sm"
                                :outlined="$isCompleted">
                                {{ $btnLabel }}
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Summary --}}
        <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800/30 rounded-lg border border-gray-200 dark:border-gray-700">
            @php
                $completed = collect($stepStatus)->filter(fn($s) => $s === 'completed')->count();
                $total = count($steps);
            @endphp
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Progress</span>
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $completed }} / {{ $total }} langkah selesai</span>
            </div>
            <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="bg-primary-500 h-2.5 rounded-full transition-all duration-500"
                     style="width: {{ $total > 0 ? ($completed / $total) * 100 : 0 }}%">
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
