<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()->with('category:id,name');

        $query->search($request->query('search'));

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        $sortBy = in_array($request->query('sort_by'), ['name', 'selling_price', 'stock_quantity', 'created_at'], true)
            ? $request->query('sort_by')
            : 'created_at';
        $sortDir = $request->query('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $products = $query->orderBy($sortBy, $sortDir)
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $product->load('category:id,name'),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with('category:id,name')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product->fresh('category:id,name'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        if ($product->stock_quantity > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete product with remaining stock. Adjust stock to zero first.',
            ], 422);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }
}
