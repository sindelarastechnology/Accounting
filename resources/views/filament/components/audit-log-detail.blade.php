<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">User:</span>
            <p class="mt-1">{{ $record->user?->name ?? '-' }}</p>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Aksi:</span>
            <p class="mt-1">{{ $record->event }}</p>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Tipe:</span>
            <p class="mt-1">{{ class_basename($record->auditable_type) }}</p>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">ID:</span>
            <p class="mt-1">{{ $record->auditable_id }}</p>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">IP Address:</span>
            <p class="mt-1">{{ $record->ip_address ?? '-' }}</p>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Waktu:</span>
            <p class="mt-1">{{ $record->created_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
        </div>
    </div>

    @if($record->old_values)
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Data Sebelum:</span>
            <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-x-auto text-xs">{{ json_encode($record->old_values, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if($record->new_values)
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Data Sesudah:</span>
            <pre class="mt-2 p-3 bg-gray-100 dark:bg-gray-800 rounded-lg overflow-x-auto text-xs">{{ json_encode($record->new_values, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif
</div>
