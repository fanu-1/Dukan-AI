<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\ProductResource;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $shop = $this->shop($request);

        $products = Product::query()->where('shop_id', $shop->id);
        $customers = Customer::query()->where('shop_id', $shop->id);
        $invoices = Invoice::query()->where('shop_id', $shop->id);
        $lowStock = Product::query()
            ->where('shop_id', $shop->id)
            ->whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->limit(10)
            ->get();

        return response()->json([
            'summary' => [
                'total_products' => $products->count(),
                'total_customers' => $customers->count(),
                'total_sales' => (float) $invoices->sum('total'),
                'outstanding_due' => (float) $customers->sum('balance_due'),
                'low_stock_count' => $lowStock->count(),
            ],
            'low_stock_products' => ProductResource::collection($lowStock),
            'recent_customers' => CustomerResource::collection(
                $customers->latest('id')->limit(5)->get()
            ),
        ]);
    }
}
