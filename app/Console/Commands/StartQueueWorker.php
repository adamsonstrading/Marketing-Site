<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class StartQueueWorker extends Command
{
    protected $signature = 'queue:start-worker';
    protected $description = 'Start the queue worker for processing email jobs';

    public function handle()
    {
        $this->info('Starting queue worker for email campaigns...');
        $this->info('Press Ctrl+C to stop');
        $this->info('');
        
        // Start queue worker
        Artisan::call('queue:work', [
            '--queue' => 'emails',
            '--tries' => 3,
            '--timeout' => 60,
            '--max-jobs' => 1000,
            '--max-time' => 3600,
        ]);
        
        return 0;
    }
}

