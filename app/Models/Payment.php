<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'payable_type', 'payable_id', 'journal_id', 'period_id', 'wallet_id',
        'date', 'amount', 'withholding_amount', 'method', 'reference', 'notes',
        'status', 'verified_by', 'verified_at', 'created_by'
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:4',
        'withholding_amount' => 'decimal:4',
        'verified_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }
}
