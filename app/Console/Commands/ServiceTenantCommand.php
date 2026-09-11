<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantPermission;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;

class ServiceTenantCommand extends Command
{
    protected $signature = 'tenant:service {type : Тип сервисного тенанта (agents, delivery)}';
    protected $description = 'Создание или сброс настроек сервисного тенанта (агенты, доставщики) с генерацией суперадмина';

    public function handle()
    {
        $type = $this->argument('type');
        $allowedTypes = ['agents', 'delivery'];

        if (!in_array($type, $allowedTypes)) {
            $this->error("❌ Недопустимый тип. Разрешенные значения: " . implode(', ', $allowedTypes));
            return Command::FAILURE;
        }

        // 1. Конфигурация в зависимости от типа
        $config = match ($type) {
            'agents' => [
                'slug' => 'agents',
                'name' => 'Панель Агентов',
                'description' => 'Система для создания новых тенантов и их курирования',
                'super_role_name' => 'senior_agent',
                'super_admin' => 'Старший агент',
                'admin' => 'agent',
                'sub_role_label' => 'Агент',
                'meta' => [
                    'is_service_tenant' => true,
                    'can_create_tenants' => true,
                ]
            ],
            'delivery' => [
                'slug' => 'delivery',
                'name' => 'Служба Доставки',
                'description' => 'Система управления курьерами, заказами и логистикой',
                'super_role_name' => 'senior_delivery',
                'super_admin' => 'Старший доставщик',
                'admin' => 'deliveryman',
                'sub_role_label' => 'Доставщик',
                'meta' => [
                    'is_service_tenant' => true,
                    'delivery_management' => true,
                ]
            ]
        };

        $this->info("🔍 Подготовка сервисного тенанта: {$config['slug']}");

        // 2. Проверка на существование
        $existingTenant = Tenant::where('slug', $config['slug'])->first();
        $isUpdate = $existingTenant !== null;

        if ($isUpdate) {
            $this->warn("⚠️ Тенант '{$config['slug']}' уже существует.");
            if (!$this->confirm("Хотите обновить его настройки и СБРОСИТЬ пароль суперадмина?")) {
                $this->info("Операция отменена.");
                return Command::SUCCESS;
            }
        }

        // 3. Минимальные данные тенанта (остальное подтянется из конфигов приложения)
        $tenantData = [
            'uuid' => (string)Str::uuid(),
            'slug' => $config['slug'],
            'name' => $config['name'],
            'description' => $config['description'],
            'app_type' => 'service', // Отличаем от 'partner'
            'balance' => 0,
            'meta' => $config['meta'],
            'is_active' => true,
            'theme_color' => $type === 'agents' ? '#8b5cf6' : '#f59e0b', // Фиолетовый для агентов, оранжевый для доставки
        ];

        $this->info("💾 Сохранение данных тенанта...");
        $tenant = Tenant::withoutEvents(function () use ($config, $tenantData) {
            return Tenant::updateOrCreate(
                ['slug' => $config['slug']],
                $tenantData
            );
        });

        // 4. Настройка прав и ролей
        $this->provisionServiceTenant($tenant, $config);

        // 5. Вывод результатов
        $this->newLine();
        $this->info("✅ Сервисный тенант успешно " . ($isUpdate ? 'обновлен' : 'создан') . "!");

        $this->table(
            ['Параметр', 'Значение'],
            [
                ['ID', $tenant->id],
                ['UUID', $tenant->uuid],
                ['Slug', $tenant->slug],
                ['Название', $tenant->name],
                ['Тип приложения', $tenant->app_type],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Настраивает роли, права и создает суперадмина для сервисного тенанта
     */
    private function provisionServiceTenant(Tenant $tenant, array $config): void
    {
        $this->info("⚙️ Настройка ролей и суперадминистратора...");

        // 1. Права (берем все из конфига, как в базовом тенанте)
        $permissionsMap = config('permissions.map', []);
        $permissionIds = [];

        foreach ($permissionsMap as $name => $label) {
            $perm = TenantPermission::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['label' => $label]
            );
            $permissionIds[] = $perm->id;
        }

        $syncData = array_fill_keys($permissionIds, ['tenant_id' => $tenant->id]);

        // 2. Роль "Старший" (Суперадмин)
        $superRole = TenantRole::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $config['super_role_name']],
            ['label' => $config['super_admin']]
        );
        $superRole->permissions()->sync($syncData);

        // 3. Роль "Младший" (обычный агент/доставщик)
        // Создаем роль, но права можно настроить вручную через админку позже,
        // либо здесь можно дать ограниченный набор прав. Пока создаем пустую.
        TenantRole::updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => $config['admin']],
            ['label' => $config['admin']]
        );

        // 4. Создание или сброс Суперадмина
        $adminEmail = "senior_{$config['slug']}@mypwa.ru";
        $adminPassword = 'SuperAdmin2024!'; // Можно заменить на генерацию Str::random(12)

        $adminUser = TenantUser::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $adminEmail],
            [
                'uuid' => (string)Str::uuid(),
                'name' => $config['super_admin'],
                'phone' => '+79990000000',
                'password' => bcrypt($adminPassword),
                'is_active' => true,
                'is_vip' => true,
            ]
        );

        // Синхронизируем роль (перезаписываем, чтобы гарантировать наличие прав при сбросе)
        $adminUser->roles()->sync([$superRole->id => ['tenant_id' => $tenant->id]]);

        $this->newLine();
        $this->info("👤 Учетные данные Суперадминистратора:");
        $this->table(
            ['Параметр', 'Значение'],
            [
                ['Email', $adminEmail],
                ['Пароль', $adminPassword],
                ['Роль', $config['super_admin']],
                ['Статус', $adminUser->is_active ? 'Активен' : 'Заблокирован'],
            ]
        );
        $this->warn("⚠️ Сохраните пароль! При повторном запуске команды он будет сброшен на этот же.");
    }
}
