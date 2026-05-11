<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Wallet extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'bank_name',
        'account_number',
        'account_holder',
        'account_id',
        'opening_balance',
        'equity_account_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:4',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function equityAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'equity_account_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function openingBalances(): HasMany
    {
        return $this->hasMany(OpeningBalance::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedOpeningBalanceAttribute(): string
    {
        return 'Rp ' . number_format((float) $this->opening_balance, 0, ',', '.');
    }

    public static function types(): array
    {
        return [
            'cash' => 'Kas',
            'bank' => 'Bank',
            'ewallet' => 'E-Wallet',
        ];
    }
}
