<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes for campaigns table
        Schema::table('campaigns', function (Blueprint $table) {
            if (!$this->hasIndex('campaigns', 'campaigns_status_index')) {
                $table->index('status', 'campaigns_status_index');
            }
            if (!$this->hasIndex('campaigns', 'campaigns_created_at_index')) {
                $table->index('created_at', 'campaigns_created_at_index');
            }
            if (!$this->hasIndex('campaigns', 'campaigns_sender_id_index')) {
                $table->index('sender_id', 'campaigns_sender_id_index');
            }
        });

        // Add indexes for recipients table
        Schema::table('recipients', function (Blueprint $table) {
            if (!$this->hasIndex('recipients', 'recipients_campaign_id_index')) {
                $table->index('campaign_id', 'recipients_campaign_id_index');
            }
            if (!$this->hasIndex('recipients', 'recipients_status_index')) {
                $table->index('status', 'recipients_status_index');
            }
            if (!$this->hasIndex('recipients', 'recipients_campaign_status_index')) {
                $table->index(['campaign_id', 'status'], 'recipients_campaign_status_index');
            }
            if (!$this->hasIndex('recipients', 'recipients_created_at_index')) {
                $table->index('created_at', 'recipients_created_at_index');
            }
        });

        // Add indexes for jobs table
        Schema::table('jobs', function (Blueprint $table) {
            if (!$this->hasIndex('jobs', 'jobs_queue_index')) {
                $table->index('queue', 'jobs_queue_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_status_index');
            $table->dropIndex('campaigns_created_at_index');
            $table->dropIndex('campaigns_sender_id_index');
        });

        Schema::table('recipients', function (Blueprint $table) {
            $table->dropIndex('recipients_campaign_id_index');
            $table->dropIndex('recipients_status_index');
            $table->dropIndex('recipients_campaign_status_index');
            $table->dropIndex('recipients_created_at_index');
        });

        Schema::table('jobs', function (Blueprint $table) {
            $table->dropIndex('jobs_queue_index');
        });
    }

    /**
     * Check if an index exists
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        
        $result = $connection->select(
            "SELECT COUNT(*) as count FROM information_schema.statistics 
             WHERE table_schema = ? AND table_name = ? AND index_name = ?",
            [$databaseName, $table, $indexName]
        );
        
        return $result[0]->count > 0;
    }
};

