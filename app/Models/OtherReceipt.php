<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtherReceipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'number', 'period_id', 'journal_id', 'date',
        'wallet_id', 'contact_id', 'receipt_type',
        'amount', 'credit_account_id', 'reference',
        'notes', 'status', 'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:4',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function receiptTypes(): array
    {
        return [
            'capital_injection' => 'Tambahan Modal',
            'owner_loan' => 'Pinjaman dari Pemilik',
            'other_income' => 'Pendapatan Lain-lain',
            'refund' => 'Pengembalian Dana',
            'other' => 'Lainnya',
        ];
    }

    public static function defaultCreditAccount(string $receiptType): ?int
    {
        return match ($receiptType) {
            'capital_injection' => Account::where('code', '3100-00-020')->value('id'),
            'owner_loan' => Account::where('code', '2300-00-020')->value('id'),
            'other_income' => Account::where('code', '4300')->value('id'),
            default => null,
        };
    }
}
