<?php

namespace App\Console\Commands;

use App\Jobs\SendRecipientJob;
use App\Models\Recipient;
use Illuminate\Console\Command;

class ProcessEmailNow extends Command
{
    protected $signature = 'email:send-now {email : Email address to send to}';
    protected $description = 'Immediately process and send email to a specific recipient';

    public function handle()
    {
        $email = $this->argument('email');
        
        $this->info("Looking for recipient with email: {$email}");
        
        $recipient = Recipient::where('email', $email)
            ->latest()
            ->first();
        
        if (!$recipient) {
            $this->error("Recipient with email '{$email}' not found.");
            return 1;
        }
        
        $this->info("Found recipient ID: {$recipient->id}");
        $this->info("Current Status: {$recipient->status}");
        $this->info("Campaign: {$recipient->campaign->name} (ID: {$recipient->campaign_id})");
        
        if ($recipient->status === 'sent') {
            $this->warn("Email already sent at: {$recipient->sent_at}");
            return 0;
        }
        
        if ($recipient->status === 'failed') {
            $this->warn("Previous attempt failed: {$recipient->last_error}");
            $this->info("Re-attempting to send...");
        }
        
        // Reset recipient to pending if needed
        if ($recipient->status !== 'pending') {
            $recipient->update([
                'status' => 'pending',
                'last_error' => null,
                'attempt_count' => 0,
            ]);
        }
        
        $this->info("\nProcessing email job...");
        
        try {
            // Process the job synchronously
            $job = new SendRecipientJob($recipient->id);
            $job->handle();
            
            // Refresh recipient to get updated status
            $recipient->refresh();
            
            if ($recipient->status === 'sent') {
                $this->info("✓ Email sent successfully!");
                $this->info("Sent at: {$recipient->sent_at}");
            } else {
                $this->error("✗ Email failed to send.");
                $this->error("Error: {$recipient->last_error}");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("✗ Error processing email: " . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}

