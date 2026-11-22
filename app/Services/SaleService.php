<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(
        private InventoryService $inventory,
        private ReceiptService $receipt
    ) {
    }

    /**
     * Create a sale with items and payment inside a DB transaction with row locking.
     *
     * @param array{
     *     items: array<int, array{product_id: int, quantity: int}>,
     *     discount?: float|int|string,
     *     tax?: float|int|string,
     *     payment_method: string,
     *     tendered?: float|int|string|null,
     *     notes?: string|null
     * } $data
     */
    public function checkout(array $data, User $cashier): Sale
    {
        return DB::transaction(function () use ($data, $cashier) {
            // Aggregate duplicate product lines
            $aggregated = collect($data['items'])
                ->groupBy('product_id')
                ->map(fn ($group, $productId) => [
                    'product_id' => (int) $productId,
                    'quantity' => (int) $group->sum('quantity'),
                ])
                ->values();

            // Lock product rows and validate stock before writing anything
            $products = \App\Models\Product::whereIn('id', $aggregated->pluck('product_id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;

            foreach ($aggregated as $item) {
                $product = $products->get($item['product_id']);

                if (!$product || !$product->is_active) {
                    throw new \RuntimeException('One or more products are unavailable.');
                }

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \RuntimeException(
                        "Insufficient stock for product: {$product->name} (available: {$product->stock_quantity})"
                    );
                }

                $subtotal += $product->selling_price * $item['quantity'];
            }

            $discount = min((float) ($data['discount'] ?? 0), $subtotal);
            $tax = (float) ($data['tax'] ?? 0);
            $total = round($subtotal - $discount + $tax, 2);

            $sale = Sale::create([
                'receipt_number' => $this->generateReceiptNumber(),
                'user_id' => $cashier->id,
                'subtotal' => round($subtotal, 2),
                'discount' => round($discount, 2),
                'tax' => round($tax, 2),
                'total' => $total,
                'status' => Sale::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($aggregated as $item) {
                $product = $products->get($item['product_id']);
                $lineSubtotal = round($product->selling_price * $item['quantity'], 2);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'unit_price' => $product->selling_price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $lineSubtotal,
                ]);
            }

            // Payment
            $method = $data['payment_method'];
            $tendered = isset($data['tendered']) ? (float) $data['tendered'] : null;
            $change = null;

            if ($method === 'cash') {
                if ($tendered === null || $tendered < $total) {
                    throw new \RuntimeException('Insufficient tendered amount for cash payment.');
                }
                $change = round($tendered - $total, 2);
            }

            Payment::create([
                'sale_id' => $sale->id,
                'method' => $method,
                'amount' => $total,
                'tendered' => $tendered,
                'change' => $change,
            ]);

            // Stock deduction (rows already locked above)
            $this->inventory->deductForSale($aggregated, $sale->receipt_number, $cashier);

            return $sale->load(['items', 'payment', 'user']);
        });
    }

    /**
     * Void a sale and restore stock inside a DB transaction.
     */
    public function void(Sale $sale, User $actor): Sale
    {
        if ($sale->status === Sale::STATUS_VOIDED) {
            throw new \RuntimeException('Sale is already voided.');
        }

        return DB::transaction(function () use ($sale, $actor) {
            $sale = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();

            if ($sale->status === Sale::STATUS_VOIDED) {
                throw new \RuntimeException('Sale is already voided.');
            }

            $items = $sale->items()
                ->get()
                ->map(fn (SaleItem $item) => [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ])
                ->filter(fn ($item) => $item['product_id'] !== null)
                ->values();

            if ($items->isNotEmpty()) {
                $this->inventory->restoreForVoid($items, $sale->receipt_number, $actor);
            }

            $sale->update([
                'status' => Sale::STATUS_VOIDED,
                'voided_by' => $actor->id,
                'voided_at' => now(),
            ]);

            return $sale->fresh(['items', 'payment', 'user', 'voidedBy']);
        });
    }

    public function generateReceiptNumber(): string
    {
        $prefix = 'RCP';
        $date = now()->format('Ymd');
        $sequence = Sale::whereDate('created_at', today())->count() + 1;

        do {
            $number = sprintf('%s-%s-%04d-%s', $prefix, $date, $sequence, Str::upper(Str::random(4)));
            $sequence++;
        } while (Sale::where('receipt_number', $number)->exists());

        return $number;
    }

    public function receipt(Sale $sale): array
    {
        return $this->receipt->build($sale);
    }
}
