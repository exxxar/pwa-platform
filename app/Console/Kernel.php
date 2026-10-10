<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('tenants:check-balance')
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->runInBackground()
            ->emailOutputOnFailure(config('app.admin_email'));

        // 🎯 ЕДИНЫЙ ПРАВИЛЬНЫЙ ВОРКЕР ДЛЯ КРОНА
        // 1. --connection=database (явно указываем драйвер)
        // 2. --queue=notifications,telegram (слушаем обе очереди, приоритет у первой)
        // 3. --tries=3 (3 попытки при ошибке)
        // 4. --once (КРИТИЧНО ВАЖНО: обработать задачи и завершиться, чтобы крон мог запустить его снова)
        $schedule->command('queue:work database --queue=notifications,telegram --tries=3 --once')
            ->withoutOverlapping()
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
