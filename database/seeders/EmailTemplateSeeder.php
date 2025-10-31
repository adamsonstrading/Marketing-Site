<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Template 1: Business Loan Email Template
        EmailTemplate::create([
            'name' => 'Business Loan Promotion',
            'subject' => 'Get Your Business Loan Approved Today!',
            'description' => 'Professional business loan promotion template',
            'is_active' => true,
            'body' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #1a4780; padding: 30px 40px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">Business Loan 4U</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #1a4780; margin: 0 0 20px 0; font-size: 24px;">Get Your Business Loan Approved Today!</h2>
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Dear {{recipient_name}},
                            </p>
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Are you looking to grow your business? We understand the challenges you face, and we\'re here to help!
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                                <tr>
                                    <td style="background-color: #f8f9fa; padding: 20px; border-left: 4px solid #1a4780;">
                                        <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0;">
                                            <strong>Why Choose Us?</strong><br>
                                            ✓ Fast approval process<br>
                                            ✓ Competitive interest rates<br>
                                            ✓ Flexible repayment options<br>
                                            ✓ Dedicated support team
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Don\'t let funding hold your business back. Apply now and take the next step towards success!
                            </p>
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding: 20px 0;">
                                        <a href="#" style="background-color: #1a4780; color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 5px; display: inline-block; font-size: 16px; font-weight: bold;">Apply Now</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 40px; text-align: center; border-radius: 0 0 8px 8px;">
                            <p style="color: #666666; font-size: 14px; margin: 0 0 10px 0;">
                                Business Loan 4U<br>
                                Email: marketing@businessloans4u.co.uk
                            </p>
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                © ' . date('Y') . ' Business Loan 4U. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>',
        ]);

        // Template 2: Marketing Newsletter Template
        EmailTemplate::create([
            'name' => 'Newsletter Template',
            'subject' => 'Your Monthly Business Insights',
            'description' => 'Professional newsletter template with multiple sections',
            'is_active' => true,
            'body' => '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 32px; font-weight: bold;">Monthly Newsletter</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 18px; opacity: 0.9;">Stay Updated with Business Insights</p>
                        </td>
                    </tr>
                    <!-- Content Section 1 -->
                    <tr>
                        <td style="padding: 40px;">
                            <h2 style="color: #333333; margin: 0 0 20px 0; font-size: 22px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">Latest Updates</h2>
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Hello {{recipient_name}},
                            </p>
                            <p style="color: #333333; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                We hope this newsletter finds you well. Here are the latest insights and updates to help your business thrive.
                            </p>
                        </td>
                    </tr>
                    <!-- Feature Boxes -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="48%" style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; vertical-align: top;">
                                        <h3 style="color: #667eea; margin: 0 0 10px 0; font-size: 18px;">Feature 1</h3>
                                        <p style="color: #333333; font-size: 14px; line-height: 1.6; margin: 0;">
                                            Discover new opportunities for your business growth and expansion.
                                        </p>
                                    </td>
                                    <td width="4%"></td>
                                    <td width="48%" style="background-color: #f8f9fa; padding: 20px; border-radius: 5px; vertical-align: top;">
                                        <h3 style="color: #667eea; margin: 0 0 10px 0; font-size: 18px;">Feature 2</h3>
                                        <p style="color: #333333; font-size: 14px; line-height: 1.6; margin: 0;">
                                            Access exclusive resources and tools designed for your success.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Call to Action -->
                    <tr>
                        <td style="padding: 0 40px 40px 40px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #667eea; border-radius: 5px;">
                                <tr>
                                    <td align="center" style="padding: 30px;">
                                        <h3 style="color: #ffffff; margin: 0 0 15px 0; font-size: 20px;">Ready to Take Action?</h3>
                                        <p style="color: #ffffff; font-size: 16px; margin: 0 0 20px 0; opacity: 0.9;">
                                            Get started today and unlock new possibilities for your business.
                                        </p>
                                        <a href="#" style="background-color: #ffffff; color: #667eea; text-decoration: none; padding: 12px 30px; border-radius: 5px; display: inline-block; font-size: 16px; font-weight: bold;">Learn More</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 30px 40px; text-align: center; border-radius: 0 0 8px 8px;">
                            <p style="color: #666666; font-size: 14px; margin: 0 0 10px 0;">
                                <strong>Business Loan 4U Marketing</strong><br>
                                marketing@businessloans4u.co.uk
                            </p>
                            <p style="color: #999999; font-size: 12px; margin: 0;">
                                You are receiving this email because you opted in to receive our newsletter.<br>
                                © ' . date('Y') . ' Business Loan 4U. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>',
        ]);
    }
}

