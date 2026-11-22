<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustInventoryRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('category:id,name');

        $query->search($request->query('search'));

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $products = $query->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $query = StockMovement::query()
            ->with(['product:id,name,sku', 'user:id,name'])
            ->latest();

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $movements = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $movements,
        ]);
    }

    public function adjust(AdjustInventoryRequest $request): JsonResponse
    {
        try {
            $movement = DB::transaction(function () use ($request) {
                $product = Product::whereKey($request->validated('product_id'))
                    ->lockForUpdate()
                    ->firstOrFail();

                $quantity = (int) $request->validated('quantity');

                if ($quantity < 0 && $product->stock_quantity + $quantity < 0) {
                    throw new \RuntimeException('Insufficient stock for this adjustment.');
                }

                return $this->inventoryService->adjust(
                    $product,
                    $quantity,
                    $request->user(),
                    $request->validated('notes')
                );
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory adjusted successfully.',
            'data' => $movement->load('product:id,name,sku,stock_quantity'),
        ], 201);
    }
}
