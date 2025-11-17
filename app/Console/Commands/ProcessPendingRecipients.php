<?php

namespace App\Console\Commands;

use App\Jobs\SendRecipientJob;
use App\Models\Campaign;
use App\Models\Recipient;
use Illuminate\Console\Command;

class ProcessPendingRecipients extends Command
{
    protected $signature = 'campaigns:process-pending {--campaign-id= : Process specific campaign} {--force : Force process even if stuck}';
    protected $description = 'Re-dispatch jobs for pending recipients or mark them as failed if stuck';

    public function handle()
    {
        $campaignId = $this->option('campaign-id');
        $force = $this->option('force');
        
        if ($campaignId) {
            $campaigns = Campaign::where('id', $campaignId)->get();
        } else {
            $campaigns = Campaign::whereIn('status', ['sending', 'queued'])
                ->get();
        }
        
        if ($campaigns->isEmpty()) {
            $this->info('No campaigns found to process.');
            return 0;
        }
        
        $this->info("Processing " . $campaigns->count() . " campaign(s)...\n");
        
        foreach ($campaigns as $campaign) {
            $this->info("Campaign ID: {$campaign->id} - {$campaign->name}");
            
            // Get pending recipients
            $pendingRecipients = $campaign->recipients()
                ->where('status', 'pending')
                ->get();
            
            if ($pendingRecipients->isEmpty()) {
                $this->info("  No pending recipients.");
                continue;
            }
            
            $this->info("  Found {$pendingRecipients->count()} pending recipient(s).");
            
            foreach ($pendingRecipients as $recipient) {
                // Check if recipient is stuck (created more than 1 hour ago)
                $isStuck = $recipient->created_at->lt(now()->subHour());
                
                if ($isStuck || $force) {
                    if ($isStuck) {
                        $this->warn("  Recipient {$recipient->id} ({$recipient->email}) is stuck (created {$recipient->created_at->diffForHumans()}).");
                    }
                    
                    // Re-dispatch the job
                    SendRecipientJob::dispatch($recipient->id)->onQueue('emails');
                    $this->info("  ✓ Re-dispatched job for recipient {$recipient->id}");
                } else {
                    $this->info("  Recipient {$recipient->id} ({$recipient->email}) is still fresh, skipping.");
                }
            }
        }
        
        $this->info("\n✓ Finished processing pending recipients.");
        $this->info("\nNote: Make sure queue worker is running: php artisan queue:work --queue=emails");
        
        return 0;
    }
}

