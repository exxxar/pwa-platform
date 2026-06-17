<?php

namespace Database\Seeders;

use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestDataSeeder extends Seeder
{
    public function run()
    {
        $tenant = Tenant::query()
            ->where("slug","test")
            ->first();

        // --- Categories ---
        $categories = collect([
            ['name' => 'Овощи', 'icon' => '🥕'],
            ['name' => 'Фрукты', 'icon' => '🍎'],
            ['name' => 'Молочные продукты', 'icon' => '🥛'],
            ['name' => 'Мясо', 'icon' => '🍖'],
            ['name' => 'Бакалея', 'icon' => '🍞'],
        ])->map(function ($item, $i) use($tenant) {
            return DB::table('categories')->insertGetId([
                'tenant_id' => $tenant->id,
                'name' => $item['name'],
                'icon' => $item['icon'],
                'order_position' => $i,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // --- Products ---
        $products = collect(range(1, 10))->map(function ($i) use ($tenant) {
            return DB::table('products')->insertGetId([
                'tenant_id' =>  $tenant->id,
                'name' => "Тестовый продукт $i",
                'price' => rand(100, 900) / 10,
                'old_price' => rand(100, 900) / 10,
                'sku' => 'SKU-' . Str::upper(Str::random(6)),
                'description' => 'Описание тестового продукта.',
                'delivery_terms' => 'Доставка в течение 24 часов.',
                'images' => json_encode([
                    "https://picsum.photos/seed/product{$i}/400/400"
                ]),
                'config' => json_encode([
                    'color' => fake()->safeColorName(),
                    'package' => 'default'
                ]),
                'dimensions' => json_encode([
                    'weight' => rand(1, 5) . ' кг',
                    'width' => rand(10, 30) . ' см',
                    'height' => rand(10, 30) . ' см',
                ]),
                'external_source' => 'test_source',
                'external_id' => Str::uuid(),
                'is_active' => true,
                'not_for_delivery' => false,
                'in_stop_list' => false,
                'is_weight_product' => (bool)rand(0, 1),
                'order_position' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        // --- Product ↔ Categories ---
        foreach ($products as $productId) {
            DB::table('product_categories')->insert([
                'product_id' => $productId,
                'category_id' => $categories->random(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // --- Product Attributes ---
        foreach ($products as $productId) {
            DB::table('product_attributes')->insert([
                [
                    'product_id' => $productId,
                    'name' => 'Состав',
                    'section' => 'Информация',
                    'value' => 'Вода, соль, специи',
                    'order_position' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'product_id' => $productId,
                    'name' => 'Срок годности',
                    'section' => 'Информация',
                    'value' => '12 месяцев',
                    'order_position' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }
}
