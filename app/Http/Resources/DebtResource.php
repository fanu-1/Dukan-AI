<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'balance_after' => (float) $this->balance_after,
            'due_date' => $this->due_date,
            'note' => $this->note,
            'happened_at' => $this->happened_at,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
        ];
    }
}
