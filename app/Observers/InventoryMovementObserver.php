<?php

namespace App\Observers;

use App\Models\InventoryMovement;
use App\Models\Product;

class InventoryMovementObserver
{
    public function created(InventoryMovement $movement): void
    {
        $product = Product::find($movement->product_id);
        if (!$product) {
            return;
        }

        // Update stock on hand
        $product->stock_on_hand = (float) $product->stock_on_hand + (float) $movement->qty;
        $product->save();
    }

    public function deleted(InventoryMovement $movement): void
    {
        $product = Product::find($movement->product_id);
        if (!$product) {
            return;
        }

        // Reverse the movement
        $product->stock_on_hand = (float) $product->stock_on_hand - (float) $movement->qty;
        $product->save();
    }
}
