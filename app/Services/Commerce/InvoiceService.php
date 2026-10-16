<?php

namespace App\Services\Commerce;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvoiceService
{
    public function __construct(
        private readonly InventoryService $inventoryService,
        private readonly DebtService $debtService,
    ) {
    }

    public function create(Shop $shop, User $user, array $payload): Invoice
    {
        return DB::transaction(function () use ($shop, $user, $payload) {
            $customer = null;

            if ($payload['customer_id'] ?? null) {
                $customer = Customer::query()
                    ->where('shop_id', $shop->id)
                    ->findOrFail($payload['customer_id']);
            }

            $itemsPayload = collect($payload['items'] ?? []);

            if ($itemsPayload->isEmpty()) {
                throw new InvalidArgumentException('Invoice must contain at least one item.');
            }

            $subtotal = 0;

            $invoice = Invoice::query()->create([
                'shop_id' => $shop->id,
                'customer_id' => $customer?->id,
                'user_id' => $user->id,
                'invoice_number' => $this->nextInvoiceNumber($shop),
                'subtotal' => 0,
                'discount' => (float) ($payload['discount'] ?? 0),
                'total' => 0,
                'paid_amount' => (float) ($payload['paid_amount'] ?? 0),
                'due_amount' => 0,
                'status' => 'paid',
                'issued_at' => now(),
                'notes' => $payload['notes'] ?? null,
                'meta' => Arr::only($payload, ['channel']),
            ]);

            foreach ($itemsPayload as $line) {
                /** @var Product $product */
                $product = Product::query()
                    ->where('shop_id', $shop->id)
                    ->findOrFail($line['product_id']);

                $quantity = (float) $line['quantity'];
                $unitPrice = array_key_exists('unit_price', $line)
                    ? (float) $line['unit_price']
                    : (float) $product->sale_price;
                $lineTotal = round($quantity * $unitPrice, 2);
                $subtotal += $lineTotal;

                $invoice->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $this->inventoryService->adjust(
                    shop: $shop,
                    product: $product,
                    quantityChange: -1 * $quantity,
                    type: 'sale',
                    user: $user,
                    note: 'Invoice '.$invoice->invoice_number,
                    referenceType: 'invoice',
                    referenceId: $invoice->id,
                );
            }

            $discount = (float) $invoice->discount;
            $total = max(round($subtotal - $discount, 2), 0);
            $paidAmount = min((float) $invoice->paid_amount, $total);
            $dueAmount = round($total - $paidAmount, 2);
            $status = $dueAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $invoice->forceFill([
                'subtotal' => $subtotal,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'due_amount' => $dueAmount,
                'status' => $status,
            ])->save();

            if ($customer && $dueAmount > 0) {
                $this->debtService->record(
                    shop: $shop,
                    customer: $customer,
                    amount: $dueAmount,
                    type: 'charge',
                    invoice: $invoice,
                    note: 'Invoice '.$invoice->invoice_number,
                    dueDate: $payload['due_date'] ?? null,
                );
            }

            return $invoice->load(['items', 'customer', 'user']);
        });
    }

    private function nextInvoiceNumber(Shop $shop): string
    {
        $count = Invoice::query()->where('shop_id', $shop->id)->count() + 1;

        return 'INV-'.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
