<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProductStoreRequest;
use App\Http\Requests\Api\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Commerce\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use InteractsWithShop;

    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $products = Product::query()
            ->where('shop_id', $this->shop($request)->id)
            ->latest('id')
            ->paginate(20);

        return ProductResource::collection($products)->response();
    }

    public function store(ProductStoreRequest $request): JsonResponse
    {
        $shop = $this->shop($request);

        $product = Product::query()->create([
            'shop_id' => $shop->id,
            'name' => $request->input('name'),
            'sku' => $request->input('sku'),
            'unit' => $request->input('unit', 'pcs'),
            'cost_price' => $request->input('cost_price', 0),
            'sale_price' => $request->input('sale_price', 0),
            'reorder_level' => $request->input('reorder_level', 0),
            'current_stock' => 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $openingStock = (float) $request->input('opening_stock', 0);

        if ($openingStock > 0) {
            $this->inventoryService->adjust(
                shop: $shop,
                product: $product,
                quantityChange: $openingStock,
                type: 'opening',
                user: $request->user(),
                note: 'Opening stock',
            );
            $product->refresh();
        }

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Product $product): ProductResource
    {
        $this->ensureBelongsToShop($request, $product);

        return new ProductResource($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): ProductResource
    {
        $this->ensureBelongsToShop($request, $product);
        $product->update($request->validated());

        return new ProductResource($product->fresh());
    }
}
