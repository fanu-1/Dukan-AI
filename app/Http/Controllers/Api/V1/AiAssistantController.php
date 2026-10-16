<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\InteractsWithShop;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AiQueryRequest;
use App\Services\Ai\AiAssistantService;
use Illuminate\Http\JsonResponse;

class AiAssistantController extends Controller
{
    use InteractsWithShop;

    public function __construct(
        private readonly AiAssistantService $assistantService,
    ) {
    }

    public function store(AiQueryRequest $request): JsonResponse
    {
        $result = $this->assistantService->answer(
            shop: $this->shop($request),
            user: $request->user(),
            question: $request->input('question'),
        );

        return response()->json($result);
    }
}
