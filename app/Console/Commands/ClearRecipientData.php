<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearRecipientData extends Command
{
    protected $signature = 'data:clear-recipients {--force : Force deletion without confirmation}';
    protected $description = 'Clear all recipient data from the database';

    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will delete ALL recipients, queue jobs, and failed jobs. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Clearing all recipient data...');

        try {
            // Get counts before deletion
            $recipientCount = DB::table('recipients')->count();
            $jobCount = DB::table('jobs')->where('queue', 'emails')->count();
            $failedCount = DB::table('failed_jobs')->count();

            $this->info("Found: {$recipientCount} recipients, {$jobCount} queue jobs, {$failedCount} failed jobs");

            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Truncate recipients
            $this->info('Deleting recipients...');
            DB::table('recipients')->truncate();
            $this->info('✓ Recipients cleared');

            // Clear queue
            $this->info('Clearing queue jobs...');
            DB::table('jobs')->where('queue', 'emails')->delete();
            $this->info('✓ Queue cleared');

            // Clear failed jobs
            $this->info('Clearing failed jobs...');
            DB::table('failed_jobs')->truncate();
            $this->info('✓ Failed jobs cleared');

            // Reset campaign counts
            $this->info('Resetting campaign recipient counts...');
            DB::table('campaigns')->update(['total_recipients' => 0]);
            $this->info('✓ Campaign counts reset');

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Verify
            $remainingRecipients = DB::table('recipients')->count();
            $remainingJobs = DB::table('jobs')->where('queue', 'emails')->count();
            $remainingFailed = DB::table('failed_jobs')->count();

            $this->info("\n=== Verification ===");
            $this->info("Remaining recipients: {$remainingRecipients}");
            $this->info("Remaining queue jobs: {$remainingJobs}");
            $this->info("Remaining failed jobs: {$remainingFailed}");

            if ($remainingRecipients === 0 && $remainingJobs === 0) {
                $this->info("\n✓ All recipient data cleared successfully!");
                return 0;
            } else {
                $this->warn("\n⚠️  Some data may still remain.");
                return 1;
            }

        } catch (\Exception $e) {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}

