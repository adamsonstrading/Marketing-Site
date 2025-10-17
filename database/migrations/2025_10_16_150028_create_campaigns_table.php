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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('senders')->onDelete('cascade');
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->integer('total_recipients')->default(0);
            $table->enum('status', ['draft', 'queued', 'sending', 'completed'])->default('draft');
            $table->timestamps();
            
            $table->index(['status']);
            $table->index(['sender_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
