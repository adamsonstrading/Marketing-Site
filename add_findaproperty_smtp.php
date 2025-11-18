<?php

/**
 * Script to add Finda Property SMTP configuration
 * Run: php add_findaproperty_smtp.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SmtpConfiguration;
use App\Models\Sender;

try {
    echo "Adding Finda Property SMTP configuration...\n\n";
    
    // Check if SMTP already exists
    $existing = SmtpConfiguration::where('from_address', 'qc@findaproperty.io')->first();
    
    if ($existing) {
        echo "SMTP configuration with email qc@findaproperty.io already exists.\n";
        echo "Updating existing configuration...\n";
        
        $existing->update([
            'name' => 'Finda Property QC',
            'host' => 'smtp.ionos.co.uk',
            'port' => 587,
            'username' => 'qc@findaproperty.io',
            'password' => 'Adamsons@514@2025',
            'from_address' => 'qc@findaproperty.io',
            'from_name' => 'Finda Property',
            'encryption' => 'tls',
            'is_active' => true,
        ]);
        
        echo "✅ SMTP configuration updated successfully!\n";
    } else {
        // Create new SMTP configuration
        $smtp = SmtpConfiguration::create([
            'name' => 'Finda Property QC',
            'host' => 'smtp.ionos.co.uk',
            'port' => 587,
            'username' => 'qc@findaproperty.io',
            'password' => 'Adamsons@514@2025',
            'from_address' => 'qc@findaproperty.io',
            'from_name' => 'Finda Property',
            'encryption' => 'tls',
            'is_active' => true,
            'is_default' => false,
        ]);
        
        echo "✅ SMTP configuration created successfully!\n";
        echo "   ID: {$smtp->id}\n";
    }
    
    // Create or update sender
    $sender = Sender::where('email', 'qc@findaproperty.io')->first();
    
    if ($sender) {
        $sender->update([
            'name' => 'Finda Property',
            'email' => 'qc@findaproperty.io',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'qc@findaproperty.io',
            'smtp_password' => 'Adamsons@514@2025',
            'smtp_encryption' => 'tls',
            'from_name' => 'Finda Property',
            'from_address' => 'qc@findaproperty.io',
        ]);
        echo "✅ Sender updated successfully!\n";
    } else {
        Sender::create([
            'name' => 'Finda Property',
            'email' => 'qc@findaproperty.io',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'qc@findaproperty.io',
            'smtp_password' => 'Adamsons@514@2025',
            'smtp_encryption' => 'tls',
            'from_name' => 'Finda Property',
            'from_address' => 'qc@findaproperty.io',
        ]);
        echo "✅ Sender created successfully!\n";
    }
    
    echo "\n✅ All done! The Finda Property SMTP configuration is now available in your campaign form.\n";
    echo "   You can find it in the SMTP Configuration dropdown as: 'Finda Property QC (qc@findaproperty.io)'\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

