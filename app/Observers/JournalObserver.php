<?php

namespace App\Observers;

use App\Models\Journal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class JournalObserver
{
    public function updated(Journal $journal): void
    {
        $changes = $journal->getChanges();

        if (!isset($changes['type'])) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        if (isset($changes['type'])) {
            $original = $journal->getOriginal('type');
            $oldValues['type'] = $original;
            $newValues['type'] = $changes['type'];
        }

        if (isset($changes['reversed_by_journal_id'])) {
            $newValues['reversed_by_journal_id'] = $changes['reversed_by_journal_id'];
        }

        \App\Models\AuditLog::create([
            'user_id' => Auth::id(),
            'event' => 'updated',
            'auditable_type' => Journal::class,
            'auditable_id' => $journal->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
