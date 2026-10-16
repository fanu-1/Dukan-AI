<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\ShopResource;
use App\Http\Resources\UserResource;
use App\Models\Shop;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly OtpService $otpService,
        private readonly TokenService $tokenService,
    ) {
    }

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        $otp = $this->otpService->create($request->string('phone')->toString(), [
            'name' => $request->input('name'),
            'shop_name' => $request->input('shop_name'),
        ]);

        return response()->json([
            'message' => 'OTP generated successfully.',
            'expires_at' => $otp->expires_at,
            'debug_code' => app()->environment(['local', 'testing']) ? $otp->code : null,
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $otp = $this->otpService->verify(
            $request->string('phone')->toString(),
            $request->string('code')->toString(),
        );

        abort_if(! $otp, 422, 'OTP is invalid or expired.');

        $user = User::query()->firstOrCreate(
            ['phone' => $otp->phone],
            [
                'name' => data_get($otp->meta, 'name', 'Dukan User'),
                'phone_verified_at' => now(),
            ],
        );

        if (! $user->phone_verified_at) {
            $user->forceFill(['phone_verified_at' => now()])->save();
        }

        $shop = $user->currentShop;

        if (! $shop) {
            $shopName = data_get($otp->meta, 'shop_name') ?: $user->name.' Store';

            $shop = Shop::query()->create([
                'name' => $shopName,
                'slug' => Str::slug($shopName).'-'.Str::lower(Str::random(5)),
                'owner_user_id' => $user->id,
                'plan' => 'free',
                'ai_enabled' => false,
                'settings' => [
                    'currency' => 'PKR',
                    'locale' => 'ur_PK',
                    'low_stock_default' => 5,
                ],
            ]);

            $shop->users()->attach($user->id, [
                'role' => 'owner',
                'is_active' => true,
            ]);

            $user->forceFill([
                'current_shop_id' => $shop->id,
            ])->save();
        }

        [$token, $plainTextToken] = $this->tokenService->issue($user, $shop, 'mobile');

        return response()->json([
            'message' => 'Authenticated successfully.',
            'token' => $plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $token->expires_at,
            'user' => new UserResource($user),
            'shop' => new ShopResource($shop),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('currentShop');

        return response()->json([
            'user' => new UserResource($user),
            'shop' => new ShopResource($request->attributes->get('current_shop')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->tokenService->revoke($request->attributes->get('api_token'));

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }
}
