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
        // Trading strategy execution (every 5 minutes)
        if (config('trading.strategies.enabled')) {
            $schedule->command('trading:run --paper')
                ->everyFiveMinutes()
                ->withoutOverlapping(60)
                ->onOneServer();
        }

        // Portfolio snapshot capture (hourly)
        $schedule->command('trading:snapshot')
            ->hourly()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
