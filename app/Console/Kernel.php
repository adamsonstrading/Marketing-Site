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
        // Process email queue every 30 seconds (increased frequency and max jobs)
        $schedule->command('queue:auto-process --max-jobs=200')
            ->everyThirtySeconds()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Fix stuck campaigns every 2 minutes (more frequent)
        $schedule->command('campaigns:fix-stuck')
            ->everyTwoMinutes()
            ->withoutOverlapping();
        
        // Re-dispatch jobs for pending recipients every minute
        // This catches any recipients that lost their jobs quickly
        $schedule->command('campaigns:process-pending --force')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
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

