<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Sale::query()
            ->with(['user:id,name', 'items', 'payment'])
            ->latest();

        $query->search($request->query('search'));

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Cashiers only see their own sales
        if ($request->user()->isCashier()) {
            $query->where('user_id', $request->user()->id);
        }

        $sales = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $sale = Sale::with(['user:id,name', 'voidedBy:id,name', 'items', 'payment'])->findOrFail($id);

        if ($request->user()->isCashier() && $sale->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            $sale = $this->saleService->checkout($request->validated(), $request->user());
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale completed successfully.',
            'data' => $sale,
        ], 201);
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);
        $sale = Sale::findOrFail($id);

        if ($request->user()->isCashier()) {
            return response()->json([
                'success' => false,
                'message' => 'Cashiers cannot void sales.',
            ], 403);
        }

        try {
            $sale = $this->saleService->void($sale, $request->user(), $validated['reason']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Sale voided successfully. Stock has been restored.',
            'data' => $sale,
        ]);
    }
}
