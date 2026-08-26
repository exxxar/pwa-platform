<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Partner;
use App\Models\Tenant\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PartnerSeeder extends Seeder
{
    /**
     * Конфигурация партнёров для Fastoran
     * ⚠️ Продукты и категории НЕ создаются. Только связь тенантов.
     */
    private array $partnersConfig = [
        [
            'slug' => 'cheburekmi',
            'title' => 'Чебурек ми',
            'description' => 'Сочные чебуреки и горячие напитки',
            'order_position' => 1,
            'image' => 'partners/cheburekmi.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'russian', 'rating' => 4.8],
        ],
        [
            'slug' => 'donmak-pl',
            'title' => 'Дон мак (Площадь)',
            'description' => 'Бургеры и картошка фри',
            'order_position' => 2,
            'image' => 'partners/donmak.png',
            'is_active' => true,
            'extra_charge' => 5,
            'config' => ['cuisine' => 'fastfood', 'rating' => 4.5],
        ],
        [
            'slug' => 'chillsushi',
            'title' => 'Чилл суши',
            'description' => 'Свежие роллы и суши с доставкой',
            'order_position' => 3,
            'image' => 'partners/chillsushi.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'asian', 'rating' => 4.7],
        ],
        [
            'slug' => 'big-shashlyk',
            'title' => 'Большой шашлык',
            'description' => 'Настоящий шашлык на углях',
            'order_position' => 4,
            'image' => 'partners/shashlyk.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'caucasian', 'rating' => 4.6],
        ],
        [
            'slug' => 'bigjohn',
            'title' => 'Большой Джон',
            'description' => 'Американские бургеры и стейки',
            'order_position' => 5,
            'image' => 'partners/bigjohn.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'american', 'rating' => 4.4],
        ],
        [
            'slug' => 'shaurmen',
            'title' => 'Шаурмен',
            'description' => 'Самая сочная шаурма в городе',
            'order_position' => 6,
            'image' => 'partners/shaurmen.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'street-food', 'rating' => 4.5],
        ],
        [
            'slug' => 'avkd-sushi',
            'title' => 'AVKD суши',
            'description' => 'Авторские роллы и сеты',
            'order_position' => 7,
            'image' => 'partners/avkd.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'asian', 'rating' => 4.6],
        ],
        [
            'slug' => 'polnaya-chasha',
            'title' => 'Полная чаша',
            'description' => 'Домашняя кухня и уютная атмосфера',
            'order_position' => 8,
            'image' => 'partners/chasha.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'russian', 'rating' => 4.8],
        ],
        [
            'slug' => 'donmak-kr',
            'title' => 'Дон мак (Крытый)',
            'description' => 'Фастфуд в торговом центре',
            'order_position' => 9,
            'image' => 'partners/donmak.png',
            'is_active' => true,
            'extra_charge' => 5,
            'config' => ['cuisine' => 'fastfood', 'rating' => 4.5],
        ],
        [
            'slug' => 'vuper-burgers',
            'title' => 'Вупер бургерс',
            'description' => 'Сочные бургеры из мраморной говядины',
            'order_position' => 10,
            'image' => 'partners/vuper.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'american', 'rating' => 4.7],
        ],
        [
            'slug' => 'labirint',
            'title' => 'Лабиринт',
            'description' => 'Квест-ресторан с необычной подачей',
            'order_position' => 11,
            'image' => 'partners/labirint.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'fusion', 'rating' => 4.9],
        ],
        [
            'slug' => 'shaurma-john',
            'title' => 'Шаурма от Джона',
            'description' => 'Фирменная шаурма по секретному рецепту',
            'order_position' => 12,
            'image' => 'partners/shaurma-john.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'street-food', 'rating' => 4.6],
        ],
        [
            'slug' => 'chacha-puri',
            'title' => 'Чача пури',
            'description' => 'Грузинская кухня: хинкали, хачапури, чача',
            'order_position' => 13,
            'image' => 'partners/chacha.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'georgian', 'rating' => 4.8],
        ],
        [
            'slug' => 'schastie-est',
            'title' => 'Счастье есть',
            'description' => 'Кондитерская и кофейня',
            'order_position' => 14,
            'image' => 'partners/schastie.png',
            'is_active' => true,
            'extra_charge' => 0,
            'config' => ['cuisine' => 'cafe', 'rating' => 4.9],
        ],
    ];

    public function run(): void
    {
        // 1. Находим главный тенант-агрегатор
        $mainTenant = Tenant::query()->where('slug', 'fastoran')->first();

        if (!$mainTenant) {
            $this->command->error('❌ Главный тенант "fastoran" не найден! Запустите сначала DatabaseSeeder.');
            return;
        }

        $this->command->info("🤝 Привязка партнеров к тенанту: {$mainTenant->name}");
        $this->command->info(str_repeat('─', 60));

        foreach ($this->partnersConfig as $config) {
            $this->linkPartner($mainTenant, $config);
        }

        $this->command->info(str_repeat('─', 60));
        $this->command->info("✅ Готово! Привязано " . count($this->partnersConfig) . " партнеров.");
        $this->command->info("💡 Примечание: Продукты и категории не создавались. Добавьте их вручную через админ-панель заведений.");
    }

    /**
     * Создание связи партнёра с главным тенантом
     */
    private function linkPartner(Tenant $mainTenant, array $config): void
    {
        // 1. Ищем или создаем тенант самого заведения-партнера
        $partnerTenant = Tenant::firstOrCreate(
            ['slug' => $config['slug']],
            [
                'uuid' => (string) Str::uuid(),
                'name' => $config['title'],
                'description' => $config['description'],
                'theme_color' => $this->getPartnerColor($config['slug']),
                'app_type' => 'partner',
                'is_active' => true,
            ]
        );

        // 2. Создаем или обновляем связь в таблице partners
        Partner::updateOrCreate(
            [
                'tenant_id' => $mainTenant->id,         // Fastoran
                'tenant_partner_id' => $partnerTenant->id, // Конкретное заведение
            ],
            [
                'title' => $config['title'],
                'description' => $config['description'],
                'order_position' => $config['order_position'],
                'image' => $config['image'],
                'is_active' => $config['is_active'],
                'extra_charge' => $config['extra_charge'],
                'config' => $config['config'],
            ]
        );

        $this->command->info("   ✓ {$config['title']} привязан к {$mainTenant->name}");
    }

    /**
     * Фирменный цвет для каждого партнёра (для UI)
     */
    private function getPartnerColor(string $slug): string
    {
        $colors = [
            'cheburekmi' => '#e67e22',
            'donmak-pl' => '#f1c40f',
            'donmak-kr' => '#f1c40f',
            'chillsushi' => '#e74c3c',
            'big-shashlyk' => '#d35400',
            'bigjohn' => '#2c3e50',
            'shaurmen' => '#e67e22',
            'avkd-sushi' => '#2c3e50',
            'polnaya-chasha' => '#8e44ad',
            'vuper-burgers' => '#e74c3c',
            'labirint' => '#34495e',
            'shaurma-john' => '#d35400',
            'chacha-puri' => '#e67e22',
            'schastie-est' => '#27ae60',
        ];

        return $colors[$slug] ?? '#667eea';
    }
}
