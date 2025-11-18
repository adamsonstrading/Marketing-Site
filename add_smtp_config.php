<?php

/**
 * Script to add new SMTP configuration
 * Run: php add_smtp_config.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SmtpConfiguration;
use App\Models\Sender;

try {
    // Check if SMTP already exists
    $existing = SmtpConfiguration::where('from_address', 'qc@businessloans4u.co.uk')->first();
    
    if ($existing) {
        echo "SMTP configuration with email qc@businessloans4u.co.uk already exists.\n";
        echo "Updating existing configuration...\n";
        
        $existing->update([
            'name' => 'Business Loans QC',
            'host' => 'smtp.ionos.co.uk',
            'port' => 587,
            'username' => 'qc@businessloans4u.co.uk',
            'password' => 'Adamsons@514@2025',
            'from_address' => 'qc@businessloans4u.co.uk',
            'from_name' => 'Business Loans',
            'encryption' => 'tls',
            'is_active' => true,
        ]);
        
        echo "SMTP configuration updated successfully!\n";
    } else {
        // Create new SMTP configuration
        $smtp = SmtpConfiguration::create([
            'name' => 'Business Loans QC',
            'host' => 'smtp.ionos.co.uk',
            'port' => 587,
            'username' => 'qc@businessloans4u.co.uk',
            'password' => 'Adamsons@514@2025',
            'from_address' => 'qc@businessloans4u.co.uk',
            'from_name' => 'Business Loans',
            'encryption' => 'tls',
            'is_active' => true,
            'is_default' => false,
        ]);
        
        echo "SMTP configuration created successfully!\n";
        echo "ID: {$smtp->id}\n";
    }
    
    // Create or update sender
    $sender = Sender::where('email', 'qc@businessloans4u.co.uk')->first();
    
    if ($sender) {
        $sender->update([
            'name' => 'Business Loans',
            'email' => 'qc@businessloans4u.co.uk',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'qc@businessloans4u.co.uk',
            'smtp_password' => 'Adamsons@514@2025',
            'smtp_encryption' => 'tls',
            'from_name' => 'Business Loans',
            'from_address' => 'qc@businessloans4u.co.uk',
        ]);
        echo "Sender updated successfully!\n";
    } else {
        Sender::create([
            'name' => 'Business Loans',
            'email' => 'qc@businessloans4u.co.uk',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'qc@businessloans4u.co.uk',
            'smtp_password' => 'Adamsons@514@2025',
            'smtp_encryption' => 'tls',
            'from_name' => 'Business Loans',
            'from_address' => 'qc@businessloans4u.co.uk',
        ]);
        echo "Sender created successfully!\n";
    }
    
    echo "\n✅ All done! The SMTP configuration is now available in your campaign form.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

