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
        // Process email queue every minute (increased max jobs for better throughput)
        $schedule->command('queue:auto-process --max-jobs=100')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Process remaining jobs for campaigns that are almost done (every 30 seconds)
        // This ensures the last few emails in campaigns are sent quickly
        $schedule->command('queue:auto-process --max-jobs=50')
            ->everyThirtySeconds()
            ->withoutOverlapping()
            ->runInBackground();
        
        // Fix stuck campaigns every 5 minutes
        $schedule->command('campaigns:fix-stuck')
            ->everyFiveMinutes()
            ->withoutOverlapping();
        
        // Re-dispatch jobs for pending recipients every 2 minutes
        // This catches any recipients that lost their jobs
        $schedule->command('campaigns:process-pending --force')
            ->everyTwoMinutes()
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

