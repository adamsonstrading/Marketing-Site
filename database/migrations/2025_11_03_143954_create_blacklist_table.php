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
        Schema::create('blacklist', function (Blueprint $table) {
            $table->id();
            $table->string('email')->nullable(); // Specific email address
            $table->string('domain')->nullable(); // Domain (e.g., example.com)
            $table->text('reason')->nullable(); // Why blacklisted
            $table->timestamps();
            
            $table->index(['email']);
            $table->index(['domain']);
            
            // Ensure at least one of email or domain is set
            // Unique constraint on email when not null
            $table->unique('email', 'blacklist_email_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blacklist');
    }
};
