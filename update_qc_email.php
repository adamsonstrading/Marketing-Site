<?php

/**
 * Script to update QC email from qc@businessloans.co.uk to qc@businessloans4u.co.uk
 * Run: php update_qc_email.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SmtpConfiguration;
use App\Models\Sender;

try {
    echo "Updating QC email address from qc@businessloans.co.uk to qc@businessloans4u.co.uk...\n\n";
    
    // Update SMTP Configurations
    $smtpUpdated = SmtpConfiguration::where('from_address', 'qc@businessloans.co.uk')
        ->orWhere('username', 'qc@businessloans.co.uk')
        ->update([
            'from_address' => 'qc@businessloans4u.co.uk',
            'username' => 'qc@businessloans4u.co.uk',
        ]);
    
    echo "✅ Updated {$smtpUpdated} SMTP configuration(s)\n";
    
    // Update Senders
    $senderUpdated = Sender::where('email', 'qc@businessloans.co.uk')
        ->orWhere('smtp_username', 'qc@businessloans.co.uk')
        ->orWhere('from_address', 'qc@businessloans.co.uk')
        ->update([
            'email' => 'qc@businessloans4u.co.uk',
            'smtp_username' => 'qc@businessloans4u.co.uk',
            'from_address' => 'qc@businessloans4u.co.uk',
        ]);
    
    echo "✅ Updated {$senderUpdated} Sender(s)\n";
    
    // Verify the update
    $smtpCount = SmtpConfiguration::where('from_address', 'qc@businessloans4u.co.uk')->count();
    $senderCount = Sender::where('email', 'qc@businessloans4u.co.uk')->count();
    
    echo "\n📊 Verification:\n";
    echo "   SMTP configurations with new email: {$smtpCount}\n";
    echo "   Senders with new email: {$senderCount}\n";
    
    // Check for any remaining old emails
    $oldSmtp = SmtpConfiguration::where('from_address', 'qc@businessloans.co.uk')
        ->orWhere('username', 'qc@businessloans.co.uk')
        ->count();
    $oldSender = Sender::where('email', 'qc@businessloans.co.uk')
        ->orWhere('smtp_username', 'qc@businessloans.co.uk')
        ->orWhere('from_address', 'qc@businessloans.co.uk')
        ->count();
    
    if ($oldSmtp > 0 || $oldSender > 0) {
        echo "\n⚠️  Warning: Found {$oldSmtp} old SMTP and {$oldSender} old Sender records\n";
    } else {
        echo "\n✅ All records updated successfully!\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

