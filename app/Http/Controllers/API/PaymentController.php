<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private ReceiptService $receiptService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Sale::query()
            ->whereHas('payment')
            ->with(['payment', 'user:id,name'])
            ->latest();

        if ($method = $request->query('method')) {
            $query->whereHas('payment', fn ($q) => $q->where('method', $method));
        }

        if ($from = $request->query('from')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->query('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($request->user()->isCashier()) {
            $query->where('user_id', $request->user()->id);
        }

        $payments = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    public function receipt(Request $request, int $saleId): JsonResponse
    {
        $sale = Sale::findOrFail($saleId);

        if ($request->user()->isCashier() && $sale->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->receiptService->build($sale),
        ]);
    }
}
