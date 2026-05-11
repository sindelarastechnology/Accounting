<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InvoiceTax extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id', 'tax_rule_id', 'tax_code', 'tax_name', 'method',
        'rate', 'base_amount', 'tax_amount', 'debit_account_id', 'credit_account_id'
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'base_amount' => 'decimal:4',
        'tax_amount' => 'decimal:4',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function taxRule(): BelongsTo
    {
        return $this->belongsTo(TaxRule::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }
}
