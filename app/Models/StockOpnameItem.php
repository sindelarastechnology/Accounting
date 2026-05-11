<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StockOpnameItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_opname_id', 'product_id', 'qty_book', 'qty_actual',
        'qty_diff', 'unit_cost', 'total_diff_value', 'notes'
    ];

    protected $casts = [
        'qty_book' => 'decimal:4',
        'qty_actual' => 'decimal:4',
        'qty_diff' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'total_diff_value' => 'decimal:4',
    ];

    public function stockOpname(): BelongsTo
    {
        return $this->belongsTo(StockOpname::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
