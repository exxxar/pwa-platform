<?php

namespace App\Services\Agent;


use App\Models\Agent\Agent;
use App\Models\Agent\AgentClient;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantPermission;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgentTenantService
{
    /**
     * Получить приложения агента с фильтрами
     */
    public function getAgentTenants(Agent $agent, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Tenant::where('agent_id', $agent->id);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Привязать приложение к клиенту
     */
    public function assignClient(Tenant $tenant, AgentClient $client): void
    {
        $tenant->update([
            'agent_id'  => $client->agent_id,
            'client_id' => $client->id,
        ]);
    }

    /**
     * Подсчитать доход по приложению (через транзакции)
     */
    public function calculateTenantEarnings(Tenant $tenant): float
    {
        return (float) $tenant->transactions()->sum('amount');
    }

    /**
     * Создать тенанта на основе данных калькулятора
     */
    public function createFromCalculator(Agent $agent, array $calculatorData): Tenant
    {
        return DB::transaction(function () use ($agent, $calculatorData) {
            $formData = $calculatorData['formData'];

            // 1. Определяем название и slug
            $businessName = $this->getBusinessName($formData['businessType']);
            $tenantName = $formData['customName'] ?: ($businessName . ' App');
            $slug = Str::slug($tenantName . '-' . Str::random(6));

            // 2. Базовая конфигурация meta (из вашей команды)
            $baseMeta = [
                'sbp' => [
                    'sber' => [],
                    'tinkoff' => ['tax' => 'osn', 'vat' => 'none', 'terminal_key' => '1739195568603', 'terminal_password' => '_tou#gb2VC6so^Vt'],
                    'selected_sbp_bank' => 'tinkoff',
                ],
                'icons' => [
                    ['slug' => 'profile', 'title' => 'Профиль', 'has_icon' => true, 'image_url' => 'profile.png', 'is_visible' => true],
                    ['slug' => 'shop', 'title' => 'Заказать Доставку', 'has_icon' => true, 'image_url' => 'shop.png', 'is_visible' => true],
                    ['slug' => 'basket', 'title' => 'Корзина', 'has_icon' => true, 'image_url' => 'basket.png', 'is_visible' => true],
                    ['slug' => 'history', 'title' => 'История заказов', 'has_icon' => true, 'image_url' => 'history.png', 'is_visible' => true],
                ],
                'manager' => ['link' => 'https://t.me/EgorShipilov', 'title' => 'Написать'],
                'min_price' => 800,
                'can_use_sbp' => true,
                'shop_coords' => '45.070734, 39.037108',
                'price_per_km' => 70,
                'min_base_delivery_price' => 100,
                // Сохраняем данные калькулятора прямо в meta для истории
                'calculator_quote' => [
                    'total_price' => $calculatorData['total'],
                    'monthly_price' => $calculatorData['monthly'],
                    'features' => $formData['features'],
                    'integrations' => $formData['integrations'],
                    'design' => $formData['design'],
                    'deadline' => $formData['deadline'],
                ],
                'payment_plan' => [
                    'plan_id' => $formData['paymentPlan'],
                    'plan_price' => $calculatorData['planPrice'],
                    'plan_balance' => $calculatorData['planBalance'],
                    'activated_at' => now()->toIso8601String(),
                ],
            ];

            $initialBalance = $calculatorData['planBalance'] ?? 100;

            $tenant = Tenant::create([
                'uuid' => (string) Str::uuid(),
                'slug' => $slug,
                'name' => $tenantName,
                'description' => 'Приложение для: ' . $formData['clientName'],
                'agent_id' => $agent->id,
                'app_type' => 'partner',
                'order_channel' => 'web',
                'balance' => $initialBalance, // 🆕 Зависит от выбранного тарифа
                'meta' => $baseMeta,
                'is_active' => true,
                'welcome_message' => 'Добро пожаловать!',
            ]);

            // 4. Обустраиваем тенанта (Права -> Роль -> Админ)
            $this->provisionTenant($tenant);

            // 5. Обновляем счетчики агента
            $agent->increment('tenant_count');

            return $tenant;
        });
    }

    /**
     * Вспомогательный метод для получения названия бизнеса
     */
    private function getBusinessName(string $type): string
    {
        $map = [
            'coffee' => 'Кофейня', 'shop' => 'Магазин', 'beauty' => 'Салон красоты',
            'fitness' => 'Фитнес-клуб', 'hotel' => 'Отель', 'auto' => 'Автосервис',
            'delivery' => 'Доставка', 'other' => 'Бизнес'
        ];
        return $map[$type] ?? 'Приложение';
    }

    /**
     * Настройка прав, ролей и админа (адаптировано из CreateTenantCommand)
     */
    private function provisionTenant(Tenant $tenant): void
    {
        $permissionsMap = config('permissions.map', []);
        $permissionIds = [];

        foreach ($permissionsMap as $name => $label) {
            $perm = TenantPermission::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['label' => $label]
            );
            $permissionIds[] = $perm->id;
        }

        $role = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'super_admin'],
            ['label' => 'Суперадмин']
        );

        $permissionsSyncData = [];
        foreach ($permissionIds as $permId) {
            $permissionsSyncData[$permId] = ['tenant_id' => $tenant->id];
        }
        $role->permissions()->sync($permissionsSyncData);

        $safeSlug = Str::slug($tenant->slug ?: $tenant->name, '_');
        $adminEmail = "admin_{$safeSlug}@mypwa.ru";

        $existingAdmin = TenantUser::where('tenant_id', $tenant->id)->where('email', $adminEmail)->first();

        if (!$existingAdmin) {
            $adminUser = TenantUser::create([
                'tenant_id' => $tenant->id,
                'uuid' => (string) Str::uuid(),
                'name' => 'Администратор',
                'email' => $adminEmail,
                'phone' => '+79990000000',
                'password' => bcrypt('admin123'),
                'is_active' => true,
                'is_vip' => true,
                'referral_code' => Str::upper(Str::random(8)),
            ]);
            $adminUser->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
        }
    }
}
