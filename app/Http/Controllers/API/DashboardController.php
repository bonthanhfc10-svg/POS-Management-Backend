<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = today();

        $salesQuery = Sale::query()->where('status', Sale::STATUS_COMPLETED);

        if ($user->isCashier()) {
            $salesQuery->where('user_id', $user->id);
        }

        $todaySales = (clone $salesQuery)->whereDate('created_at', $today);

        $data = [
            'today' => [
                'sales_count' => (clone $todaySales)->count(),
                'revenue' => (clone $todaySales)->sum('total'),
            ],
            'totals' => [
                'sales_count' => (clone $salesQuery)->count(),
                'revenue' => (clone $salesQuery)->sum('total'),
                'products' => Product::count(),
                'categories' => Category::count(),
                'users' => User::count(),
            ],
            'low_stock_products' => Product::query()
                ->lowStock()
                ->orderBy('stock_quantity')
                ->limit(10)
                ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']),
            'recent_sales' => (clone $salesQuery)
                ->with('user:id,name')
                ->latest()
                ->limit(5)
                ->get(['id', 'receipt_number', 'total', 'status', 'user_id', 'created_at']),
        ];

        if ($user->isAdmin() || $user->isManager()) {
            $data['totals']['inventory_value'] = DB::table('products')
                ->whereNull('deleted_at')
                ->selectRaw('COALESCE(SUM(cost_price * stock_quantity), 0) as value')
                ->value('value');
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
