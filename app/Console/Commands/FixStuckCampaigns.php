<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;

class FixStuckCampaigns extends Command
{
    protected $signature = 'campaigns:fix-stuck';
    protected $description = 'Fix campaigns stuck in sending/queued status by checking recipient statuses';

    public function handle()
    {
        $this->info('Checking for stuck campaigns...');
        
        // Get campaigns that are stuck in sending or queued status
        $stuckCampaigns = Campaign::whereIn('status', ['sending', 'queued'])
            ->with('recipients')
            ->get();
        
        if ($stuckCampaigns->isEmpty()) {
            $this->info('No stuck campaigns found.');
            return 0;
        }
        
        $this->info("Found {$stuckCampaigns->count()} stuck campaign(s).");
        
        foreach ($stuckCampaigns as $campaign) {
            $this->info("\nProcessing Campaign ID: {$campaign->id} - {$campaign->name}");
            $this->info("Current Status: {$campaign->status}");
            
            // Get recipient counts
            $statusCounts = $campaign->getRecipientsCountByStatus();
            $total = $campaign->total_recipients;
            $sent = $statusCounts['sent'] ?? 0;
            $failed = $statusCounts['failed'] ?? 0;
            $pending = $statusCounts['pending'] ?? 0;
            
            $this->info("Total Recipients: {$total}");
            $this->info("Sent: {$sent}, Failed: {$failed}, Pending: {$pending}");
            
            // Determine new status
            $newStatus = $campaign->status;
            if ($pending > 0) {
                $newStatus = 'sending';
                $this->warn("Campaign still has {$pending} pending recipients. Status: sending");
            } elseif (($sent + $failed) >= $total && $total > 0) {
                $newStatus = 'completed';
                $this->info("All recipients processed. Updating status to: completed");
            } elseif ($total == 0) {
                $newStatus = 'completed';
                $this->warn("Campaign has no recipients. Updating status to: completed");
            }
            
            $oldStatus = $campaign->status;
            if ($newStatus !== $oldStatus) {
                $campaign->update(['status' => $newStatus]);
                $this->info("✓ Campaign status updated from '{$oldStatus}' to '{$newStatus}'");
            } else {
                $this->info("Campaign status is correct: {$newStatus}");
            }
        }
        
        $this->info("\n✓ Finished processing stuck campaigns.");
        return 0;
    }
}

