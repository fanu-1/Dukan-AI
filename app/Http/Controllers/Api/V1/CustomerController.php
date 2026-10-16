<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerStoreRequest;
use App\Http\Requests\Api\CustomerUpdateRequest;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\DebtResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use InteractsWithShop;

    public function index(Request $request): JsonResponse
    {
        $customers = Customer::query()
            ->where('shop_id', $this->shop($request)->id)
            ->latest('id')
            ->paginate(20);

        return CustomerResource::collection($customers)->response();
    }

    public function store(CustomerStoreRequest $request): JsonResponse
    {
        $customer = Customer::query()->create([
            'shop_id' => $this->shop($request)->id,
            ...$request->validated(),
        ]);

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Customer $customer): CustomerResource
    {
        $this->ensureBelongsToShop($request, $customer);

        return new CustomerResource($customer);
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): CustomerResource
    {
        $this->ensureBelongsToShop($request, $customer);
        $customer->update($request->validated());

        return new CustomerResource($customer->fresh());
    }

    public function ledger(Request $request, Customer $customer): JsonResponse
    {
        $this->ensureBelongsToShop($request, $customer);

        return response()->json([
            'customer' => new CustomerResource($customer),
            'ledger' => DebtResource::collection(
                $customer->debts()->latest('id')->get()
            ),
        ]);
    }
}
