<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit_amount',
        'credit_amount',
        'wallet_id',
        'description',
        'reconciled_at',
        'reconciled_by',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:4',
        'credit_amount' => 'decimal:4',
        'reconciled_at' => 'datetime',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
