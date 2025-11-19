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
            // Get current queue size before processing
            $queueSize = \Illuminate\Support\Facades\DB::table('jobs')
                ->where('queue', 'emails')
                ->count();
            
            $this->info("Queue size before processing: {$queueSize} jobs");
            
            // Process jobs from the emails queue
            // stop-when-empty=true ensures it processes all available jobs up to max-jobs
            Artisan::call('queue:work', [
                '--queue' => 'emails',
                '--max-jobs' => $maxJobs,
                '--tries' => 3,
                '--timeout' => 120, // Increased timeout for better reliability
                '--stop-when-empty' => true, // Stop when queue is empty (processes all available)
            ]);
            
            $output = Artisan::output();
            if ($output) {
                $this->info($output);
            }
            
            // Check queue size after processing
            $remainingJobs = \Illuminate\Support\Facades\DB::table('jobs')
                ->where('queue', 'emails')
                ->count();
            
            if ($remainingJobs > 0) {
                $this->warn("⚠️  {$remainingJobs} jobs still remaining in queue.");
                $this->info("These will be processed in the next scheduled run.");
            } else {
                $this->info("✓ All jobs processed successfully.");
            }
            
            // Also check for pending recipients that might need jobs re-dispatched
            $this->checkAndRedispatchPendingRecipients();
            
        } catch (\Exception $e) {
            $this->error("Error processing queue: " . $e->getMessage());
            Log::error("AutoProcessQueue error: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Check for pending recipients in active campaigns and re-dispatch jobs if needed
     */
    private function checkAndRedispatchPendingRecipients()
    {
        try {
            $pendingRecipients = \App\Models\Recipient::where('status', 'pending')
                ->whereHas('campaign', function($query) {
                    $query->whereIn('status', ['sending', 'queued']);
                })
                ->where('created_at', '<', now()->subMinutes(2)) // Recipients pending for more than 2 minutes
                ->limit(50)
                ->get();
            
            if ($pendingRecipients->count() > 0) {
                $this->info("Found {$pendingRecipients->count()} pending recipients, re-dispatching jobs...");
                
                foreach ($pendingRecipients as $recipient) {
                    // Check if job already exists in queue
                    $jobExists = \Illuminate\Support\Facades\DB::table('jobs')
                        ->where('queue', 'emails')
                        ->where('payload', 'like', '%"recipientId":' . $recipient->id . '%')
                        ->exists();
                    
                    if (!$jobExists) {
                        \App\Jobs\SendRecipientJob::dispatch($recipient->id)
                            ->onQueue('emails')
                            ->delay(now()->addSeconds(rand(1, 3)));
                        $this->info("  ✓ Re-dispatched job for recipient {$recipient->id}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error checking pending recipients: " . $e->getMessage());
        }
    }
}

