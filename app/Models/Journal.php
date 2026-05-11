<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'date',
        'period_id',
        'description',
        'source',
        'type',
        'ref_type',
        'ref_id',
        'reversed_journal_id',
        'reversed_by_journal_id',
        'is_posted',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_posted' => 'boolean',
        'ref_id' => 'integer',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversed_journal_id');
    }

    public function reversedByJournal(): BelongsTo
    {
        return $this->belongsTo(Journal::class, 'reversed_by_journal_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    public function referenceable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function sources(): array
    {
        return [
            'manual' => 'Manual',
            'sale' => 'Penjualan',
            'purchase' => 'Pembelian',
            'payment' => 'Pembayaran',
            'expense' => 'Beban',
            'stock_opname' => 'Stock Opname',
            'opening' => 'Saldo Awal',
            'closing' => 'Penutup',
            'fixed_asset' => 'Aset Tetap',
            'depreciation' => 'Penyusutan',
            'credit_note' => 'Credit Note',
            'debit_note' => 'Debit Note',
            'transfer' => 'Transfer Kas',
            'other_receipt' => 'Kas Masuk Lainnya',
            'system' => 'Sistem',
        ];
    }

    public static function types(): array
    {
        return [
            'normal' => 'Normal',
            'reversal' => 'Pembalik',
            'void' => 'Dibatalkan',
            'closing' => 'Penutup',
            'opening' => 'Saldo Awal',
        ];
    }
}
