<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'quantity_before' => (float) $this->quantity_before,
            'quantity_change' => (float) $this->quantity_change,
            'quantity_after' => (float) $this->quantity_after,
            'note' => $this->note,
            'happened_at' => $this->happened_at,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
