<?php

namespace Database\Seeders;

use App\Models\SmtpConfiguration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SmtpConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Finda Property Marketing SMTP Configuration
        SmtpConfiguration::create([
            'name' => 'Finda Property Marketing',
            'host' => 'smtp.ionos.co.uk',
            'port' => 587,
            'username' => 'marketing@findaproperty.io',
            'password' => 'Adamsons@514',
            'from_address' => 'marketing@findaproperty.io',
            'from_name' => 'Finda Property Marketing',
            'encryption' => 'tls',
            'is_active' => true,
            'is_default' => true,
            'description' => 'SMTP configuration for Finda Property marketing emails'
        ]);

        // Business Loan 4U Marketing SMTP Configuration
        SmtpConfiguration::create([
            'name' => 'Business Loan 4U Marketing',
            'host' => 'smtp.ionos.co.uk',
            'port' => 587,
            'username' => 'marketing@businessloan4u.co.uk',
            'password' => 'Adamsons@514',
            'from_address' => 'marketing@businessloan4u.co.uk',
            'from_name' => 'Business Loan 4U Marketing',
            'encryption' => 'tls',
            'is_active' => true,
            'is_default' => false,
            'description' => 'SMTP configuration for Business Loan 4U marketing emails'
        ]);

        // Default SMTP Configuration (for testing)
        SmtpConfiguration::create([
            'name' => 'Default SMTP',
            'host' => 'smtp.mailtrap.io',
            'port' => 2525,
            'username' => 'your_username',
            'password' => 'your_password',
            'from_address' => 'test@example.com',
            'from_name' => 'Email Agent Test',
            'encryption' => 'tls',
            'is_active' => false,
            'is_default' => false,
            'description' => 'Default SMTP configuration for testing purposes'
        ]);
    }
}