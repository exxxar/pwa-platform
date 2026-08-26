<?php

namespace Database\Seeders\Admin;

use App\Models\Admin\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Запустить seeder для создания администраторов.
     */
    public function run(): void
    {
        $this->command->info('👤 Начинаем создание администраторов...');

        // Проверяем существование роли admin
        $adminRole = Role::where('name', config('admin.default_role', 'admin'))->first();

        if (!$adminRole) {
            $this->command->error('❌ Роль "admin" не найдена!');
            $this->command->warn('💡 Сначала запустите RoleAndPermissionSeeder:');
            $this->command->line('   php artisan db:seed --class=RoleAndPermissionSeeder');
            return;
        }

        $this->command->info("✅ Роль 'admin' найдена (ID: {$adminRole->id})");

        // Получаем список админов из конфига
        $admins = config('admin.default_admins', []);

        if (empty($admins)) {
            $this->command->warn('⚠️  Список администраторов пуст. Проверьте config/admin.php');
            return;
        }

        $createdCount = 0;
        $updatedCount = 0;

        foreach ($admins as $adminData) {
            $this->command->info("📝 Обрабатываем: {$adminData['email']}");

            // Проверяем обязательные поля
            if (empty($adminData['email']) || empty($adminData['password'])) {
                $this->command->error("  ❌ Пропущен: отсутствуют обязательные поля (email или password)");
                continue;
            }

            // Валидация email
            if (!filter_var($adminData['email'], FILTER_VALIDATE_EMAIL)) {
                $this->command->error("  ❌ Пропущен: некорректный email формат");
                continue;
            }

            // Валидация пароля
            if (strlen($adminData['password']) < 8) {
                $this->command->error("  ❌ Пропущен: пароль должен быть минимум 8 символов");
                continue;
            }

            // Создаем или обновляем админа
            $admin = User::where('email', $adminData['email'])->first();

            if ($admin) {
                // Админ уже существует - обновляем данные
                $admin->update([
                    'name' => $adminData['name'] ?? $admin->name,
                    'password' => $adminData['password'], // Мутиратор захеширует
                ]);
                $updatedCount++;
                $this->command->line("  ✓ Обновлен: {$adminData['email']}");
            } else {
                // Создаем нового админа
                $admin = User::create([
                    'name' => $adminData['name'] ?? 'Администратор',
                    'email' => $adminData['email'],
                    'password' => $adminData['password'], // Мутиратор захеширует
                ]);
                $createdCount++;
                $this->command->line("  ✓ Создан: {$adminData['email']}");
            }

            // Назначаем роль admin
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
            $this->command->line("  ✓ Назначена роль: {$adminRole->label}");
        }

        // Итоговая информация
        $this->command->info('');
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('📊 ИТОГО:');
        $this->command->info("  ✅ Создано администраторов: {$createdCount}");
        $this->command->info("  🔄 Обновлено администраторов: {$updatedCount}");
        $this->command->info('═══════════════════════════════════════════════════');
        $this->command->info('');

        // Выводим креды для первого админа
        if ($createdCount > 0 && !empty($admins[0])) {
            $this->command->warn('🔐 Креды для входа:');
            $this->command->line("  Email:    {$admins[0]['email']}");
            $this->command->line("  Password: {$admins[0]['password']}");
            $this->command->warn('  ⚠️  Не забудьте сменить пароль после первого входа!');
        }
    }
}
