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

        // 🎯 ИСПРАВЛЕННАЯ НАСТРОЙКА ОЧЕРЕДИ
        // 1. Убрали runInBackground(), чтобы ошибки писались в лог
        // 2. Заменили --once на --stop-when-empty (более корректно для крона)
        $schedule->command('queue:work database --queue=notifications,telegram --tries=3 --stop-when-empty')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/queue_worker.log')); // 🆕 Пишем ошибки воркера в отдельный файл!
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
