<?php

namespace App\Services\Commerce;

use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function adjust(
        Shop $shop,
        Product $product,
        float $quantityChange,
        string $type,
        ?User $user = null,
        ?string $note = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): StockMovement {
        if ($product->shop_id !== $shop->id) {
            throw new InvalidArgumentException('Product does not belong to the active shop.');
        }

        return DB::transaction(function () use ($product, $shop, $quantityChange, $type, $user, $note, $referenceType, $referenceId) {
            $product->refresh();

            $before = (float) $product->current_stock;
            $after = $before + $quantityChange;

            if ($after < 0) {
                throw new InvalidArgumentException('Insufficient stock for this action.');
            }

            $product->forceFill([
                'current_stock' => $after,
            ])->save();

            return StockMovement::query()->create([
                'shop_id' => $shop->id,
                'product_id' => $product->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity_before' => $before,
                'quantity_change' => $quantityChange,
                'quantity_after' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'note' => $note,
                'happened_at' => now(),
            ]);
        });
    }
}
