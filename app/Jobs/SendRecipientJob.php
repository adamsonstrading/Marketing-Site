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
                ->send(new CampaignMail($campaign->subject, $campaign->body));

            // Update recipient status on success
            $recipient->update([
                'status' => 'sent',
                'sent_at' => now(),
                'last_error' => null,
            ]);

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
        }
    }
}
