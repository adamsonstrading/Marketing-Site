<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify the status enum to include 'paused'
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'queued', 'sending', 'paused', 'completed') DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        DB::statement("ALTER TABLE campaigns MODIFY COLUMN status ENUM('draft', 'queued', 'sending', 'completed') DEFAULT 'draft'");
    }
};
