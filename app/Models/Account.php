<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'category',
        'normal_balance',
        'parent_id',
        'is_header',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_header' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function openingBalances(): HasMany
    {
        return $this->hasMany(OpeningBalance::class);
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function scopeHeader($query)
    {
        return $query->where('is_header', true);
    }

    public function scopeDetail($query)
    {
        return $query->where('is_header', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public static function categories(): array
    {
        return [
            'asset' => 'Aset',
            'liability' => 'Liabilitas',
            'equity' => 'Ekuitas',
            'revenue' => 'Pendapatan',
            'cogs' => 'HPP',
            'expense' => 'Beban',
        ];
    }

    public static function normalBalanceForCategory(string $category): string
    {
        return match ($category) {
            'asset', 'cogs', 'expense' => 'debit',
            default => 'credit',
        };
    }
}
