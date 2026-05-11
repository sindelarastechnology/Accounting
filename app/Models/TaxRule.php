<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'module',
        'method',
        'rate',
        'debit_account_id',
        'credit_account_id',
        'is_default',
        'is_active',
        'description',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function invoiceTaxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }

    public function purchaseTaxes(): HasMany
    {
        return $this->hasMany(PurchaseTax::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public static function types(): array
    {
        return [
            'ppn' => 'PPN',
            'pph' => 'PPh',
            'other' => 'Lainnya',
        ];
    }

    public static function modules(): array
    {
        return [
            'sale' => 'Penjualan',
            'purchase' => 'Pembelian',
            'both' => 'Penjualan & Pembelian',
        ];
    }

    public static function methods(): array
    {
        return [
            'exclusive' => 'Eksklusif (ditambahkan)',
            'inclusive' => 'Inklusif (sudah termasuk)',
            'withholding' => 'Potong (withholding)',
        ];
    }
}
