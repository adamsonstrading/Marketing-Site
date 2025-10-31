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
    }
}
