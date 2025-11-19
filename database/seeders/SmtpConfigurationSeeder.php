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
        // Business Loan 4U Marketing SMTP Configuration (IONOS)
        SmtpConfiguration::create([
            'name' => 'Business Loan 4U Marketing',
            'host' => 'smtp.ionos.co.uk',
            'port' => 587,
            'username' => 'marketing@businessloans4u.co.uk',
            'password' => 'Adamsons@514',
            'from_address' => 'marketing@businessloans4u.co.uk',
            'from_name' => 'Business Loan 4U Marketing',
            'encryption' => 'tls',
            'is_active' => true,
            'is_default' => true,
            'description' => 'SMTP configuration for Business Loan 4U marketing emails via IONOS'
        ]);

        // Finda Property Marketing SMTP Configuration (IONOS)
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
            'is_default' => false,
            'description' => 'SMTP configuration for Finda Property marketing emails via IONOS'
        ]);

        // Business Loans QC SMTP Configuration (IONOS)
        SmtpConfiguration::create([
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
            'description' => 'SMTP configuration for Business Loans quality control emails via IONOS'
        ]);

        // Finda Property QC SMTP Configuration (IONOS)
        SmtpConfiguration::create([
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
            'description' => 'SMTP configuration for Finda Property quality control emails via IONOS'
        ]);
    }
}
