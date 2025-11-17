<?php

namespace App\Console\Commands;

use App\Models\SmtpConfiguration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TestSmtp extends Command
{
    protected $signature = 'smtp:test {id? : SMTP Configuration ID}';
    protected $description = 'Test SMTP configuration by sending a test email';

    public function handle()
    {
        $id = $this->argument('id');
        
        if ($id) {
            $smtp = SmtpConfiguration::find($id);
            if (!$smtp) {
                $this->error("SMTP Configuration with ID {$id} not found.");
                return 1;
            }
            $smtps = collect([$smtp]);
        } else {
            // Test all active SMTPs
            $smtps = SmtpConfiguration::where('is_active', true)->get();
            if ($smtps->isEmpty()) {
                $this->error("No active SMTP configurations found.");
                return 1;
            }
        }
        
        $this->info("Testing " . $smtps->count() . " SMTP configuration(s)...\n");
        
        foreach ($smtps as $smtp) {
            $this->info("Testing SMTP: {$smtp->name} (ID: {$smtp->id})");
            $this->info("Host: {$smtp->host}:{$smtp->port}");
            $this->info("From: {$smtp->from_name} <{$smtp->from_address}>");
            
            try {
                // Configure mailer dynamically
                config([
                    'mail.mailers.smtp.host' => $smtp->host,
                    'mail.mailers.smtp.port' => $smtp->port,
                    'mail.mailers.smtp.username' => $smtp->username,
                    'mail.mailers.smtp.password' => $smtp->password,
                    'mail.mailers.smtp.encryption' => $smtp->encryption,
                    'mail.from.address' => $smtp->from_address,
                    'mail.from.name' => $smtp->from_name,
                ]);
                
                // Test connection by sending to the from address
                Mail::raw('This is a test email from the Email Campaign System.', function ($message) use ($smtp) {
                    $message->to($smtp->from_address)
                            ->subject('SMTP Test - ' . $smtp->name);
                });
                
                $this->info("✓ SMTP test successful!");
                
            } catch (\Exception $e) {
                $this->error("✗ SMTP test failed: " . $e->getMessage());
                Log::error("SMTP Test Failed for {$smtp->name}: " . $e->getMessage());
            }
            
            $this->info("");
        }
        
        return 0;
    }
}

