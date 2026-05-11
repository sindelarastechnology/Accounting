<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DebitNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'contact_id', 'period_id', 'purchase_id', 'journal_id',
        'date', 'reason', 'subtotal', 'discount_percent', 'discount_amount',
        'tax_amount', 'total', 'applied_amount', 'remaining_amount',
        'status', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'subtotal' => 'decimal:4',
        'discount_percent' => 'decimal:4',
        'discount_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
        'total' => 'decimal:4',
        'applied_amount' => 'decimal:4',
        'remaining_amount' => 'decimal:4',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DebitNoteItem::class)->orderBy('sort_order');
    }

    public static function statuses(): array
    {
        return [
            'draft' => 'Draft',
            'posted' => 'Posted',
            'applied' => 'Applied',
            'cancelled' => 'Dibatalkan',
        ];
    }
}
