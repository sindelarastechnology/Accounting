<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxPayment extends Model
{
    use SoftDeletes;

    const TAX_TYPES = [
        'ppn'    => 'PPN (Pajak Pertambahan Nilai)',
        'pph23'  => 'PPh Pasal 23',
        'pph21'  => 'PPh Pasal 21',
        'pph4a2' => 'PPh Pasal 4 Ayat 2',
    ];

    const TAX_ACCOUNT_CODES = [
        'ppn'    => '2100-00-070',
        'pph23'  => '2100-00-071',
        'pph21'  => '2100-00-072',
        'pph4a2' => '2100-00-073',
    ];

    protected $fillable = [
        'period_id', 'account_id', 'journal_id', 'created_by',
        'document_number', 'tax_type', 'payment_date', 'amount',
        'reference', 'notes', 'status', 'posted_at',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:4',
        'posted_at' => 'datetime',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
