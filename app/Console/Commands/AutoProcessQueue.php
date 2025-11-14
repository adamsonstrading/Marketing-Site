<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class AutoProcessQueue extends Command
{
    protected $signature = 'queue:auto-process {--max-jobs=10 : Maximum jobs to process}';
    protected $description = 'Automatically process queued email jobs';

    public function handle()
    {
        $maxJobs = (int) $this->option('max-jobs');
        
        $this->info("Processing up to {$maxJobs} email jobs...");
        
        try {
            // Process jobs from the emails queue
            Artisan::call('queue:work', [
                '--queue' => 'emails',
                '--max-jobs' => $maxJobs,
                '--tries' => 3,
                '--timeout' => 60,
                '--stop-when-empty' => true,
            ]);
            
            $output = Artisan::output();
            if ($output) {
                $this->info($output);
            }
            
            $this->info("✓ Queue processing completed.");
            
        } catch (\Exception $e) {
            $this->error("Error processing queue: " . $e->getMessage());
            Log::error("AutoProcessQueue error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

