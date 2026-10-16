<?php

namespace App\Services\Commerce;

use App\Models\Customer;
use App\Models\Debt;
use App\Models\Invoice;
use App\Models\Shop;

class DebtService
{
    public function record(
        Shop $shop,
        Customer $customer,
        float $amount,
        string $type,
        ?Invoice $invoice = null,
        ?string $note = null,
        ?string $dueDate = null,
    ): Debt {
        $signedAmount = match ($type) {
            'payment' => -1 * abs($amount),
            default => abs($amount),
        };

        $balance = round(((float) $customer->balance_due) + $signedAmount, 2);

        $customer->forceFill([
            'balance_due' => $balance,
        ])->save();

        return Debt::query()->create([
            'shop_id' => $shop->id,
            'customer_id' => $customer->id,
            'invoice_id' => $invoice?->id,
            'type' => $type,
            'amount' => $signedAmount,
            'balance_after' => $balance,
            'due_date' => $dueDate,
            'note' => $note,
            'happened_at' => now(),
        ]);
    }
}
