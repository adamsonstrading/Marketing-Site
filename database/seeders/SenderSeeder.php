<?php

namespace Database\Seeders;

use App\Models\Sender;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing senders
        Sender::query()->delete();

        // Finda Property Marketing Sender
        Sender::create([
            'name' => 'Finda Property Marketing',
            'email' => 'marketing@findaproperty.io',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'marketing@findaproperty.io',
            'smtp_password' => 'Adamsons@514',
            'smtp_encryption' => 'tls',
            'from_name' => 'Finda Property Marketing',
            'from_address' => 'marketing@findaproperty.io',
        ]);

        // Business Loan 4U Marketing Sender
        Sender::create([
            'name' => 'Business Loan 4U Marketing',
            'email' => 'marketing@businessloan4u.co.uk',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'marketing@businessloan4u.co.uk',
            'smtp_password' => 'Adamsons@514',
            'smtp_encryption' => 'tls',
            'from_name' => 'Business Loan 4U Marketing',
            'from_address' => 'marketing@businessloan4u.co.uk',
        ]);
    }
}