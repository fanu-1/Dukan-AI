<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Models\Shop;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

trait InteractsWithShop
{
    protected function shop(Request $request): Shop
    {
        return $request->attributes->get('current_shop');
    }

    protected function ensureBelongsToShop(Request $request, Model $model): void
    {
        if (! isset($model->shop_id) || $model->shop_id !== $this->shop($request)->id) {
            throw new NotFoundHttpException();
        }
    }
}
