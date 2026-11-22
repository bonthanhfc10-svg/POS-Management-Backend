<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dailySales(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $query = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        if ($request->user()->isCashier()) {
            $query->where('user_id', $request->user()->id);
        }

        $rows = (clone $query)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COALESCE(SUM(discount), 0) as discounts')
            ->selectRaw('COALESCE(SUM(tax), 0) as taxes')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'summary' => [
                    'sales_count' => $rows->sum('sales_count'),
                    'revenue' => $rows->sum('revenue'),
                    'discounts' => $rows->sum('discounts'),
                    'taxes' => $rows->sum('taxes'),
                ],
                'rows' => $rows,
            ],
        ]);
    }

    public function revenue(Request $request): JsonResponse
    {
        $days = min($request->integer('days', 30), 365);
        $from = now()->subDays($days - 1)->toDateString();

        $query = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDate('created_at', '>=', $from);

        if ($request->user()->isCashier()) {
            $query->where('user_id', $request->user()->id);
        }

        $rows = (clone $query)
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw('COALESCE(SUM(total), 0) as revenue')
            ->selectRaw('COUNT(*) as sales_count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from,
                'days' => $days,
                'total_revenue' => $rows->sum('revenue'),
                'rows' => $rows,
            ],
        ]);
    }

    public function topProducts(Request $request): JsonResponse
    {
        $limit = min($request->integer('limit', 10), 100);
        $from = $request->query('from');
        $to = $request->query('to');

        $query = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', Sale::STATUS_COMPLETED);

        if ($request->user()->isCashier()) {
            $query->where('sales.user_id', $request->user()->id);
        }

        if ($from) {
            $query->whereDate('sales.created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('sales.created_at', '<=', $to);
        }

        $rows = $query
            ->selectRaw('sale_items.product_id, sale_items.product_name, sale_items.product_sku')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity')
            ->selectRaw('SUM(sale_items.subtotal) as total_revenue')
            ->groupBy('sale_items.product_id', 'sale_items.product_name', 'sale_items.product_sku')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
    }

    public function cashierSales(Request $request): JsonResponse
    {
        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        $rows = Sale::query()
            ->where('status', Sale::STATUS_COMPLETED)
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to)
            ->join('users', 'users.id', '=', 'sales.user_id')
            ->selectRaw('sales.user_id, users.name as cashier_name')
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('COALESCE(SUM(sales.total), 0) as revenue')
            ->groupBy('sales.user_id', 'users.name')
            ->orderByDesc('revenue')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'rows' => $rows,
            ],
        ]);
    }
}
