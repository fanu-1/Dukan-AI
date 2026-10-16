<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBackendTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_and_verify_otp(): void
    {
        $request = $this->postJson('/api/v1/auth/otp/request', [
            'phone' => '03001234567',
            'name' => 'Farhan',
            'shop_name' => 'DukanAI Test Shop',
        ]);

        $request->assertOk()
            ->assertJsonPath('debug_code', '123456');

        $verify = $this->postJson('/api/v1/auth/otp/verify', [
            'phone' => '03001234567',
            'code' => '123456',
        ]);

        $verify->assertOk()
            ->assertJsonPath('user.phone', '03001234567')
            ->assertJsonPath('shop.name', 'DukanAI Test Shop');

        $this->assertDatabaseHas('users', ['phone' => '03001234567']);
        $this->assertDatabaseCount('api_tokens', 1);
    }

    public function test_product_creation_and_manual_stock_update_work(): void
    {
        $headers = $this->authHeaders();

        $productResponse = $this->withHeaders($headers)->postJson('/api/v1/products', [
            'name' => 'Shan Masala',
            'sku' => 'SHAN-001',
            'sale_price' => 120,
            'cost_price' => 100,
            'reorder_level' => 3,
            'opening_stock' => 8,
        ]);

        $productResponse->assertCreated()
            ->assertJsonPath('current_stock', 8);

        $productId = $productResponse->json('id');

        $movementResponse = $this->withHeaders($headers)->postJson('/api/v1/stock-movements', [
            'product_id' => $productId,
            'type' => 'purchase',
            'quantity' => 5,
            'note' => 'Restock',
        ]);

        $movementResponse->assertCreated()
            ->assertJsonPath('quantity_after', 13);

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'current_stock' => 13,
        ]);
    }

    public function test_invoice_creation_updates_stock_and_customer_due(): void
    {
        [$headers, $shop] = $this->authHeadersWithShop();

        $customer = Customer::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Ali',
            'phone' => '03110000000',
        ]);

        $product = Product::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Milk Pack',
            'sku' => 'MILK-001',
            'unit' => 'pcs',
            'sale_price' => 250,
            'cost_price' => 220,
            'reorder_level' => 2,
            'current_stock' => 10,
        ]);

        $response = $this->withHeaders($headers)->postJson('/api/v1/invoices', [
            'customer_id' => $customer->id,
            'paid_amount' => 300,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 250,
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('total', 500)
            ->assertJsonPath('due_amount', 200)
            ->assertJsonPath('status', 'partial');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'current_stock' => 8,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'balance_due' => 200,
        ]);

        $this->assertDatabaseHas('debts', [
            'customer_id' => $customer->id,
            'type' => 'charge',
            'balance_after' => 200,
        ]);
    }

    public function test_dashboard_returns_summary(): void
    {
        [$headers, $shop] = $this->authHeadersWithShop();

        Product::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Tea',
            'sku' => 'TEA-1',
            'sale_price' => 100,
            'cost_price' => 80,
            'reorder_level' => 5,
            'current_stock' => 2,
        ]);

        Customer::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Noman',
            'balance_due' => 400,
        ]);

        $response = $this->withHeaders($headers)->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('summary.total_products', 1)
            ->assertJsonPath('summary.total_customers', 1)
            ->assertJsonPath('summary.outstanding_due', 400)
            ->assertJsonPath('summary.low_stock_count', 1);
    }

    private function authHeaders(): array
    {
        return $this->authHeadersWithShop()[0];
    }

    private function authHeadersWithShop(): array
    {
        $user = User::factory()->create();
        $shop = Shop::query()->create([
            'name' => 'Test Shop',
            'slug' => 'test-shop',
            'owner_user_id' => $user->id,
            'plan' => 'pro',
            'ai_enabled' => true,
        ]);

        $user->shops()->attach($shop->id, ['role' => 'owner', 'is_active' => true]);
        $user->update(['current_shop_id' => $shop->id]);

        $plainToken = 'plain-token-for-tests';

        ApiToken::query()->create([
            'user_id' => $user->id,
            'shop_id' => $shop->id,
            'name' => 'test',
            'token' => hash('sha256', $plainToken),
            'abilities' => ['*'],
            'expires_at' => now()->addDay(),
        ]);

        return [[
            'Authorization' => 'Bearer '.$plainToken,
            'Accept' => 'application/json',
        ], $shop];
    }
}
