<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DebtStoreRequest;
use App\Http\Resources\DebtResource;
use App\Models\Customer;
use App\Models\Debt;
use App\Services\Commerce\DebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    use InteractsWithShop;

    public function __construct(
        private readonly DebtService $debtService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $debts = Debt::query()
            ->with('customer')
            ->where('shop_id', $this->shop($request)->id)
            ->latest('id')
            ->paginate(20);

        return DebtResource::collection($debts)->response();
    }

    public function store(DebtStoreRequest $request): JsonResponse
    {
        $customer = Customer::query()->findOrFail($request->integer('customer_id'));
        $this->ensureBelongsToShop($request, $customer);

        $debt = $this->debtService->record(
            shop: $this->shop($request),
            customer: $customer,
            amount: (float) $request->input('amount'),
            type: $request->input('type'),
            note: $request->input('note'),
            dueDate: $request->input('due_date'),
        );

        return (new DebtResource($debt->load('customer')))
            ->response()
            ->setStatusCode(201);
    }
}
