<?php

namespace App\Services\Auth;

use App\Models\ApiToken;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Str;

class TokenService
{
    public function issue(User $user, Shop $shop, string $name = 'mobile'): array
    {
        $plainText = Str::random(64);

        $token = ApiToken::query()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'name' => $name,
            'token' => hash('sha256', $plainText),
            'abilities' => ['*'],
            'expires_at' => now()->addDays(30),
        ]);

        return [$token, $plainText];
    }

    public function revoke(ApiToken $token): void
    {
        $token->delete();
    }
}
