<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use App\Support\ShopContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json([
                'message' => 'Authentication token is missing.',
            ], 401);
        }

        $token = ApiToken::query()
            ->with(['user', 'shop'])
            ->where('token', hash('sha256', $plainToken))
            ->first();

        if (! $token || ($token->expires_at && $token->expires_at->isPast())) {
            return response()->json([
                'message' => 'Authentication token is invalid or expired.',
            ], 401);
        }

        $token->forceFill([
            'last_used_at' => now(),
        ])->save();

        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('current_shop', $token->shop);
        $request->attributes->set('api_token', $token);

        app()->instance(ShopContext::class, new ShopContext($token->user, $token->shop));

        return $next($request);
    }
}
