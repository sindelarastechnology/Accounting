<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'acquisition_date',
        'acquisition_cost',
        'salvage_value',
        'useful_life_years',
        'depreciation_method',
        'accumulated_depreciation',
        'monthly_depreciation',
        'is_fully_depreciated',
        'asset_account_id',
        'depreciation_account_id',
        'accumulated_depreciation_account_id',
        'status',
        'disposed_date',
        'disposal_amount',
        'created_by',
    ];

    protected $casts = [
        'acquisition_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'monthly_depreciation' => 'decimal:2',
        'is_fully_depreciated' => 'boolean',
        'disposed_date' => 'date',
        'disposal_amount' => 'decimal:2',
    ];

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function depreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'depreciation_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'accumulated_depreciation_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getBookValueAttribute(): float
    {
        return (float) $this->acquisition_cost - (float) $this->accumulated_depreciation;
    }

    public function getDepreciableAmountAttribute(): float
    {
        return (float) $this->acquisition_cost - (float) $this->salvage_value;
    }

    public function getRemainingMonthsAttribute(): int
    {
        $totalMonths = $this->useful_life_years * 12;
        $elapsedMonths = now()->diffInMonths($this->acquisition_date);
        return max(0, $totalMonths - $elapsedMonths);
    }
}
