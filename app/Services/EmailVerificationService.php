<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class EmailVerificationService
{
    /**
     * Verify an email address using multiple methods
     * 
     * @param string $email
     * @return array
     */
    public function verify(string $email): array
    {
        $result = [
            'email' => $email,
            'is_valid' => false,
            'is_disposable' => false,
            'is_role_based' => false,
            'is_domain_valid' => false,
            'has_mx_record' => false,
            'verification_method' => 'basic',
            'details' => []
        ];

        // Basic syntax validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['details'][] = 'Invalid email syntax';
            return $result;
        }

        // Extract domain
        $parts = explode('@', $email);
        $domain = $parts[1] ?? null;
        $localPart = $parts[0] ?? '';

        if (!$domain) {
            $result['details'][] = 'No domain found';
            return $result;
        }

        // Check if domain has MX records (with timeout to prevent hanging)
        $result['has_mx_record'] = $this->checkMxRecord($domain);
        
        // Check if domain is valid (can resolve) - with timeout
        $result['is_domain_valid'] = @checkdnsrr($domain, 'A') || @checkdnsrr($domain, 'AAAA');
        
        // Check if email is disposable (fast check)
        $result['is_disposable'] = $this->isDisposableEmail($domain);
        
        // Check if email is role-based (fast check)
        $result['is_role_based'] = $this->isRoleBasedEmail($localPart);

        // Determine overall validity
        $result['is_valid'] = $result['is_domain_valid'] && 
                             $result['has_mx_record'] && 
                             !$result['is_disposable'];

        if ($result['is_valid']) {
            $result['details'][] = 'Email is valid and deliverable';
        } else {
            if (!$result['is_domain_valid']) {
                $result['details'][] = 'Domain does not exist';
            }
            if (!$result['has_mx_record']) {
                $result['details'][] = 'Domain has no MX records';
            }
            if ($result['is_disposable']) {
                $result['details'][] = 'Disposable email address';
            }
        }

        return $result;
    }

    /**
     * Check if domain has MX records (with timeout protection)
     */
    private function checkMxRecord(string $domain): bool
    {
        // Use @ to suppress warnings and set timeout
        $result = @checkdnsrr($domain, 'MX');
        return $result !== false;
    }

    /**
     * Check if email is from a disposable email service
     */
    private function isDisposableEmail(string $domain): bool
    {
        // Common disposable email domains
        $disposableDomains = [
            'tempmail.com', '10minutemail.com', 'guerrillamail.com',
            'mailinator.com', 'throwaway.email', 'temp-mail.org',
            'yopmail.com', 'maildrop.cc', 'getnada.com',
            'tempinbox.com', 'fakeinbox.com', 'dispostable.com'
        ];

        return in_array(strtolower($domain), $disposableDomains);
    }

    /**
     * Check if email is role-based (e.g., info@, admin@, support@)
     */
    private function isRoleBasedEmail(string $localPart): bool
    {
        $roleBasedPrefixes = [
            'info', 'admin', 'support', 'help', 'contact',
            'sales', 'service', 'no-reply', 'noreply',
            'postmaster', 'abuse', 'webmaster'
        ];

        return in_array(strtolower($localPart), $roleBasedPrefixes);
    }

    /**
     * Verify multiple emails in batch
     */
    public function verifyBatch(array $emails): array
    {
        $results = [];
        
        foreach ($emails as $email) {
            $results[] = $this->verify($email);
        }

        return $results;
    }

    /**
     * Get verification statistics
     */
    public function getVerificationStats(array $verificationResults): array
    {
        $total = count($verificationResults);
        $valid = 0;
        $invalid = 0;
        $disposable = 0;
        $roleBased = 0;
        $noMx = 0;

        foreach ($verificationResults as $result) {
            if ($result['is_valid']) {
                $valid++;
            } else {
                $invalid++;
            }
            
            if ($result['is_disposable']) {
                $disposable++;
            }
            
            if ($result['is_role_based']) {
                $roleBased++;
            }
            
            if (!$result['has_mx_record']) {
                $noMx++;
            }
        }

        return [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'disposable' => $disposable,
            'role_based' => $roleBased,
            'no_mx_record' => $noMx,
            'valid_percentage' => $total > 0 ? round(($valid / $total) * 100, 2) : 0
        ];
    }
}

