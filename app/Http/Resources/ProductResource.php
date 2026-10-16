<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'unit' => $this->unit,
            'cost_price' => (float) $this->cost_price,
            'sale_price' => (float) $this->sale_price,
            'reorder_level' => (float) $this->reorder_level,
            'current_stock' => (float) $this->current_stock,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
