<?php

namespace App\Support;

use App\Models\Shop;
use App\Models\User;

class ShopContext
{
    public function __construct(
        public readonly User $user,
        public readonly Shop $shop,
    ) {
    }
}
