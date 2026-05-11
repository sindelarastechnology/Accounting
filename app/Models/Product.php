<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'type',
        'unit',
        'description',
        'purchase_price',
        'selling_price',
        'stock_on_hand',
        'stock_minimum',
        'tax_rate',
        'revenue_account_id',
        'cogs_account_id',
        'inventory_account_id',
        'purchase_account_id',
        'subscription_duration',
        'subscription_unit',
        'is_active',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'stock_on_hand' => 'decimal:4',
        'stock_minimum' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'subscription_duration' => 'integer',
        'is_active' => 'boolean',
        'deleted_at' => 'datetime',
    ];

    public function revenueAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'revenue_account_id');
    }

    public function cogsAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'cogs_account_id');
    }

    public function inventoryAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'inventory_account_id');
    }

    public function purchaseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGoods($query)
    {
        return $query->where('type', 'goods');
    }

    public static function types(): array
    {
        return [
            'goods' => 'Barang',
            'service' => 'Jasa',
            'subscription' => 'Langganan',
            'bundle' => 'Paket',
        ];
    }

    public static function subscriptionUnits(): array
    {
        return [
            'day' => 'Hari',
            'month' => 'Bulan',
            'year' => 'Tahun',
        ];
    }
}
