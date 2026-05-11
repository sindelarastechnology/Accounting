<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'email',
        'phone',
        'address',
        'city',
        'tax_number',
        'contact_person',
        'ar_account_id',
        'ap_account_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function arAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ar_account_id');
    }

    public function apAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'ap_account_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeCustomer($query)
    {
        return $query->whereIn('type', ['customer', 'both']);
    }

    public function scopeSupplier($query)
    {
        return $query->whereIn('type', ['supplier', 'both']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function types(): array
    {
        return [
            'customer' => 'Customer',
            'supplier' => 'Supplier',
            'both' => 'Customer & Supplier',
        ];
    }
}
