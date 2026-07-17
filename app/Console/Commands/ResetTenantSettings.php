<?php

namespace App\Console\Commands;

use App\Models\Tenant\Tenant;
use App\Services\TenantSettingsService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ResetTenantSettings extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'tenant:reset-settings
                            {tenant? : Slug или ID тенанта для сброса}
                            {--all : Сбросить настройки у ВСЕХ тенантов}
                            {--force : Пропустить подтверждение действия}';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Сброс настроек тенанта(ов) до значений по умолчанию из TenantSettingsService';

    /**
     * Выполнение команды.
     */
    public function handle(): int
    {
        $isAll = $this->option('all');
        $tenantArg = $this->argument('tenant');
        $isForce = $this->option('force');

        // 1. Валидация входных данных
        if (!$isAll && !$tenantArg) {
            $this->error('Ошибка: Укажите slug/ID тенанта или используйте флаг --all.');
            $this->info('Пример: php artisan tenant:reset-settings fastoran');
            $this->info('Пример: php artisan tenant:reset-settings --all');
            return Command::FAILURE;
        }

        // 2. Поиск тенантов
        /** @var Collection|Tenant[] $tenants */
        if ($isAll) {
            $tenants = Tenant::all();
        } else {
            $tenants = Tenant::where('slug', $tenantArg)
                ->orWhere('id', $tenantArg)
                ->get();
        }

        if ($tenants->isEmpty()) {
            $this->error("Тенант с идентификатором '{$tenantArg}' не найден.");
            return Command::FAILURE;
        }

        // 3. Запрос подтверждения (если не указан --force)
        $count = $tenants->count();
        if (!$isForce && !$this->confirm("⚠️ Вы уверены, что хотите сбросить настройки для {$count} тенанта(ов)? Все кастомные настройки будут безвозвратно удалены.")) {
            $this->info('Операция отменена.');
            return Command::FAILURE;
        }

        // 4. Получение дефолтных настроек
        $this->info('Загрузка настроек по умолчанию...');
        $defaultSettings = TenantSettingsService::getDefaultSettings();

        // 5. Выполнение сброса
        $this->info("Начинаю сброс настроек для {$count} тенанта(ов)...");
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        foreach ($tenants as $tenant) {
            // Перезаписываем колонку meta полностью
            $tenant->update([
                'meta' => $defaultSettings
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // 6. Вывод результатов
        $this->table(
            ['ID', 'Slug', 'Name', 'Status'],
            $tenants->map(fn($t) => [
                $t->id,
                $t->slug,
                $t->name,
                '<fg=green>Успешно</>'
            ])->toArray()
        );

        $this->info('✅ Настройки успешно сброшены до значений по умолчанию!');

        // Опционально: очистка кэша, если вы кэшируете настройки тенантов
        // $this->call('cache:clear');

        return Command::SUCCESS;
    }
}
