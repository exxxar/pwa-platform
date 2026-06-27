<?php

namespace Database\Seeders;

use App\Models\Tenant\Category;
use App\Models\Tenant\Partner;
use App\Models\Tenant\Product;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PartnerSeeder extends Seeder
{
    /**
     * Конфигурация партнёров-общепитов
     */
    private array $partnersConfig = [
        // ==========================================
        // 🍕 DODO PIZZA
        // ==========================================
        [
            'slug' => 'dodo-pizza',
            'title' => 'Dodo Pizza',
            'description' => 'Пицца на тонком тесте с доставкой за 30 минут',
            'order_position' => 1,
            'image' => 'partners/dodo.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => [
                'api_enabled' => true,
                'delivery_time_min' => 30,
                'cuisine' => 'italian',
                'rating' => 4.7,
            ],
            'legal_info' => [
                'company' => 'Dodo Brands LLC',
                'bin' => '7736677554',
            ],
            'categories' => [
                [
                    'title' => '🍕 Пицца',
                    'products' => [
                        ['name' => 'Пепперони', 'price' => 599, 'old_price' => 699, 'description' => 'Острая пепперони, моцарелла, томатный соус, 30 см'],
                        ['name' => 'Маргарита', 'price' => 499, 'description' => 'Томаты, моцарелла, свежий базилик, томатный соус, 30 см'],
                        ['name' => '4 сыра', 'price' => 699, 'description' => 'Моцарелла, чеддер, пармезан, дорблю, сливочный соус, 30 см'],
                        ['name' => 'Цыплёнок Барбекю', 'price' => 649, 'description' => 'Цыплёнок, бекон, соус BBQ, красный лук, моцарелла, 30 см'],
                        ['name' => 'Додо Микс', 'price' => 749, 'description' => 'Пепперони, бекон, цыплёнок, маринованные огурцы, моцарелла, 35 см'],
                        ['name' => 'Карбонара', 'price' => 679, 'description' => 'Бекон, цыплёнок, моцарелла, соус карбонара, пармезан, 30 см'],
                    ],
                ],
                [
                    'title' => '🥖 Закуски',
                    'products' => [
                        ['name' => 'Куриные крылья 6 шт', 'price' => 299, 'description' => 'Острые крылышки с соусом на выбор'],
                        ['name' => 'Сырные палочки', 'price' => 249, 'description' => 'Хрустящие палочки с тянущимся сыром'],
                        ['name' => 'Картофель по-деревенски', 'price' => 199, 'description' => 'Запечённый картофель со специями'],
                        ['name' => 'Хлебные палочки', 'price' => 149, 'description' => 'С чесночным соусом'],
                    ],
                ],
                [
                    'title' => '🥤 Напитки',
                    'products' => [
                        ['name' => 'Coca-Cola 0.5л', 'price' => 129, 'description' => 'Газированный напиток'],
                        ['name' => 'Морс клюквенный 0.5л', 'price' => 159, 'description' => 'Натуральный морс из клюквы'],
                        ['name' => 'Вода BonAqua 0.5л', 'price' => 99, 'description' => 'Негазированная питьевая вода'],
                    ],
                ],
                [
                    'title' => '🍰 Десерты',
                    'products' => [
                        ['name' => 'Чизкейк Нью-Йорк', 'price' => 249, 'description' => 'Классический чизкейк с ягодным соусом'],
                        ['name' => 'Тирамису', 'price' => 279, 'description' => 'Итальянский десерт с маскарпоне и кофе'],
                    ],
                ],
            ],
        ],

        // ==========================================
        // 🍔 BURGER KING
        // ==========================================
        [
            'slug' => 'burger-king',
            'title' => 'Burger King',
            'description' => 'Бургеры на открытом огне с 1954 года',
            'order_position' => 2,
            'image' => 'partners/burgerking.png',
            'is_active' => true,
            'extra_charge' => 5,
            'config' => [
                'api_enabled' => true,
                'delivery_time_min' => 25,
                'cuisine' => 'american',
                'rating' => 4.5,
            ],
            'legal_info' => [
                'company' => 'Burger King Rus LLC',
                'bin' => '7704789880',
            ],
            'categories' => [
                [
                    'title' => '🍔 Бургеры',
                    'products' => [
                        ['name' => 'Воппер', 'price' => 349, 'description' => 'Говяжья котлета на гриле, томаты, салат, майонез, маринованные огурцы'],
                        ['name' => 'Двойной Воппер', 'price' => 449, 'old_price' => 499, 'description' => 'Две говяжьи котлеты на гриле, двойной сыр, соус Воппер'],
                        ['name' => 'Чизбургер', 'price' => 149, 'description' => 'Говяжья котлета, ломтик сыра, горчица, кетчуп, лук'],
                        ['name' => 'Кинг Наггетс (9 шт)', 'price' => 229, 'description' => 'Куриные наггетсы в хрустящей панировке'],
                        ['name' => 'Лонг Чикен', 'price' => 279, 'description' => 'Куриное филе в панировке, салат, майонез, длинная булочка'],
                        ['name' => 'Биг Кинг', 'price' => 399, 'description' => 'Две говяжьи котлеты, два ломтика сыра, соус Биг Кинг'],
                    ],
                ],
                [
                    'title' => '🍟 Гарниры',
                    'products' => [
                        ['name' => 'Картофель Фри (стандарт)', 'price' => 139, 'description' => 'Золотистый картофель в фирменной панировке'],
                        ['name' => 'Картофель Фри (большой)', 'price' => 179, 'description' => 'Большая порция фирменного картофеля'],
                        ['name' => 'Луковые кольца', 'price' => 149, 'description' => 'Хрустящие луковые кольца в панировке'],
                    ],
                ],
                [
                    'title' => '🥤 Напитки',
                    'products' => [
                        ['name' => 'Coca-Cola 0.5л', 'price' => 119, 'description' => 'Классический газированный напиток'],
                        ['name' => 'Fanta апельсиновая 0.5л', 'price' => 119, 'description' => 'Газированный напиток со вкусом апельсина'],
                        ['name' => 'Молочный коктейль Ваниль', 'price' => 169, 'description' => 'Нежный коктейль с натуральным ванильным вкусом'],
                        ['name' => 'Кофе Американо', 'price' => 129, 'description' => 'Классический чёрный кофе'],
                    ],
                ],
                [
                    'title' => '🍦 Десерты',
                    'products' => [
                        ['name' => 'Мороженое Королевское', 'price' => 99, 'description' => 'Мягкое мороженое с топпингом на выбор'],
                        ['name' => 'Пирожок с вишней', 'price' => 79, 'description' => 'Хрустящий пирожок с вишнёвой начинкой'],
                    ],
                ],
            ],
        ],

        // ==========================================
        // 🍗 KFC
        // ==========================================
        [
            'slug' => 'kfc',
            'title' => 'KFC',
            'description' => 'Цыплёнок по оригинальному рецепту полковника Сандерса',
            'order_position' => 3,
            'image' => 'partners/kfc.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => [
                'api_enabled' => true,
                'delivery_time_min' => 35,
                'cuisine' => 'american',
                'rating' => 4.6,
            ],
            'legal_info' => [
                'company' => 'KFC Russia',
                'bin' => '7704844455',
            ],
            'categories' => [
                [
                    'title' => '🍗 Баскеты',
                    'products' => [
                        ['name' => 'Баскет Дуэт', 'price' => 549, 'description' => '4 оригинальных стрипса, 2 мини-ролла, 2 соуса, картофель фри'],
                        ['name' => 'Баскет Полковника', 'price' => 899, 'old_price' => 999, 'description' => '8 стрипсов, 4 крыла, 2 соуса, большой картофель фри'],
                        ['name' => 'Шеф Баскет', 'price' => 1199, 'description' => '12 стрипсов, 6 крыльев, 3 соуса, 3 картофеля фри'],
                    ],
                ],
                [
                    'title' => '🍗 Курица',
                    'products' => [
                        ['name' => 'Оригинальный стрипс', 'price' => 129, 'description' => 'Куриное филе в оригинальной панировке из 11 специй'],
                        ['name' => 'Крылышки 3 шт', 'price' => 199, 'description' => 'Острые куриные крылышки'],
                        ['name' => 'Куриные ножки 2 шт', 'price' => 229, 'description' => 'Сочные куриные ножки в панировке'],
                        ['name' => 'Шеф Ролл', 'price' => 249, 'description' => 'Стрипс, салат, томат, соус в тортилье'],
                    ],
                ],
                [
                    'title' => '🍔 Сандвичи',
                    'products' => [
                        ['name' => 'Шефбургер Оригинальный', 'price' => 249, 'description' => 'Куриное филе в панировке, салат, майонез, булочка'],
                        ['name' => 'Шефбургер Де Люкс', 'price' => 329, 'old_price' => 379, 'description' => 'Двойное куриное филе, двойной сыр, соус Шефбургер'],
                        ['name' => 'Чизбургер', 'price' => 179, 'description' => 'Куриная котлета, ломтик сыра, салат, соус'],
                        ['name' => 'Лонгер', 'price' => 219, 'description' => 'Длинный сандвич с куриным филе и салатом'],
                    ],
                ],
                [
                    'title' => '🍟 Гарниры',
                    'products' => [
                        ['name' => 'Картофель Фри (стандарт)', 'price' => 129, 'description' => 'Золотистый картофель'],
                        ['name' => 'Картофель по-деревенски', 'price' => 149, 'description' => 'Запечённый картофель со специями'],
                        ['name' => 'Соус сырный', 'price' => 49, 'description' => 'Нежный сырный соус'],
                        ['name' => 'Соус барбекю', 'price' => 49, 'description' => 'Пикантный соус барбекю'],
                    ],
                ],
                [
                    'title' => '🥤 Напитки',
                    'products' => [
                        ['name' => 'Pepsi 0.5л', 'price' => 119, 'description' => 'Газированный напиток'],
                        ['name' => '7UP 0.5л', 'price' => 119, 'description' => 'Освежающий лимонад'],
                        ['name' => 'Морс 0.5л', 'price' => 139, 'description' => 'Натуральный ягодный морс'],
                    ],
                ],
            ],
        ],

        // ==========================================
        // ☕ ШОКОЛАДНИЦА
        // ==========================================
        [
            'slug' => 'shokoladnica',
            'title' => 'Шоколадница',
            'description' => 'Уютная кофейня с авторскими десертами',
            'order_position' => 4,
            'image' => 'partners/shokoladnica.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => [
                'api_enabled' => false,
                'delivery_time_min' => 45,
                'cuisine' => 'cafe',
                'rating' => 4.8,
            ],
            'legal_info' => [
                'company' => 'Шоколадница Group',
                'bin' => '7707720777',
            ],
            'categories' => [
                [
                    'title' => '☕ Кофе',
                    'products' => [
                        ['name' => 'Эспрессо', 'price' => 149, 'description' => 'Классический крепкий кофе, 30 мл'],
                        ['name' => 'Американо', 'price' => 179, 'description' => 'Эспрессо с горячей водой, 200 мл'],
                        ['name' => 'Капучино', 'price' => 229, 'description' => 'Эспрессо с нежной молочной пеной, 250 мл'],
                        ['name' => 'Латте', 'price' => 249, 'old_price' => 289, 'description' => 'Мягкий кофе с большим количеством молока, 300 мл'],
                        ['name' => 'Раф ванильный', 'price' => 279, 'description' => 'Кофе со сливками и ванильным сиропом, 300 мл'],
                        ['name' => 'Флэт Уайт', 'price' => 259, 'description' => 'Двойной эспрессо с бархатистым молоком, 200 мл'],
                        ['name' => 'Какао', 'price' => 239, 'description' => 'Горячий шоколадный напиток с молоком, 300 мл'],
                    ],
                ],
                [
                    'title' => '🥐 Завтраки',
                    'products' => [
                        ['name' => 'Сырники со сметаной', 'price' => 349, 'description' => 'Домашние сырники со сметаной и ягодным соусом'],
                        ['name' => 'Каша овсяная с ягодами', 'price' => 249, 'description' => 'Овсяная каша с сезонными ягодами и мёдом'],
                        ['name' => 'Омлет с ветчиной', 'price' => 389, 'description' => 'Пышный омлет с ветчиной, сыром и томатами'],
                        ['name' => 'Блинчики с Nutella', 'price' => 329, 'description' => 'Тонкие блинчики с шоколадной пастой и бананом'],
                    ],
                ],
                [
                    'title' => '🍰 Десерты',
                    'products' => [
                        ['name' => 'Чизкейк Нью-Йорк', 'price' => 349, 'description' => 'Классический американский чизкейк'],
                        ['name' => 'Тирамису', 'price' => 389, 'description' => 'Итальянский десерт с маскарпоне и кофе'],
                        ['name' => 'Наполеон', 'price' => 299, 'description' => 'Слоёный торт с заварным кремом'],
                        ['name' => 'Медовик', 'price' => 319, 'old_price' => 369, 'description' => 'Домашний медовый торт со сметанным кремом'],
                        ['name' => 'Эклер с кремом', 'price' => 199, 'description' => 'Заварное пирожное с ванильным кремом'],
                    ],
                ],
                [
                    'title' => '🥪 Сэндвичи',
                    'products' => [
                        ['name' => 'Клубный сэндвич', 'price' => 389, 'description' => 'Курица, бекон, яйцо, салат, томат в трёхслойном бутерброде'],
                        ['name' => 'Сэндвич с лососем', 'price' => 449, 'description' => 'Слабосолёный лосось, сливочный сыр, руккола'],
                        ['name' => 'Круассан с ветчиной', 'price' => 279, 'description' => 'Свежий круассан с ветчиной и сыром'],
                    ],
                ],
            ],
        ],

        // ==========================================
        // 🍣 ТАНУКИ
        // ==========================================
        [
            'slug' => 'tanuki',
            'title' => 'Тануки',
            'description' => 'Сеть ресторанов паназиатской кухни',
            'order_position' => 5,
            'image' => 'partners/tanuki.png',
            'is_active' => true,
            'extra_charge' => 7.5,
            'config' => [
                'api_enabled' => true,
                'delivery_time_min' => 50,
                'cuisine' => 'asian',
                'rating' => 4.6,
            ],
            'legal_info' => [
                'company' => 'Тануки Group',
                'bin' => '7728844556',
            ],
            'categories' => [
                [
                    'title' => '🍣 Роллы',
                    'products' => [
                        ['name' => 'Филадельфия Классик', 'price' => 590, 'description' => 'Лосось, сливочный сыр, огурец, нори, рис'],
                        ['name' => 'Филадельфия Де Люкс', 'price' => 790, 'old_price' => 890, 'description' => 'Двойной лосось, сливочный сыр, авокадо, икра тобико'],
                        ['name' => 'Калифорния', 'price' => 520, 'description' => 'Краб, авокадо, огурец, икра тобико, майонез'],
                        ['name' => 'Дракон', 'price' => 690, 'description' => 'Копчёный угорь, авокадо, огурец, соус унаги, кунжут'],
                        ['name' => 'Запечённый с лососем', 'price' => 640, 'description' => 'Лосось, сливочный сыр, запечённый соус, кунжут'],
                        ['name' => 'Вулкан', 'price' => 720, 'description' => 'Креветка темпура, спайси соус, тобико, зелёный лук'],
                    ],
                ],
                [
                    'title' => '🍜 Супы',
                    'products' => [
                        ['name' => 'Том Ям с морепродуктами', 'price' => 490, 'description' => 'Тайский острый суп с креветками, мидиями, грибами'],
                        ['name' => 'Рамен с курицей', 'price' => 420, 'description' => 'Японский суп с лапшой, курицей, яйцом, нори'],
                        ['name' => 'Мисо суп', 'price' => 249, 'description' => 'Классический японский суп с тофу, вакаме, луком'],
                    ],
                ],
                [
                    'title' => '🍱 Горячие блюда',
                    'products' => [
                        ['name' => 'Удон с курицей', 'price' => 449, 'description' => 'Толстая лапша удон, курица терияки, овощи вок'],
                        ['name' => 'Фунчоза с морепродуктами', 'price' => 520, 'description' => 'Стеклянная лапша, тигровые креветки, мидии, овощи'],
                        ['name' => 'Рис с говядиной', 'price' => 489, 'description' => 'Рис жасмин, говядина терияки, овощи, кунжут'],
                        ['name' => 'Лапша Соба с уткой', 'price' => 549, 'old_price' => 620, 'description' => 'Гречневая лапша, утка, грибы шиитаке, соус понзу'],
                    ],
                ],
                [
                    'title' => '🥟 Дим-самы',
                    'products' => [
                        ['name' => 'Пельмени с свининой (6 шт)', 'price' => 349, 'description' => 'Домашние пельмени на пару с свининой и имбирём'],
                        ['name' => 'Чикен Дим-сам (6 шт)', 'price' => 329, 'description' => 'Паровые пельмени с курицей и овощами'],
                        ['name' => 'Бао с свининой (3 шт)', 'price' => 389, 'description' => 'Паровые булочки бао с свининой барбекю'],
                    ],
                ],
                [
                    'title' => '🍵 Напитки',
                    'products' => [
                        ['name' => 'Зелёный чай Сенча', 'price' => 199, 'description' => 'Классический японский зелёный чай'],
                        ['name' => 'Матча Латте', 'price' => 299, 'description' => 'Японский чай матча с молоком'],
                        ['name' => 'Лимонад с личи', 'price' => 249, 'description' => 'Освежающий лимонад с фруктом личи'],
                        ['name' => 'Саке (50 мл)', 'price' => 399, 'description' => 'Японская рисовая водка'],
                    ],
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $tenant = Tenant::query()
            ->where('slug', 'test')
            ->first();

        if (!$tenant) {
            $this->command->error('❌ Тенант с slug "test" не найден!');
            return;
        }

        $this->command->info("🤝 Создание партнёров-общепитов для тенанта: {$tenant->name}");
        $this->command->info(str_repeat('─', 60));

        $totalProducts = 0;

        foreach ($this->partnersConfig as $config) {
            $count = $this->createPartner($tenant, $config);
            $totalProducts += $count;
        }

        $this->command->info(str_repeat('─', 60));
        $this->command->info("✅ Готово! Создано " . count($this->partnersConfig) . " партнёров, {$totalProducts} товаров.");
    }

    /**
     * Создание партнёра с товарами
     */
    private function createPartner(Tenant $tenant, array $config): int
    {
        $this->command->info("🏪 {$config['title']} — {$config['description']}");

        // Создаём тенант-партнёр (для товаров)
        $partnerTenant = Tenant::updateOrCreate(
            ['slug' => $config['slug']],
            [
                'uuid'=>Str::uuid(),
                'name' => $config['title'],
                'description' => $config['description'],
                'theme_color' => $this->getPartnerColor($config['slug']),
                'app_type' => 'partner',
            ]
        );

        // Создаём запись партнёра
        $partner = Partner::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'tenant_partner_id' => $partnerTenant->id,
            ],
            [
                'title' => $config['title'],
                'description' => $config['description'],
                'order_position' => $config['order_position'],
                'image' => $config['image'],
                'is_active' => $config['is_active'],
                'extra_charge' => $config['extra_charge'],
                'config' => $config['config'],
                'legal_info' => $config['legal_info'],
            ]
        );

        // Создаём категории и товары
        $productsCount = $this->createCategoriesAndProducts($partnerTenant, $config['categories'] ?? []);

        $this->command->info("   ✓ Создано {$productsCount} товаров в " . count($config['categories']) . " категориях");

        return $productsCount;
    }

    /**
     * Создание категорий и товаров для партнёра
     */
    private function createCategoriesAndProducts(Tenant $partnerTenant, array $categories): int
    {
        $productsCount = 0;

        foreach ($categories as $index => $categoryData) {
            $category = Category::updateOrCreate(
                [
                    'tenant_id' => $partnerTenant->id,
                    'name' => $categoryData['title'],
                ],
                [
                    //'description' => "Категория {$categoryData['title']}",
                    'is_active' => true,
                    'order_position' => $index,
                ]
            );

            foreach ($categoryData['products'] as $productIndex => $productData) {
                $product = Product::updateOrCreate(
                    [
                        'tenant_id' => $partnerTenant->id,
                        'name' => $productData['name'],
                    ],
                    [
                        'price' => $productData['price'],
                        'old_price' => $productData['old_price'] ?? null,
                        'description' => $productData['description'] ?? null,
                        'sku' => 'SKU-' . Str::upper(Str::random(8)),
                        'is_active' => true,
                        'not_for_delivery' => false,
                        'in_stop_list' => false,
                        'is_weight_product' => false,
                        'order_position' => $productIndex,
                        'images' => [],
                        'config' => [],
                        'dimensions' => null,
                        'delivery_terms' => null,
                        'external_source' => 'partner',
                        'external_id' => null,
                    ]
                );

                // ✅ Привязка через pivot (syncWithoutDetaching не дублирует)
                $product->categories()->syncWithoutDetaching([$category->id]);

                $productsCount++;
            }
        }

        return $productsCount;
    }
    /**
     * Фирменный цвет для каждого партнёра
     */
    private function getPartnerColor(string $slug): string
    {
        $colors = [
            'dodo-pizza' => '#ff3f3f',
            'burger-king' => '#ff8732',
            'kfc' => '#e4002b',
            'shokoladnica' => '#6f4e37',
            'tanuki' => '#1a1a1a',
        ];

        return $colors[$slug] ?? '#667eea';
    }
}
