<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Tenant;
use App\Models\Tenant\TenantPermission;
use App\Models\Tenant\TenantRole;
use App\Models\Tenant\TenantUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BackfillTenantAdminsSeeder extends Seeder
{
    /**
     * Единый список всех прав в системе.
     * Если добавите новое право в будущем — добавляйте его сюда,
     * и сидер автоматически "догонит" все существующие тенанты.
     */
    private array $permissionsMap = [

    ];

    public function run()
    {

        $this->permissionsMap = [...$this->permissionsMap, ...config("permissions.map")];
        $tenants = Tenant::all();
        $total = $tenants->count();

        $this->command->info("🚀 Найдено тенантов: {$total}");
        $this->command->newLine();

        $stats = [
            'tenants_processed' => 0,
            'admins_created' => 0,
            'admins_existing' => 0,
            'permissions_created' => 0,
            'roles_created' => 0,
            'errors' => 0,
        ];

        $bar = $this->command->getOutput()->createProgressBar($total);
        $bar->start();

        foreach ($tenants as $tenant) {
            try {
                $result = $this->provisionTenant($tenant);

                $stats['tenants_processed']++;
                $stats['admins_created'] += $result['admin_created'] ? 1 : 0;
                $stats['admins_existing'] += $result['admin_created'] ? 0 : 1;
                $stats['permissions_created'] += $result['new_permissions'];
                $stats['roles_created'] += $result['role_created'] ? 1 : 0;

            } catch (\Throwable $e) {
                $stats['errors']++;
                $this->command->newLine();
                $this->command->error("❌ Ошибка для тенанта [{$tenant->id}] {$tenant->slug}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine(2);

        // Красивый отчёт
        $this->command->info("✅ Обработка завершена!");
        $this->command->table(
            ['Метрика', 'Значение'],
            [
                ['Тенантов обработано', $stats['tenants_processed']],
                ['Создано админов', $stats['admins_created']],
                ['Админов уже было', $stats['admins_existing']],
                ['Создано новых прав', $stats['permissions_created']],
                ['Создано ролей super_admin', $stats['roles_created']],
                ['Ошибок', $stats['errors']],
            ]
        );
    }


    /**
     * "Обустраивает" один тенант: права → роль → админ
     */
    private function provisionTenant(Tenant $tenant): array
    {
        $result = [
            'admin_created' => false,
            'new_permissions' => 0,
            'role_created' => false,
        ];

        // 1. Создаём/находим все права для этого тенанта
        $permissionIds = [];
        foreach ($this->permissionsMap as $name => $label) {
            $perm = TenantPermission::firstOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                ['label' => $label]
            );
            $permissionIds[] = $perm->id;

            if ($perm->wasRecentlyCreated) {
                $result['new_permissions']++;
            }
        }

        // 2. Создаём/находим роль super_admin
        $role = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'super_admin'],
            ['label' => 'Суперадмин']
        );
        $result['role_created'] = $role->wasRecentlyCreated;

        // 3. 🔧 ИСПРАВЛЕНИЕ: синхронизируем права с передачей tenant_id в pivot
        $permissionsSyncData = [];
        foreach ($permissionIds as $permId) {
            $permissionsSyncData[$permId] = ['tenant_id' => $tenant->id];
        }
        $role->permissions()->sync($permissionsSyncData);

        // 4. Формируем email админа
        $safeSlug = Str::slug($tenant->slug ?: $tenant->name, '_');
        $adminEmail = "admin_{$safeSlug}@mypwa.ru";

        // 5. Проверяем, существует ли уже админ
        $existingAdmin = TenantUser::where('tenant_id', $tenant->id)
            ->where('email', $adminEmail)
            ->first();

        if ($existingAdmin) {
            // 🔧 ИСПРАВЛЕНИЕ: проверяем роль с учётом tenant_id в pivot
            $hasSuperAdminRole = $existingAdmin->roles()
                ->where('tenant_roles.id', $role->id)
                ->wherePivot('tenant_id', $tenant->id)
                ->exists();

            if (!$hasSuperAdminRole) {
                // 🔧 ИСПРАВЛЕНИЕ: attach с передачей tenant_id в pivot
                $existingAdmin->roles()->attach($role->id, ['tenant_id' => $tenant->id]);
            }

            return $result;
        }

        // 6. Создаём нового админа
        $adminUser = TenantUser::create([
            'tenant_id' => $tenant->id,
            'uuid' => (string)Str::uuid(),
            'name' => 'Администратор',
            'email' => $adminEmail,
            'phone' => '+79494320661',
            'password' => 'admin123',
            'is_active' => true,
            'is_vip' => true,
            'referral_code' => TenantUser::generateReferralCode(),
        ]);

        // 🔧 ИСПРАВЛЕНИЕ: attach с передачей tenant_id в pivot
        $adminUser->roles()->attach($role->id, ['tenant_id' => $tenant->id]);

        $result['admin_created'] = true;

        return $result;
    }
}
