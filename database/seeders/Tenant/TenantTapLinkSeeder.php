<?php

namespace Database\Seeders\Tenant;


use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantTapLink;
use Illuminate\Database\Seeder;

class TenantTapLinkSeeder extends Seeder
{
    public function run()
    {
        // Берем первого попавшегося тенанта для теста
        $tenant = Tenant::first();

        if (!$tenant) {
            $this->command->info('Тенанты не найдены. Создайте хотя бы одного тенанта.');
            return;
        }

        $links = [
            [
                'title' => '📱 Наш Telegram-канал',
                'url' => 'https://t.me/durov',
                'icon' => 'fa-brands fa-telegram',
                'icon_bg' => '#0088cc',
                'sort_order' => 1,
            ],
            [
                'title' => '📸 Мы в Instagram',
                'url' => 'https://instagram.com',
                'icon' => 'fa-brands fa-instagram',
                'icon_bg' => '#E1306C',
                'sort_order' => 2,
            ],
            [
                'title' => '📍 Как нас найти (Яндекс Карты)',
                'url' => 'https://yandex.ru/maps',
                'icon' => 'fa-solid fa-location-dot',
                'icon_bg' => '#ff8a00',
                'sort_order' => 3,
            ],
            [
                'title' => '📞 Позвонить нам',
                'url' => 'tel:+79991234567',
                'icon' => 'fa-solid fa-phone',
                'icon_bg' => '#28a745',
                'sort_order' => 4,
            ],
        ];

        foreach ($links as $link) {
            TenantTapLink::create(array_merge($link, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));
        }

        $this->command->info('Taplink ссылки успешно добавлены для тенанта: ' . $tenant->name);
    }
}
