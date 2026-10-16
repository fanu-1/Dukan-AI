<?php

namespace App\Services\Ai;

use App\Models\AiQueryLog;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class AiAssistantService
{
    public function __construct(
        private readonly AnthropicService $anthropic,
    ) {
    }

    public function answer(Shop $shop, ?User $user, string $question): array
    {
        $context = $this->buildContext($shop);
        $fallback = $this->buildFallbackAnswer($context, $question);
        $answer = $fallback;
        $status = 'fallback';
        $model = config('services.anthropic.model');

        if ($shop->ai_enabled && config('services.anthropic.key')) {
            try {
                $answer = $this->anthropic->ask($this->buildPrompt($shop, $context, $question));
                $status = 'completed';
            } catch (Throwable) {
                $status = 'fallback';
            }
        }

        AiQueryLog::query()->create([
            'shop_id' => $shop->id,
            'user_id' => $user?->id,
            'question' => $question,
            'answer' => $answer,
            'context' => $context,
            'provider' => 'anthropic',
            'model' => $model,
            'status' => $status,
            'asked_at' => now(),
        ]);

        return [
            'answer' => $answer,
            'status' => $status,
            'context' => $context,
        ];
    }

    private function buildContext(Shop $shop): array
    {
        $topProducts = Product::query()
            ->where('shop_id', $shop->id)
            ->orderBy('current_stock')
            ->limit(5)
            ->get(['name', 'current_stock', 'reorder_level']);

        $invoiceSummary = DB::table('invoices')
            ->where('shop_id', $shop->id)
            ->selectRaw('COUNT(*) as total_invoices, COALESCE(SUM(total), 0) as sales_total, COALESCE(SUM(due_amount), 0) as outstanding_total')
            ->first();

        $customerSummary = DB::table('customers')
            ->where('shop_id', $shop->id)
            ->selectRaw('COUNT(*) as total_customers, COALESCE(SUM(balance_due), 0) as total_balance_due')
            ->first();

        return [
            'shop_name' => $shop->name,
            'total_products' => Product::query()->where('shop_id', $shop->id)->count(),
            'low_stock' => $topProducts->filter(fn ($product) => $product->current_stock <= $product->reorder_level)->values(),
            'invoice_summary' => $invoiceSummary,
            'customer_summary' => $customerSummary,
        ];
    }

    private function buildPrompt(Shop $shop, array $context, string $question): string
    {
        return "You are an Urdu-speaking retail assistant for {$shop->name}. ".
            'Answer in simple Urdu, concise, and grounded only in the provided shop data. '.
            'If data is not enough, say so clearly. '.
            'Shop context: '.json_encode($context, JSON_UNESCAPED_UNICODE).'. '.
            'Question: '.$question;
    }

    private function buildFallbackAnswer(array $context, string $question): string
    {
        $lowStockNames = collect($context['low_stock'])->pluck('name')->implode(', ');
        $sales = number_format((float) data_get($context, 'invoice_summary.sales_total', 0), 2);
        $dues = number_format((float) data_get($context, 'customer_summary.total_balance_due', 0), 2);

        if (str_contains(mb_strtolower($question), 'stock')) {
            $count = $context['total_products'];
            $low = $lowStockNames !== '' ? $lowStockNames : 'abhi koi low-stock item nahi';

            return "Aap ki dukaan mein {$count} products tracked hain. Low stock items: {$low}.";
        }

        if (str_contains(mb_strtolower($question), 'udhaar') || str_contains(mb_strtolower($question), 'hisab')) {
            return "Total udhaar balance Rs. {$dues} hai.";
        }

        return "Aaj tak recorded sales Rs. {$sales} hain aur total udhaar Rs. {$dues} hai. Agar aap specific sawal poochhen, jaise 'kaun sa stock kam hai?', to main zyada seedha jawab doon ga.";
    }
}
