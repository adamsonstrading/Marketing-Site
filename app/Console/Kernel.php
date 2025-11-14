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
        // Process email queue every minute
        $schedule->command('queue:auto-process --max-jobs=50')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Fix stuck campaigns every 5 minutes
        $schedule->command('campaigns:fix-stuck')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // Mark stuck recipients as failed every hour
        $schedule->command('campaigns:mark-stuck-failed --hours=2')
            ->hourly()
            ->withoutOverlapping();
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

