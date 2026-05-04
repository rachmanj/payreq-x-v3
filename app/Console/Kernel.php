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
        $schedule->command('exchange-rates:update')
            ->weeklyOn(3, '10:00') // Wednesday 10:00 Asia/Jakarta assumed
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('exchange-rates:update --force')
            ->dailyAt('11:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('sap:sync-master-data')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('anggaran:sync-release-totals')
            ->hourly()
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('anggaran:inactivate-many --last-month')
            ->monthlyOn(1, '05:00')
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
