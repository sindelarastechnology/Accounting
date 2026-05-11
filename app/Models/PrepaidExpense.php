<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrepaidExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'period_id',
        'asset_account_id',
        'expense_account_id',
        'description',
        'total_amount',
        'remaining_amount',
        'start_date',
        'total_months',
        'months_amortized',
        'monthly_amount',
        'status',
        'created_by',
        'journal_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'total_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
        'monthly_amount' => 'decimal:4',
        'total_months' => 'integer',
        'months_amortized' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'asset_account_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
