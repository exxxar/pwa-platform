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
        // $schedule->command('inspire')->hourly();


        $schedule->command('tenants:check-balance')
            ->dailyAt('00:05')
            ->withoutOverlapping()
            ->runInBackground()
            ->emailOutputOnFailure(config('app.admin_email'));

        ///* * * * * cd /home/l/likholetov/mypwa.ru/public_html && php artisan schedule:run >> /dev/null 2>&1
        ///
        $schedule->command('queue:work --queue=telegram --stops-when-empty --tries=3')
            ->withoutOverlapping()
            ->runInBackground();

        // 🎯 ИСПРАВЛЕНО: Имя очереди 'notifications' должно точно совпадать с ->onQueue('notifications') в Job
        // 🎯 ДОБАВЛЕНО: --connection=database для гарантии работы с таблицей jobs
        $schedule->command('queue:work --queue=notifications --stops-when-empty --tries=3')
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
