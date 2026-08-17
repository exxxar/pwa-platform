<?php

namespace App\Console\Commands;

use App\Models\Tenant\Category;
use App\Models\Tenant\Ingredient;
use App\Models\Tenant\IngredientGroup;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductAttribute;
use App\Models\Tenant\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerateTestProducts extends Command
{
    protected $signature = 'products:generate-test-data
                        {--tenant= : Slug тенанта (обязательно)}
                        {--count=10 : Количество товаров для создания}
                        {--force : Без подтверждения}
                        {--with-categories : Создать тестовые категории}
                        {--composite-ratio=0.4 : Доля составных товаров (0.0 - 1.0)}';

    protected $description = 'Генерирует тестовые товары с ингредиентами, атрибутами и составными продуктами (ВСЕ товары имеют и то, и другое)';

    public function handle(): int
    {
        $tenantSlug = $this->option('tenant');
        $count = (int) $this->option('count');
        $force = $this->option('force');
        $withCategories = $this->option('with-categories');
        $compositeRatio = (float) $this->option('composite-ratio');

        if (!$tenantSlug) {
            $this->error('❌ Укажите slug тенанта через --tenant=');
            return Command::FAILURE;
        }

        $tenant = Tenant::where('slug', $tenantSlug)->first();
        if (!$tenant) {
            $this->error("❌ Тенант '{$tenantSlug}' не найден");
            return Command::FAILURE;
        }

        app()->instance('tenant', $tenant);

        $this->info("🏢 Тенант: {$tenant->name} (ID: {$tenant->id})");
        $this->info("📊 Количество товаров: {$count}");
        $this->info("🔗 Доля составных: " . ($compositeRatio * 100) . "%");
        $this->newLine();

        if (!$force && !$this->confirm("Создать {$count} тестовых товаров?", false)) {
            return Command::SUCCESS;
        }

        DB::beginTransaction();

        try {
            $stats = [
                'products' => 0,
                'composite_products' => 0,
                'regular_products' => 0,
                'ingredient_groups' => 0,
                'ingredients' => 0,
                'attributes' => 0,
                'categories' => 0,
                'components_links' => 0,
            ];

            // 1. Создаем тестовые категории
            $categories = [];
            if ($withCategories) {
                $categories = $this->createCategories($tenant, $stats);
            } else {
                $categories = Category::where('tenant_id', $tenant->id)->limit(3)->get();
            }

            // 2. Создаем ВСЕ товары (каждый получает ингредиенты и атрибуты)
            $this->info("📦 Создаем {$count} товаров с ингредиентами и атрибутами...");
            $allProducts = $this->createAllProducts($tenant, $count, $categories, $stats);

            // 3. Определяем какие товары станут составными (не первые 2, чтобы было на что ссылаться)
            $minProductsForComposite = 2;
            if (count($allProducts) >= $minProductsForComposite) {
                $compositeCount = max(1, (int) floor(count($allProducts) * $compositeRatio));

                // Берем случайные товары из второй половины (чтобы было на что ссылаться)
                $availableForComposite = array_slice($allProducts, $minProductsForComposite);
                $compositeCandidates = array_rand(
                    array_flip(array_keys($availableForComposite)),
                    min($compositeCount, count($availableForComposite))
                );

                if (!is_array($compositeCandidates)) {
                    $compositeCandidates = [$compositeCandidates];
                }

                $this->newLine();
                $this->info("🔗 Делаем " . count($compositeCandidates) . " товаров составными (добавляем компоненты)...");

                foreach ($compositeCandidates as $candidateIndex) {
                    $compositeProduct = $availableForComposite[$candidateIndex];
                    $this->addComponentsToProduct($compositeProduct, $allProducts, $tenant, $stats);
                }
            }

            DB::commit();

            $this->newLine();
            $this->info('📊 Статистика:');
            $this->table(
                ['Тип данных', 'Количество'],
                [
                    ['✅ Всего товаров', $stats['products']],
                    ['📦 Составных товаров', $stats['composite_products']],
                    ['📄 Обычных товаров', $stats['regular_products']],
                    ['🧪 Групп ингредиентов', $stats['ingredient_groups']],
                    ['🥘 Ингредиентов', $stats['ingredients']],
                    ['🏷️ Атрибутов', $stats['attributes']],
                    ['🔗 Связей компонентов', $stats['components_links']],
                    ['📁 Категорий', $stats['categories']],
                ]
            );

            // 🆕 Выводим список составных товаров
            $compositeProducts = Product::where('tenant_id', $tenant->id)
                ->where('is_composite', true)
                ->with(['components' => function($q) {
                    $q->select('products.id', 'products.name', 'products.price');
                }])
                ->get();

            if ($compositeProducts->isNotEmpty()) {
                $this->newLine();
                $this->info('📦 Составные товары:');
                $this->newLine();

                foreach ($compositeProducts as $composite) {
                    $componentsList = $composite->components->map(function($comp) {
                        $qty = $comp->pivot->quantity ?? 1;
                        return "{$comp->name} (x{$qty})";
                    })->implode(', ');

                    $this->line("  📦 <info>{$composite->name}</info> (ID: {$composite->id})");
                    $this->line("     └─ Состав: {$componentsList}");
                    $this->newLine();
                }
            }

            $this->info('🎉 Тестовые данные успешно созданы!');
            $this->comment('💡 Каждый товар имеет свои ингредиенты и атрибуты');
            $this->comment('💡 Составные товары также имеют свои модификаторы');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function createCategories(Tenant $tenant, array &$stats): array
    {
        $categoryNames = [
            'Пицца', 'Бургеры', 'Суши', 'Салаты', 'Напитки',
            'Десерты', 'Закуски', 'Супы', 'Паста', 'Завтраки'
        ];

        $categories = [];
        $this->info('📁 Создаем категории...');

        foreach ($categoryNames as $index => $name) {
            $category = Category::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'is_active' => true,
                'order_position' => $index,
            ]);
            $categories[] = $category;
            $stats['categories']++;
        }

        return $categories;
    }

    private function createAllProducts(Tenant $tenant, int $count, array $categories, array &$stats): array
    {
        $products = [];
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $productTemplates = [
            [
                'name' => 'Маргарита',
                'price' => 599,
                'old_price' => 699,
                'description' => 'Классическая итальянская пицца с томатным соусом, моцареллой и свежим базиликом',
                'sku' => 'PIZZA-MARG',
            ],
            [
                'name' => 'Пепперони',
                'price' => 699,
                'old_price' => null,
                'description' => 'Пикантная пицца с салями пепперони, моцареллой и томатным соусом',
                'sku' => 'PIZZA-PEP',
            ],
            [
                'name' => 'Чизбургер Классик',
                'price' => 349,
                'old_price' => 399,
                'description' => 'Сочная говяжья котлета, чеддер, салат, томат и фирменный соус',
                'sku' => 'BURG-CHZ',
            ],
            [
                'name' => 'Биг Бургер',
                'price' => 449,
                'old_price' => null,
                'description' => 'Двойная котлета, двойной сыр, бекон и специальный соус',
                'sku' => 'BURG-BIG',
            ],
            [
                'name' => 'Цезарь с курицей',
                'price' => 399,
                'old_price' => 449,
                'description' => 'Свежий салат романо, куриное филе гриль, пармезан, гренки и соус цезарь',
                'sku' => 'SAL-CZS',
            ],
            [
                'name' => 'Греческий салат',
                'price' => 329,
                'old_price' => null,
                'description' => 'Томаты, огурцы, перец, маслины, фета и оливковое масло',
                'sku' => 'SAL-GRK',
            ],
            [
                'name' => 'Кола 0.5л',
                'price' => 129,
                'old_price' => null,
                'description' => 'Освежающий газированный напиток',
                'sku' => 'DRK-COLA',
            ],
            [
                'name' => 'Тирамису',
                'price' => 299,
                'old_price' => 349,
                'description' => 'Классический итальянский десерт с маскарпоне и кофе',
                'sku' => 'DSR-TIR',
            ],
            [
                'name' => 'Паста Карбонара',
                'price' => 499,
                'old_price' => null,
                'description' => 'Спагетти с беконом, пармезаном и сливочным соусом',
                'sku' => 'PASTA-CARB',
            ],
            [
                'name' => 'Том Ям',
                'price' => 449,
                'old_price' => 499,
                'description' => 'Тайский острый суп с креветками и грибами',
                'sku' => 'SOUP-TOMYAM',
            ],
        ];

        for ($i = 0; $i < $count; $i++) {
            $template = $productTemplates[$i % count($productTemplates)];

            $name = $template['name'];
            if ($i >= count($productTemplates)) {
                $name .= ' #' . ($i + 1);
            }

            // 🆕 ВСЕ товары создаются как обычные (is_composite = false)
            // Позже некоторые станут составными
            $product = Product::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'price' => $template['price'],
                'old_price' => $template['old_price'],
                'description' => $template['description'],
                'sku' => $template['sku'] . '-' . Str::random(4),
                'is_active' => true,
                'in_stop_list' => false,
                'is_composite' => false, // Пока обычный
                'images' => [
                    [
                        'url' => 'https://picsum.photos/seed/' . Str::random(8) . '/400/400',
                        'name' => 'product.jpg',
                    ]
                ],
                'dimensions' => [
                    'width' => rand(20, 40),
                    'height' => rand(5, 15),
                    'length' => rand(20, 40),
                    'weight' => rand(200, 800),
                ],
            ]);

            // Привязываем к случайной категории
            if (!empty($categories)) {
                $randomCategory = $categories[array_rand($categories)];
                $product->categories()->attach($randomCategory->id);
            }

            // ✅ ВСЕ товары получают атрибуты
            $this->createProductAttributes($product, $stats);

            // ✅ ВСЕ товары получают ингредиенты (100%, а не 70%)
            $this->createIngredientGroups($product, $tenant, $stats);

            $products[] = $product;
            $stats['products']++;
            $stats['regular_products']++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $products;
    }

    private function addComponentsToProduct(Product $compositeProduct, array $allProducts, Tenant $tenant, array &$stats): void
    {
        // Помечаем как составной
        $compositeProduct->update(['is_composite' => true]);
        $stats['composite_products']++;
        $stats['regular_products']--; // Уменьшаем счетчик обычных

        // Выбираем 2-4 случайных компонента из ДРУГИХ товаров (не из самого себя)
        $availableProducts = array_filter($allProducts, fn($p) => $p->id !== $compositeProduct->id);

        if (count($availableProducts) < 2) {
            $this->warn("⚠️ Недостаточно товаров для компонентов (товар #{$compositeProduct->id})");
            return;
        }

        $componentCount = rand(2, min(4, count($availableProducts)));
        $selectedKeys = array_rand($availableProducts, $componentCount);

        if (!is_array($selectedKeys)) {
            $selectedKeys = [$selectedKeys];
        }

        foreach ($selectedKeys as $index => $key) {
            $componentProduct = $availableProducts[$key];
            $quantity = rand(1, 2);
            $isDefault = rand(1, 100) <= 70; // 70% компонентов выбраны по умолчанию

            $compositeProduct->components()->attach($componentProduct->id, [
                'quantity' => $quantity,
                'is_default' => $isDefault,
                'sort_order' => $index,
                'tenant_id' => $tenant->id,
            ]);
            $stats['components_links']++;
        }

        // 🆕 Добавляем СПЕЦИАЛЬНЫЕ ингредиенты для составных товаров
        // (упаковка, подарок, открытка и т.д.)
        $this->addCompositeSpecificIngredients($compositeProduct, $tenant, $stats);
    }

    private function addCompositeSpecificIngredients(Product $product, Tenant $tenant, array &$stats): void
    {
        // Специальные группы для составных товаров
        $compositeGroups = [
            [
                'name' => 'Упаковка',
                'ingredients' => [
                    ['name' => 'Стандартная коробка', 'extra_price' => 0, 'is_default' => true],
                    ['name' => 'Подарочная упаковка', 'extra_price' => 150, 'is_default' => false],
                    ['name' => 'Эко-упаковка', 'extra_price' => 50, 'is_default' => false],
                ],
            ],
            [
                'name' => 'Дополнительно',
                'ingredients' => [
                    ['name' => 'Открытка с поздравлением', 'extra_price' => 100, 'is_default' => false],
                    ['name' => 'Подарочный пакет', 'extra_price' => 80, 'is_default' => false],
                    ['name' => 'Лента подарочная', 'extra_price' => 50, 'is_default' => false],
                ],
            ],
            [
                'name' => 'Напиток в подарок',
                'ingredients' => [
                    ['name' => 'Без напитка', 'extra_price' => 0, 'is_default' => true],
                    ['name' => 'Кола 0.33л', 'extra_price' => 99, 'is_default' => false],
                    ['name' => 'Сок 0.33л', 'extra_price' => 120, 'is_default' => false],
                    ['name' => 'Вода 0.5л', 'extra_price' => 60, 'is_default' => false],
                ],
            ],
        ];

        // Добавляем 1-2 специальные группы к уже существующим
        $groupCount = rand(1, 2);
        $selectedGroups = array_rand($compositeGroups, $groupCount);

        if (!is_array($selectedGroups)) {
            $selectedGroups = [$selectedGroups];
        }

        // Получаем текущий максимальный sort_order
        $maxSortOrder = $product->ingredientGroups()->max('sort_order') ?? -1;

        foreach ($selectedGroups as $index => $groupIndex) {
            $template = $compositeGroups[$groupIndex];

            $group = IngredientGroup::create([
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'name' => $template['name'],
                'sort_order' => $maxSortOrder + $index + 1,
            ]);
            $stats['ingredient_groups']++;

            foreach ($template['ingredients'] as $ingIndex => $ingTemplate) {
                Ingredient::create([
                    'tenant_id' => $tenant->id,
                    'group_id' => $group->id,
                    'name' => $ingTemplate['name'],
                    'extra_price' => $ingTemplate['extra_price'],
                    'is_default' => $ingTemplate['is_default'],
                    'sort_order' => $ingIndex,
                ]);
                $stats['ingredients']++;
            }
        }
    }

    private function createProductAttributes(Product $product, array &$stats): void
    {
        $attributeSets = [
            [
                ['name' => 'Вес', 'value' => rand(200, 800) . 'г'],
                ['name' => 'Калорийность', 'value' => rand(200, 600) . ' ккал'],
                ['name' => 'Белки', 'value' => rand(10, 40) . 'г'],
                ['name' => 'Жиры', 'value' => rand(5, 30) . 'г'],
                ['name' => 'Углеводы', 'value' => rand(20, 60) . 'г'],
            ],
            [
                ['name' => 'Страна производства', 'value' => 'Россия'],
                ['name' => 'Срок годности', 'value' => rand(3, 30) . ' дней'],
                ['name' => 'Условия хранения', 'value' => 'При температуре +2...+6°C'],
            ],
            [
                ['name' => 'Размер', 'value' => ['S', 'M', 'L', 'XL'][rand(0, 3)]],
                ['name' => 'Цвет', 'value' => ['Красный', 'Зеленый', 'Синий', 'Желтый'][rand(0, 3)]],
            ],
        ];

        $attributes = $attributeSets[array_rand($attributeSets)];

        foreach ($attributes as $index => $attr) {
            ProductAttribute::create([
                'product_id' => $product->id,
                'name' => $attr['name'],
                'value' => $attr['value'],
                'section' => null,
                'order_position' => $index,
            ]);
            $stats['attributes']++;
        }
    }

    private function createIngredientGroups(Product $product, Tenant $tenant, array &$stats): void
    {
        $groupTemplates = [
            [
                'name' => 'Размер',
                'ingredients' => [
                    ['name' => 'Маленький', 'extra_price' => 0, 'is_default' => true],
                    ['name' => 'Средний', 'extra_price' => 100, 'is_default' => false],
                    ['name' => 'Большой', 'extra_price' => 200, 'is_default' => false],
                ],
            ],
            [
                'name' => 'Соус',
                'ingredients' => [
                    ['name' => 'Томатный', 'extra_price' => 0, 'is_default' => true],
                    ['name' => 'Сливочный', 'extra_price' => 50, 'is_default' => false],
                    ['name' => 'Барбекю', 'extra_price' => 50, 'is_default' => false],
                    ['name' => 'Чесночный', 'extra_price' => 50, 'is_default' => false],
                ],
            ],
            [
                'name' => 'Добавки',
                'ingredients' => [
                    ['name' => 'Двойной сыр', 'extra_price' => 100, 'is_default' => false],
                    ['name' => 'Бекон', 'extra_price' => 150, 'is_default' => false],
                    ['name' => 'Грибы', 'extra_price' => 80, 'is_default' => false],
                    ['name' => 'Оливки', 'extra_price' => 60, 'is_default' => false],
                    ['name' => 'Халапеньо', 'extra_price' => 40, 'is_default' => false],
                ],
            ],
            [
                'name' => 'Тесто',
                'ingredients' => [
                    ['name' => 'Тонкое', 'extra_price' => 0, 'is_default' => true],
                    ['name' => 'Толстое', 'extra_price' => 50, 'is_default' => false],
                    ['name' => 'Сырное', 'extra_price' => 100, 'is_default' => false],
                ],
            ],
        ];

        // Создаем 1-3 группы для товара
        $groupCount = rand(1, 3);
        $selectedGroups = array_rand($groupTemplates, $groupCount);

        if (!is_array($selectedGroups)) {
            $selectedGroups = [$selectedGroups];
        }

        foreach ($selectedGroups as $index => $groupIndex) {
            $template = $groupTemplates[$groupIndex];

            $group = IngredientGroup::create([
                'tenant_id' => $tenant->id,
                'product_id' => $product->id,
                'name' => $template['name'],
                'sort_order' => $index,
            ]);
            $stats['ingredient_groups']++;

            foreach ($template['ingredients'] as $ingIndex => $ingTemplate) {
                Ingredient::create([
                    'tenant_id' => $tenant->id,
                    'group_id' => $group->id,
                    'name' => $ingTemplate['name'],
                    'extra_price' => $ingTemplate['extra_price'],
                    'is_default' => $ingTemplate['is_default'],
                    'sort_order' => $ingIndex,
                ]);
                $stats['ingredients']++;
            }
        }
    }
}
