<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\Recipient;
use App\Services\SmtpRotationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Exception;

class SendRecipientJob implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 60;
    
    // Add delay between emails to prevent rate limiting (2 seconds default)
    public int $backoff = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $recipientId
    ) {
        // Add small random delay to prevent sending too fast
        $this->delay(now()->addSeconds(rand(1, 3)));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $recipient = Recipient::with(['campaign.smtpConfiguration', 'campaign.sender'])->findOrFail($this->recipientId);
            $campaign = $recipient->campaign;

            Log::info("Processing recipient {$this->recipientId} for campaign {$campaign->id}");

            // Check if email is blacklisted
            if (\App\Models\Blacklist::isBlacklisted($recipient->email)) {
                Log::info("Recipient {$recipient->email} is blacklisted, skipping");
                $recipient->update([
                    'status' => 'failed',
                    'last_error' => 'Email address is blacklisted',
                ]);
                $this->recomputeCampaignStatus($campaign->id);
                return;
            }

            // Use SMTP rotation service for better load balancing
            $smtpRotationService = app(SmtpRotationService::class);
            $smtpConfig = null;

            // Use SMTP configuration if available, otherwise fallback to sender or rotation
            if ($campaign->smtp_configuration_id && $campaign->smtpConfiguration) {
                $smtpConfig = $campaign->smtpConfiguration;
                
                // Check if this SMTP can be used (not at limit)
                if (!$smtpRotationService->canUse($smtpConfig)) {
                    Log::warning("SMTP {$smtpConfig->id} is at limit, trying rotation");
                    // Try to get another SMTP via rotation
                    $smtpConfig = $smtpRotationService->getNextSmtp();
                    if (!$smtpConfig) {
                        throw new \Exception("No available SMTP configurations");
                    }
                }
            } elseif (!$campaign->sender_id || !$campaign->sender) {
                // No SMTP or sender specified, use rotation
                $smtpConfig = $smtpRotationService->getNextSmtp();
                if ($smtpConfig) {
                    Log::info("Using rotated SMTP: {$smtpConfig->name} (ID: {$smtpConfig->id})");
                }
            }

            if ($smtpConfig) {
                
                // Get decrypted password
                $smtpPassword = $smtpConfig->password; // Already decrypted via accessor
                
                // Use SMTP configuration
                config([
                    'mail.mailers.smtp.host' => $smtpConfig->host,
                    'mail.mailers.smtp.port' => $smtpConfig->port,
                    'mail.mailers.smtp.username' => $smtpConfig->username,
                    'mail.mailers.smtp.password' => $smtpPassword,
                    'mail.mailers.smtp.encryption' => $smtpConfig->encryption ?? 'tls',
                    'mail.from.address' => $smtpConfig->from_address,
                    'mail.from.name' => $smtpConfig->from_name,
                ]);

                $fromAddress = $smtpConfig->from_address;
                $fromName = $smtpConfig->from_name;
                $smtpHost = $smtpConfig->host;
                $smtpPort = $smtpConfig->port;
                $smtpUsername = $smtpConfig->username;
                $encryption = $smtpConfig->encryption ?? 'tls';
                
                Log::info("Using SMTP Configuration:", [
                    'host' => $smtpHost,
                    'port' => $smtpPort,
                    'username' => $smtpUsername,
                    'password_length' => strlen($smtpPassword),
                    'encryption' => $encryption,
                    'from_address' => $fromAddress,
                    'from_name' => $fromName,
                ]);
            } elseif ($campaign->sender_id && $campaign->sender) {
                // Fallback to sender (legacy support)
                $sender = $campaign->sender;
                
                // Decrypt the SMTP password
                $smtpPassword = decrypt($sender->getRawOriginal('smtp_password'));

                config([
                    'mail.mailers.smtp.host' => $sender->smtp_host,
                    'mail.mailers.smtp.port' => $sender->smtp_port,
                    'mail.mailers.smtp.username' => $sender->smtp_username,
                    'mail.mailers.smtp.password' => $smtpPassword,
                    'mail.mailers.smtp.encryption' => $sender->smtp_encryption ?? 'tls',
                    'mail.from.address' => $sender->from_address,
                    'mail.from.name' => $sender->from_name,
                ]);

                $fromAddress = $sender->from_address;
                $fromName = $sender->from_name;
                $smtpHost = $sender->smtp_host;
                $smtpPort = $sender->smtp_port;
                $smtpUsername = $sender->smtp_username;
                $encryption = $sender->smtp_encryption ?? 'tls';
                
                Log::info("Using Sender (legacy):", [
                    'host' => $smtpHost,
                    'port' => $smtpPort,
                    'username' => $smtpUsername,
                    'password_length' => strlen($smtpPassword),
                    'encryption' => $encryption,
                    'from_address' => $fromAddress,
                    'from_name' => $fromName,
                ]);
            } else {
                throw new \Exception("Campaign {$campaign->id} has no SMTP configuration or sender");
            }

            // Clear mail manager cache to ensure new config is used
            app('mail.manager')->forgetMailers();

            // Check if campaign is paused
            if ($campaign->status === 'paused') {
                Log::info("Campaign {$campaign->id} is paused, skipping recipient {$this->recipientId}");
                return; // Don't send, but don't mark as failed
            }

            // Add small delay between emails to prevent rate limiting
            // This helps avoid being blocked by SMTP servers during bulk sending
            usleep(2000000); // 2 seconds delay (2000000 microseconds)

            // Prepare dynamic variables for email template
            $firstName = '';
            $lastName = '';
            if ($recipient->name) {
                $nameParts = explode(' ', trim($recipient->name), 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
            }

            $variables = [
                'name' => $recipient->name ?? $recipient->email,
                'email' => $recipient->email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'campaign_name' => $campaign->name,
                'sender_name' => $fromName,
            ];

            // Send the email with dynamic variables
            try {
                Mail::mailer('smtp')
                    ->to($recipient->email)
                    ->bcc($fromAddress) // ensure a copy lands in sender's mailbox
                    ->send(new CampaignMail($campaign->subject, $campaign->body, $variables));

                // Record success for SMTP rotation
                if ($smtpConfig) {
                    $smtpRotationService->recordSuccess($smtpConfig);
                }

                // Update recipient status on success
                $recipient->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'last_error' => null,
                ]);

                // Recompute and persist campaign aggregate status for dashboards
                $this->recomputeCampaignStatus($campaign->id);

                Log::info("Successfully sent email to {$recipient->email}");
            } catch (Exception $mailException) {
                // Record failure for SMTP rotation
                if ($smtpConfig) {
                    $smtpRotationService->recordFailure($smtpConfig);
                }
                throw $mailException;
            }

        } catch (Exception $e) {
            Log::error("Failed to send email to recipient {$this->recipientId}: " . $e->getMessage());

            // Record failure for SMTP rotation if SMTP config was used
            if (isset($smtpConfig) && $smtpConfig && isset($smtpRotationService)) {
                $smtpRotationService->recordFailure($smtpConfig);
            }

            $recipient = Recipient::find($this->recipientId);
            if ($recipient) {
                $recipient->increment('attempt_count');
                
                if ($recipient->attempt_count >= $this->tries) {
                    $recipient->update([
                        'status' => 'failed',
                        'last_error' => $e->getMessage(),
                    ]);
                    Log::error("Recipient {$this->recipientId} marked as failed after {$this->tries} attempts");
                    if ($recipient->campaign_id) {
                        $this->recomputeCampaignStatus($recipient->campaign_id);
                    }
                } else {
                    $recipient->update([
                        'last_error' => $e->getMessage(),
                    ]);
                    
                    // Re-dispatch with exponential backoff delay
                    $delay = pow(2, $recipient->attempt_count) * 60; // Exponential backoff in minutes
                    Log::info("Re-dispatching recipient {$this->recipientId} with {$delay} minute delay");
                    
                    static::dispatch($this->recipientId)->delay(now()->addMinutes($delay));
                }
            }

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("SendRecipientJob failed for recipient {$this->recipientId}: " . $exception->getMessage());
        
        $recipient = Recipient::find($this->recipientId);
        if ($recipient) {
            $recipient->update([
                'status' => 'failed',
                'last_error' => $exception->getMessage(),
            ]);
            if ($recipient->campaign_id) {
                $this->recomputeCampaignStatus($recipient->campaign_id);
            }
        }
    }

    /**
     * Update the parent campaign's status based on its recipients.
     */
    private function recomputeCampaignStatus(int $campaignId): void
    {
        try {
            $campaign = \App\Models\Campaign::find($campaignId);
            if (!$campaign) {
                return;
            }

            $counts = $campaign->getRecipientsCountByStatus();
            $total = (int) $campaign->total_recipients;
            $sent = (int) ($counts['sent'] ?? 0);
            $failed = (int) ($counts['failed'] ?? 0);
            $pending = (int) ($counts['pending'] ?? 0);

            $newStatus = $campaign->status;
            if ($pending > 0) {
                $newStatus = 'sending';
            } elseif (($sent + $failed) >= $total && $total > 0) {
                $newStatus = 'completed';
            }

            if ($newStatus !== $campaign->status) {
                $campaign->update(['status' => $newStatus]);
            } else {
                // Touch updated_at so dashboards see recent activity
                $campaign->touch();
            }
        } catch (Exception $e) {
            Log::warning('Failed to recompute campaign status: ' . $e->getMessage());
        }
    }
}
