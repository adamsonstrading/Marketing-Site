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
        Schema::table('smtp_configurations', function (Blueprint $table) {
            $table->integer('total_sent')->default(0)->after('description');
            $table->integer('total_failed')->default(0)->after('total_sent');
            $table->integer('success_rate')->default(100)->after('total_failed'); // percentage
            $table->timestamp('last_used_at')->nullable()->after('success_rate');
            $table->integer('daily_limit')->nullable()->after('last_used_at'); // Optional daily sending limit
            $table->integer('daily_count')->default(0)->after('daily_limit');
            $table->date('daily_reset_date')->nullable()->after('daily_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('smtp_configurations', function (Blueprint $table) {
            //
        });
    }
};
