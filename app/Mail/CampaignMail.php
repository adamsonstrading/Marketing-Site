<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $body;
    private string $emailSubject;
    private array $variables;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $body, array $variables = [])
    {
        $this->emailSubject = $this->replaceVariables($subject, $variables);
        $this->body = $this->replaceVariables($body, $variables);
        $this->variables = $variables;
    }

    /**
     * Replace variables in content with actual values
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value ?? '', $content);
            $content = str_replace('{{ ' . $key . ' }}', $value ?? '', $content); // Support with spaces
        }
        
        return $content;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->body,
        );
    }
    
    /**
     * Build the message with custom headers for bulk email deliverability
     */
    public function build()
    {
        $email = $this->subject($this->emailSubject)->html($this->body);
        
        // Add proper headers for bulk email deliverability and compliance
        if (isset($this->variables['email'])) {
            $unsubscribeUrl = url('/unsubscribe?email=' . urlencode($this->variables['email']));
            
            $email->withSymfonyMessage(function ($message) use ($unsubscribeUrl) {
                $message->getHeaders()
                    ->addTextHeader('List-Unsubscribe', '<' . $unsubscribeUrl . '>')
                    ->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click')
                    ->addTextHeader('Precedence', 'bulk')
                    ->addTextHeader('X-Auto-Response-Suppress', 'All');
            });
        }
        
        return $email;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
