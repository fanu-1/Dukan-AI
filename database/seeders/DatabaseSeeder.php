<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $owner = User::factory()->create([
            'name' => 'Demo Shop Owner',
            'phone' => '03001234567',
        ]);

        $shop = Shop::query()->create([
            'name' => 'DukanAI Demo Store',
            'slug' => 'dukanai-demo-store',
            'owner_user_id' => $owner->id,
            'plan' => 'pro',
            'ai_enabled' => true,
            'settings' => [
                'currency' => 'PKR',
                'locale' => 'ur_PK',
                'low_stock_default' => 5,
            ],
        ]);

        $owner->shops()->attach($shop->id, ['role' => 'owner', 'is_active' => true]);
        $owner->update(['current_shop_id' => $shop->id]);

        Product::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Surf Excel',
            'sku' => 'SURF-1KG',
            'unit' => 'pcs',
            'cost_price' => 780,
            'sale_price' => 850,
            'reorder_level' => 5,
            'current_stock' => 12,
        ]);

        Product::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Tapal Danedar',
            'sku' => 'TAPAL-475',
            'unit' => 'pcs',
            'cost_price' => 920,
            'sale_price' => 995,
            'reorder_level' => 4,
            'current_stock' => 3,
        ]);

        Customer::query()->create([
            'shop_id' => $shop->id,
            'name' => 'Ali Raza',
            'phone' => '03111222333',
            'credit_limit' => 15000,
            'balance_due' => 2500,
        ]);
    }
}
