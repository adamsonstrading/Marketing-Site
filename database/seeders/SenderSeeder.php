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
        // Create Business Loans 4U sender
        Sender::create([
            'name' => 'Business Loans 4U',
            'email' => 'info@businessloans4u.co.uk',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'info@businessloans4u.co.uk',
            'smtp_password' => 'Adamsons@514',
            'smtp_encryption' => 'tls',
            'from_name' => 'Business Loans 4U',
            'from_address' => 'info@businessloans4u.co.uk',
        ]);

        // Create Finda Property sender
        Sender::create([
            'name' => 'Finda Property',
            'email' => 'no-reply@findaproperty.io',
            'smtp_host' => 'smtp.ionos.co.uk',
            'smtp_port' => 587,
            'smtp_username' => 'no-reply@findaproperty.io',
            'smtp_password' => 'Adamsons@514',
            'smtp_encryption' => 'tls',
            'from_name' => 'Finda Property',
            'from_address' => 'no-reply@findaproperty.io',
        ]);

        // Create a sample SendGrid sender
        Sender::create([
            'name' => 'SendGrid Sender',
            'email' => 'noreply@yourdomain.com',
            'smtp_host' => 'smtp.sendgrid.net',
            'smtp_port' => 587,
            'smtp_username' => 'apikey',
            'smtp_password' => 'YOUR_SENDGRID_API_KEY_HERE', // Replace with actual API key
            'smtp_encryption' => 'tls',
            'from_name' => 'Your Company',
            'from_address' => 'noreply@yourdomain.com',
        ]);

        // Create a sample Mailtrap sender for testing
        Sender::create([
            'name' => 'Mailtrap Test Sender',
            'email' => 'test@example.com',
            'smtp_host' => 'sandbox.smtp.mailtrap.io',
            'smtp_port' => 2525,
            'smtp_username' => 'your_mailtrap_username',
            'smtp_password' => 'your_mailtrap_password',
            'smtp_encryption' => 'tls',
            'from_name' => 'Test Sender',
            'from_address' => 'test@example.com',
        ]);
    }
}
