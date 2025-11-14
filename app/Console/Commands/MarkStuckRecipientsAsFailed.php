<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\Recipient;
use Illuminate\Console\Command;

class MarkStuckRecipientsAsFailed extends Command
{
    protected $signature = 'campaigns:mark-stuck-failed {--hours=24 : Mark recipients as failed if pending for this many hours}';
    protected $description = 'Mark old pending recipients as failed to clear stuck campaigns';

    public function handle()
    {
        $hours = (int) $this->option('hours');
        
        $this->info("Marking recipients as failed if pending for more than {$hours} hour(s)...\n");
        
        // Get pending recipients older than specified hours
        $stuckRecipients = Recipient::where('status', 'pending')
            ->where('created_at', '<', now()->subHours($hours))
            ->with('campaign')
            ->get();
        
        if ($stuckRecipients->isEmpty()) {
            $this->info('No stuck recipients found.');
            return 0;
        }
        
        $this->info("Found {$stuckRecipients->count()} stuck recipient(s).\n");
        
        $campaignsToUpdate = [];
        
        foreach ($stuckRecipients as $recipient) {
            $this->info("Marking recipient {$recipient->id} ({$recipient->email}) as failed");
            $this->info("  Campaign: {$recipient->campaign->name} (ID: {$recipient->campaign_id})");
            $this->info("  Created: {$recipient->created_at->diffForHumans()}");
            
            $recipient->update([
                'status' => 'failed',
                'last_error' => 'Recipient marked as failed - stuck in pending status for more than ' . $hours . ' hour(s)',
            ]);
            
            if (!in_array($recipient->campaign_id, $campaignsToUpdate)) {
                $campaignsToUpdate[] = $recipient->campaign_id;
            }
        }
        
        // Update campaign statuses
        $this->info("\nUpdating campaign statuses...");
        foreach ($campaignsToUpdate as $campaignId) {
            $campaign = Campaign::find($campaignId);
            if ($campaign) {
                $statusCounts = $campaign->getRecipientsCountByStatus();
                $total = $campaign->total_recipients;
                $sent = $statusCounts['sent'] ?? 0;
                $failed = $statusCounts['failed'] ?? 0;
                $pending = $statusCounts['pending'] ?? 0;
                
                $newStatus = $campaign->status;
                if ($pending > 0) {
                    $newStatus = 'sending';
                } elseif (($sent + $failed) >= $total && $total > 0) {
                    $newStatus = 'completed';
                }
                
                if ($newStatus !== $campaign->status) {
                    $campaign->update(['status' => $newStatus]);
                    $this->info("  Campaign {$campaignId} status updated to: {$newStatus}");
                }
            }
        }
        
        $this->info("\n✓ Finished marking stuck recipients as failed.");
        return 0;
    }
}

