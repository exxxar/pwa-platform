<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;

use App\Models\Tenant\TenantPermission;
use App\Models\Tenant\TenantRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Запустить seeder для создания ролей и разрешений.
     */
    public function run(): void
    {
        $this->command->info('🌱 Начинаем создание ролей и разрешений...');

        // Очищаем существующие данные (опционально, можно закомментировать)
        $this->cleanExistingData();

        // Создаем глобальные разрешения и роли
        $this->createGlobalPermissionsAndRoles();

        // Создаем тенантные разрешения и роли
      //  $this->createTenantPermissionsAndRoles();

        $this->command->info('✅ Роли и разрешения успешно созданы!');
    }

    /**
     * Очистка существующих данных (опционально)
     */
    private function cleanExistingData(): void
    {
        if ($this->command->confirm('Удалить существующие роли и разрешения?', false)) {
            $this->command->warn('⚠️  Удаляем существующие данные...');

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Глобальные
            DB::table('permission_role')->truncate();
            DB::table('role_user')->truncate();
            Permission::query()->delete();
            Role::query()->delete();

            // Тенантные
            DB::table('tenant_permission_role')->truncate();
            TenantPermission::query()->delete();
            TenantRole::query()->delete();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            $this->command->info('✅ Существующие данные удалены');
        }
    }

    /**
     * Создание глобальных разрешений и ролей
     */
    private function createGlobalPermissionsAndRoles(): void
    {
        $this->command->info('📋 Создаем глобальные разрешения...');

        $globalPermissions = config('permissions.global', []);
        $permissionIds = [];

        foreach ($globalPermissions as $name => $label) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['label' => $label]
            );
            $permissionIds[] = $permission->id;
            $this->command->line("  ✓ {$name}");
        }

        $this->command->info('✅ Создано ' . count($permissionIds) . ' глобальных разрешений');

        // Создаем глобальные роли
        $this->command->info('👥 Создаем глобальные роли...');

        $rolesConfig = config('permissions.roles', []);

        // Роль "Администратор"
        if (isset($rolesConfig['admin'])) {
            $adminRole = Role::firstOrCreate(
                ['name' => 'admin'],
                ['label' => $rolesConfig['admin']['label']]
            );

            // Назначаем все глобальные разрешения
            $adminRole->permissions()->sync($permissionIds);
            $this->command->line("  ✓ Роль 'admin' ({$rolesConfig['admin']['label']}) создана с " . count($permissionIds) . " разрешениями");
        }

        $this->command->info('✅ Глобальные роли созданы');

        $this->createFirstAdmin();
    }

    /**
     * Создание тенантных разрешений и ролей
     */
    private function createTenantPermissionsAndRoles(): void
    {
        $this->command->info('📋 Создаем тенантные разрешения...');

        $tenantPermissions = config('permissions.tenant', []);
        $permissionIds = [];

        // Создаем разрешения БЕЗ привязки к конкретному тенанту
        // (они будут клонироваться при создании каждого тенанта в Tenant model booted)
        foreach ($tenantPermissions as $name => $label) {
            // Создаем "шаблон" разрешения без tenant_id
            // При создании тенанта в Tenant::booted() они будут скопированы
            $permission = TenantPermission::firstOrCreate(
                ['name' => $name, 'tenant_id' => null],
                ['label' => $label]
            );
            $permissionIds[] = $permission->id;
            $this->command->line("  ✓ {$name}");
        }

        $this->command->info('✅ Создано ' . count($permissionIds) . ' тенантных разрешений');

        // Создаем роль "Владелец тенанта"
        $this->command->info('👥 Создаем роль "Владелец тенанта"...');

        $rolesConfig = config('permissions.roles', []);

        if (isset($rolesConfig['tenant_owner'])) {
            // Создаем "шаблон" роли без tenant_id
            $ownerRole = TenantRole::firstOrCreate(
                ['name' => 'tenant_owner', 'tenant_id' => null],
                ['label' => $rolesConfig['tenant_owner']['label']]
            );

            // Назначаем все тенантные разрешения
            $ownerRole->permissions()->sync($permissionIds);
            $this->command->line("  ✓ Роль 'tenant_owner' ({$rolesConfig['tenant_owner']['label']}) создана с " . count($permissionIds) . " разрешениями");
        }

        $this->command->info('✅ Тенантные роли созданы');

        $this->command->warn('💡 Примечание: При создании каждого нового тенанта эти разрешения и роли будут автоматически скопированы для него.');
    }

    private function createFirstAdmin(): void
    {
        $this->command->info('👤 Создаем первого администратора...');

        $adminRole = Role::where('name', 'admin')->first();

        $admin = \App\Models\Admin\User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Главный Администратор',
                'password' => 'password', // Будет захеширован мутиратором
            ]
        );

        $admin->roles()->sync([$adminRole->id]);

        $this->command->line('  ✓ Email: admin@example.com');
        $this->command->line('  ✓ Password: password');
        $this->command->warn('  ⚠️  Не забудьте сменить пароль после первого входа!');
    }
}
