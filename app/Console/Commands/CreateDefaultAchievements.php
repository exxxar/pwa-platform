<?php

namespace App\Console\Commands;

use App\Models\Tenant\Achievement;
use App\Models\Tenant\Tenant;
use Illuminate\Console\Command;

class CreateDefaultAchievements extends Command
{
    protected $signature = 'achievements:create-default {tenant_id?}';
    protected $description = 'Создать стандартные достижения для тенанта';

    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        if ($tenantId) {
            $tenants = [Tenant::find($tenantId)];
        } else {
            $tenants = Tenant::all();
        }

        foreach ($tenants as $tenant) {
            $this->info("Создание достижений для тенанта #{$tenant->id}");

            $achievements = [
                [
                    'title' => 'Первый заказ',
                    'description' => 'Сделайте свой первый заказ',
                    'icon' => 'fa-solid fa-shopping-bag',
                    'condition_type' => 'orders_count',
                    'condition_value' => 1,
                    'reward_type' => 'cashback',
                    'reward_value' => 100,
                ],
                [
                    'title' => 'Постоянный клиент',
                    'description' => 'Сделайте 10 заказов',
                    'icon' => 'fa-solid fa-star',
                    'condition_type' => 'orders_count',
                    'condition_value' => 10,
                    'reward_type' => 'cashback',
                    'reward_value' => 500,
                ],
                [
                    'title' => 'Шопоголик',
                    'description' => 'Сделайте 50 заказов',
                    'icon' => 'fa-solid fa-trophy',
                    'condition_type' => 'orders_count',
                    'condition_value' => 50,
                    'reward_type' => 'cashback',
                    'reward_value' => 2000,
                ],
                [
                    'title' => 'Критик',
                    'description' => 'Оставьте 5 отзывов',
                    'icon' => 'fa-solid fa-comment',
                    'condition_type' => 'reviews_count',
                    'condition_value' => 5,
                    'reward_type' => 'cashback',
                    'reward_value' => 200,
                ],
                [
                    'title' => 'Исследователь',
                    'description' => 'Просмотрите 100 товаров',
                    'icon' => 'fa-solid fa-eye',
                    'condition_type' => 'products_viewed',
                    'condition_value' => 100,
                    'reward_type' => 'cashback',
                    'reward_value' => 150,
                ],
                [
                    'title' => 'Игрок',
                    'description' => 'Сыграйте 10 игр',
                    'icon' => 'fa-solid fa-gamepad',
                    'condition_type' => 'games_played',
                    'condition_value' => 10,
                    'reward_type' => 'cashback',
                    'reward_value' => 300,
                ],
                [
                    'title' => 'Миллионер',
                    'description' => 'Потратьте 100 000 ₽',
                    'icon' => 'fa-solid fa-money-bill-wave',
                    'condition_type' => 'orders_sum',
                    'condition_value' => 100000,
                    'reward_type' => 'cashback',
                    'reward_value' => 5000,
                ],
            ];

            foreach ($achievements as $index => $achievement) {
                Achievement::create([
                    'tenant_id' => $tenant->id,
                    'title' => $achievement['title'],
                    'description' => $achievement['description'],
                    'icon' => $achievement['icon'],
                    'condition_type' => $achievement['condition_type'],
                    'condition_value' => $achievement['condition_value'],
                    'reward_type' => $achievement['reward_type'],
                    'reward_value' => $achievement['reward_value'],
                    'is_active' => true,
                    'sort_order' => $index,
                ]);
            }

            $this->info("✅ Создано " . count($achievements) . " достижений");
        }

        $this->info("🎉 Готово!");
    }
}
