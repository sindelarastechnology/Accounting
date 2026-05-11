<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class FifoCostService
{
    /**
     * Calculate COGS using perpetual FIFO method.
     * Reconstructs inventory batches from inventory_movements in chronological order,
     * consuming oldest batches first for each historical out movement,
     * then allocates the requested qty from remaining stock.
     *
     * @return array{total_cost: float, breakdown: array, unit_cost_avg: float}
     * @throws InsufficientStockException
     */
    public static function getFifoCosts(int $productId, float $qtyToSell): array
    {
        $movements = InventoryMovement::where('product_id', $productId)
            ->orderBy('date')
            ->orderBy('id')
            ->get(['id', 'type', 'qty', 'unit_cost', 'total_cost', 'date', 'ref_type', 'ref_id']);

        $batches = [];

        foreach ($movements as $mov) {
            $qty = (float) $mov->qty;

            if ($qty > 0) {
                $batches[] = [
                    'qty' => $qty,
                    'unit_cost' => (float) $mov->unit_cost,
                ];
            } elseif ($qty < 0) {
                $remaining = abs($qty);
                while ($remaining > 0 && !empty($batches)) {
                    $consumed = min($batches[0]['qty'], $remaining);
                    $batches[0]['qty'] -= $consumed;
                    $remaining -= $consumed;
                    if ($batches[0]['qty'] <= 0) {
                        array_shift($batches);
                    }
                }
            }
        }

        $totalCost = 0;
        $breakdown = [];
        $remaining = $qtyToSell;

        foreach ($batches as $batch) {
            $consume = min($batch['qty'], $remaining);
            $cost = $consume * $batch['unit_cost'];
            $totalCost += $cost;
            $remaining -= $consume;
            $breakdown[] = [
                'qty' => $consume,
                'unit_cost' => $batch['unit_cost'],
                'cost' => $cost,
            ];
            if ($remaining <= 0) {
                break;
            }
        }

        if ($remaining > 0) {
            throw new InsufficientStockException(
                'Stok tidak mencukupi untuk FIFO. Dibutuhkan ' . $qtyToSell
                . ', tersedia ' . ($qtyToSell - $remaining) . '.'
            );
        }

        return [
            'total_cost' => $totalCost,
            'breakdown' => $breakdown,
            'unit_cost_avg' => $qtyToSell > 0 ? $totalCost / $qtyToSell : 0,
        ];
    }

    /**
     * Get the total FIFO value of current stock for a single product.
     */
    public static function getCurrentStockValue(int $productId): float
    {
        return self::calculateStockValue($productId);
    }

    /**
     * Get total closing inventory value (FIFO) across all goods products.
     * Optionally filter by a specific date.
     */
    public static function getClosingInventoryValue(?string $asOfDate = null): float
    {
        $productIds = Product::where('type', 'goods')
            ->where('is_active', true)
            ->pluck('id');

        $total = 0;
        foreach ($productIds as $id) {
            $total += self::calculateStockValue($id);
        }
        return $total;
    }

    /**
     * Core batch FIFO valuation for a single product.
     * Reconstructs all movements up to (optional) date and values remaining stock.
     */
    private static function calculateStockValue(int $productId, ?string $asOfDate = null): float
    {
        $query = InventoryMovement::where('product_id', $productId)
            ->orderBy('date')
            ->orderBy('id');

        if ($asOfDate) {
            $query->where('date', '<=', $asOfDate);
        }

        $movements = $query->get(['type', 'qty', 'unit_cost']);

        $batches = [];

        foreach ($movements as $mov) {
            $qty = (float) $mov->qty;

            if ($qty > 0) {
                $batches[] = [
                    'qty' => $qty,
                    'unit_cost' => (float) $mov->unit_cost,
                ];
            } elseif ($qty < 0) {
                $remaining = abs($qty);
                while ($remaining > 0 && !empty($batches)) {
                    $consumed = min($batches[0]['qty'], $remaining);
                    $batches[0]['qty'] -= $consumed;
                    $remaining -= $consumed;
                    if ($batches[0]['qty'] <= 0) {
                        array_shift($batches);
                    }
                }
            }
        }

        return array_reduce($batches, fn ($sum, $b) => $sum + ($b['qty'] * $b['unit_cost']), 0.0);
    }
}
