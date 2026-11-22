<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SystemSetting;

class ReceiptService
{
    /**
     * Build a receipt payload for a sale.
     *
     * @return array<string, mixed>
     */
    public function build(Sale $sale): array
    {
        $sale->loadMissing(['items', 'payment', 'user']);

        $storeName = SystemSetting::getValue('store_name', config('app.name'));
        $storeAddress = SystemSetting::getValue('store_address');
        $storePhone = SystemSetting::getValue('store_phone');
        $currency = SystemSetting::getValue('currency', 'USD');
        $taxRate = SystemSetting::getValue('tax_rate', 0);

        return [
            'receipt_number' => $sale->receipt_number,
            'date' => $sale->created_at->toDateTimeString(),
            'status' => $sale->status,
            'store' => [
                'name' => $storeName,
                'address' => $storeAddress,
                'phone' => $storePhone,
            ],
            'cashier' => [
                'id' => $sale->user?->id,
                'name' => $sale->user?->name,
            ],
            'items' => $sale->items->map(fn ($item) => [
                'product_name' => $item->product_name,
                'product_sku' => $item->product_sku,
                'unit_price' => $item->unit_price,
                'quantity' => $item->quantity,
                'subtotal' => $item->subtotal,
            ])->values()->all(),
            'totals' => [
                'currency' => $currency,
                'subtotal' => $sale->subtotal,
                'discount' => $sale->discount,
                'tax' => $sale->tax,
                'total' => $sale->total,
            ],
            'payment' => $sale->payment ? [
                'method' => $sale->payment->method,
                'amount' => $sale->payment->amount,
                'tendered' => $sale->payment->tendered,
                'change' => $sale->payment->change,
            ] : null,
        ];
    }
}
