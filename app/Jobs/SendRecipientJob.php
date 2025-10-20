<?php

namespace App\Jobs;

use App\Mail\CampaignMail;
use App\Models\Recipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Exception;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class SendRecipientJob implements ShouldQueue
{
    use Queueable, SerializesModels, InteractsWithQueue;

    public int $tries = 3;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $recipientId
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $recipient = Recipient::with(['campaign.sender'])->findOrFail($this->recipientId);
            $campaign = $recipient->campaign;
            $sender = $campaign->sender;

            Log::info("Processing recipient {$this->recipientId} for campaign {$campaign->id}");

            // Decrypt the SMTP password
            $smtpPassword = decrypt($sender->getRawOriginal('smtp_password'));

            // Override mail configuration for this sender
            config([
                'mail.mailers.smtp.host' => $sender->smtp_host,
                'mail.mailers.smtp.port' => $sender->smtp_port,
                'mail.mailers.smtp.username' => $sender->smtp_username,
                'mail.mailers.smtp.password' => $smtpPassword,
                'mail.mailers.smtp.encryption' => $sender->smtp_encryption ?? 'tls',
                'mail.from.address' => $sender->from_address,
                'mail.from.name' => $sender->from_name,
            ]);

            // Clear mail manager cache to ensure new config is used
            app('mail.manager')->forgetMailers();

            // Log the configuration being used
            Log::info("Using SMTP configuration:", [
                'host' => $sender->smtp_host,
                'port' => $sender->smtp_port,
                'username' => $sender->smtp_username,
                'password_length' => strlen($smtpPassword),
                'encryption' => $sender->smtp_encryption ?? 'tls',
                'from_address' => $sender->from_address,
                'from_name' => $sender->from_name,
            ]);

            // Send the email
            Mail::mailer('smtp')
                ->to($recipient->email)
                ->bcc($sender->from_address) // ensure a copy lands in sender's mailbox
                ->send(new CampaignMail($campaign->subject, $campaign->body));

            // IMAP append disabled (using BCC-to-sender instead)

            // Update recipient status on success
            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
            ]);

            // Recompute and persist campaign aggregate status for dashboards
            $this->recomputeCampaignStatus($campaign->id);

            Log::info("Successfully sent email to {$recipient->email}");

        } catch (Exception $e) {
            Log::error("Failed to send email to recipient {$this->recipientId}: " . $e->getMessage());

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
                    
                    // Re-dispatch with delay for retry
                    $delay = pow(2, $recipient->attempt_count) * 60; // Exponential backoff in minutes
                    Log::info("Re-dispatching recipient {$this->recipientId} with {$delay} minute delay");
                    
                    static::dispatch($this->recipientId)->delay(now()->addMinutes($delay));
                }
            }

            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Append the recently sent message to the sender's IMAP Sent folder.
     * Tries common Sent folder names for IONOS.
     */
    private function appendMessageToSentFolder(
        string $smtpUsername,
        string $smtpPassword,
        string $fromAddress,
        string $fromName,
        string $toAddress,
        string $subject,
        string $htmlBody
    ): void {
        try {
            if (!function_exists('imap_open')) {
                Log::warning('IMAP PHP extension not available; skipping append to Sent.');
                return;
            }

            // Build raw RFC822 message via Symfony Mime
            $email = (new Email())
                ->from(new Address($fromAddress, $fromName))
                ->to($toAddress)
                ->subject($subject)
                ->html($htmlBody);

            // RFC822 formatted message
            $rawMessage = $email->toString();

            // Common IONOS Sent folder paths
            $sentMailboxes = [
                '{imap.ionos.co.uk:993/imap/ssl}Sent',
                '{imap.ionos.co.uk:993/imap/ssl}INBOX.Sent',
                '{imap.ionos.co.uk:993/imap/ssl}Sent Items',
            ];

            foreach ($sentMailboxes as $mailboxPath) {
                $mbox = @imap_open($mailboxPath, $smtpUsername, $smtpPassword);
                if ($mbox === false) {
                    continue; // try next mailbox
                }

                $ok = @imap_append($mbox, $mailboxPath, $rawMessage);
                @imap_close($mbox);

                if ($ok) {
                    Log::info("Appended sent message to IMAP folder: {$mailboxPath}");
                    return;
                }
            }

            Log::warning('Failed to append message to any IMAP Sent folder.');
        } catch (Exception $e) {
            Log::warning('IMAP append to Sent failed: ' . $e->getMessage());
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
            $this->recomputeCampaignStatus($recipient->campaign_id);
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
