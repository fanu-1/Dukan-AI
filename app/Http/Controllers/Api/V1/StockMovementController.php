<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StockMovementStoreRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\Commerce\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class StockMovementController extends Controller
{
    use InteractsWithShop;

    public function __construct(
        private readonly InventoryService $inventoryService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $movements = StockMovement::query()
            ->with('product')
            ->where('shop_id', $this->shop($request)->id)
            ->latest('id')
            ->paginate(20);

        return StockMovementResource::collection($movements)->response();
    }

    public function store(StockMovementStoreRequest $request): JsonResponse
    {
        $product = Product::query()->findOrFail($request->integer('product_id'));
        $this->ensureBelongsToShop($request, $product);

        try {
            $movement = $this->inventoryService->adjust(
                shop: $this->shop($request),
                product: $product,
                quantityChange: (float) $request->input('quantity'),
                type: $request->input('type'),
                user: $request->user(),
                note: $request->input('note'),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return (new StockMovementResource($movement->load('product')))
            ->response()
            ->setStatusCode(201);
    }
}
