<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InvoiceStoreRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Services\Commerce\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    use InteractsWithShop;

    public function __construct(
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::query()
            ->with(['customer', 'items'])
            ->where('shop_id', $this->shop($request)->id)
            ->latest('id')
            ->paginate(20);

        return InvoiceResource::collection($invoices)->response();
    }

    public function store(InvoiceStoreRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->create(
                shop: $this->shop($request),
                user: $request->user(),
                payload: $request->validated(),
            );
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return (new InvoiceResource($invoice))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Invoice $invoice): InvoiceResource
    {
        $this->ensureBelongsToShop($request, $invoice);

        return new InvoiceResource($invoice->load(['customer', 'items']));
    }
}
