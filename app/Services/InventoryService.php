<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Collection;

class InventoryService
{
    /**
     * Record a stock movement and update the product quantity.
     * Must be called inside a transaction.
     */
    public function recordMovement(
        Product $product,
        int $quantity,
        string $type,
        ?string $reference = null,
        ?User $user = null,
        ?string $notes = null
    ): StockMovement {
        $product->increment('stock_quantity', $quantity);

        return StockMovement::create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'type' => $type,
            'reference' => $reference,
            'user_id' => $user?->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Adjust stock by a signed quantity (positive = in, negative = out).
     */
    public function adjust(Product $product, int $quantity, ?User $user = null, ?string $notes = null): StockMovement
    {
        if ($quantity < 0 && $product->stock_quantity + $quantity < 0) {
            throw new \RuntimeException('Insufficient stock for adjustment.');
        }

        return $this->recordMovement(
            $product,
            $quantity,
            'adjustment',
            null,
            $user,
            $notes
        );
    }

    /**
     * Deduct stock for a sale. Must run inside a transaction with locked rows.
     *
     * @param Collection<int, array{product_id:int, quantity:int}> $items
     */
    public function deductForSale(Collection $items, string $receiptNumber, User $user): void
    {
        $this->applyForSale($items, $receiptNumber, $user, -1, 'sale');
    }

    /**
     * Restore stock when a sale is voided.
     *
     * @param Collection<int, array{product_id:int, quantity:int}> $items
     */
    public function restoreForVoid(Collection $items, string $receiptNumber, User $user): void
    {
        $this->applyForSale($items, $receiptNumber, $user, 1, 'void');
    }

    /**
     * @param Collection<int, array{product_id:int, quantity:int}> $items
     */
    private function applyForSale(Collection $items, string $reference, User $user, int $sign, string $type): void
    {
        $products = Product::whereIn('id', $items->pluck('product_id'))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw new \RuntimeException('Product not found.');
            }

            $delta = $sign * (int) $item['quantity'];

            if ($delta < 0 && $product->stock_quantity + $delta < 0) {
                throw new \RuntimeException("Insufficient stock for product: {$product->name}");
            }

            $this->recordMovement($product, $delta, $type, $reference, $user);
        }
    }
}
