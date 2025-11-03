<?php

namespace App\Services;

use App\Models\SmtpConfiguration;
use Illuminate\Support\Facades\Log;

class SmtpRotationService
{
    /**
     * Get the best SMTP configuration for sending
     * Uses round-robin with success rate consideration
     * 
     * @param int|null $campaignId Optional campaign ID for tracking
     * @return SmtpConfiguration|null
     */
    public function getNextSmtp($campaignId = null): ?SmtpConfiguration
    {
        // Get all active SMTP configurations
        $activeSmtps = SmtpConfiguration::where('is_active', true)->get();
        
        if ($activeSmtps->isEmpty()) {
            Log::warning('No active SMTP configurations available');
            return null;
        }

        // If only one SMTP, return it
        if ($activeSmtps->count() === 1) {
            return $activeSmtps->first();
        }

        // Filter out SMTPs that have reached daily limit
        $availableSmtps = $activeSmtps->filter(function ($smtp) {
            if (!$smtp->daily_limit) {
                return true; // No limit set
            }
            
            // Reset daily count if it's a new day
            if ($smtp->daily_reset_date && $smtp->daily_reset_date < today()) {
                $smtp->update([
                    'daily_count' => 0,
                    'daily_reset_date' => today()
                ]);
                return true;
            }
            
            return $smtp->daily_count < $smtp->daily_limit;
        });

        // If no SMTPs available (all at limit), return the one with highest success rate
        if ($availableSmtps->isEmpty()) {
            Log::warning('All SMTP configurations have reached daily limit, using best available');
            return $activeSmtps->sortByDesc('success_rate')->first();
        }

        // Sort by success rate (descending), then by total sent (ascending) for load balancing
        $sortedSmtps = $availableSmtps->sortBy([
            ['success_rate', 'desc'],
            ['total_sent', 'asc']
        ]);

        return $sortedSmtps->first();
    }

    /**
     * Rotate to next SMTP based on round-robin or load balancing
     * 
     * @param array $excludeIds SMTP IDs to exclude
     * @return SmtpConfiguration|null
     */
    public function rotate(array $excludeIds = []): ?SmtpConfiguration
    {
        $activeSmtps = SmtpConfiguration::where('is_active', true)
            ->whereNotIn('id', $excludeIds)
            ->get();

        if ($activeSmtps->isEmpty()) {
            return null;
        }

        // Get SMTP with least usage (for round-robin effect)
        return $activeSmtps->sortBy('total_sent')->first();
    }

    /**
     * Record successful send
     */
    public function recordSuccess(SmtpConfiguration $smtp): void
    {
        $smtp->increment('total_sent');
        $smtp->increment('daily_count');
        $smtp->update([
            'last_used_at' => now(),
            'daily_reset_date' => today()
        ]);

        // Recalculate success rate
        $this->updateSuccessRate($smtp);
    }

    /**
     * Record failed send
     */
    public function recordFailure(SmtpConfiguration $smtp): void
    {
        $smtp->increment('total_failed');
        $smtp->update([
            'last_used_at' => now()
        ]);

        // Recalculate success rate
        $this->updateSuccessRate($smtp);
    }

    /**
     * Update success rate for SMTP configuration
     */
    private function updateSuccessRate(SmtpConfiguration $smtp): void
    {
        $total = $smtp->total_sent + $smtp->total_failed;
        
        if ($total > 0) {
            $successRate = round(($smtp->total_sent / $total) * 100);
            $smtp->update(['success_rate' => $successRate]);
        }
    }

    /**
     * Check if SMTP can be used (not at limit, active, etc.)
     */
    public function canUse(SmtpConfiguration $smtp): bool
    {
        if (!$smtp->is_active) {
            return false;
        }

        // Check daily limit
        if ($smtp->daily_limit) {
            // Reset if new day
            if ($smtp->daily_reset_date && $smtp->daily_reset_date < today()) {
                $smtp->update([
                    'daily_count' => 0,
                    'daily_reset_date' => today()
                ]);
                return true;
            }
            
            if ($smtp->daily_count >= $smtp->daily_limit) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all available SMTPs (not at limit)
     */
    public function getAvailableSmtps(): \Illuminate\Database\Eloquent\Collection
    {
        return SmtpConfiguration::where('is_active', true)
            ->get()
            ->filter(function ($smtp) {
                return $this->canUse($smtp);
            });
    }

    /**
     * Reset daily counts (run via cron)
     */
    public function resetDailyCounts(): void
    {
        SmtpConfiguration::where('daily_reset_date', '<', today())
            ->orWhereNull('daily_reset_date')
            ->update([
                'daily_count' => 0,
                'daily_reset_date' => today()
            ]);
    }
}

