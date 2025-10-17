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
        Schema::create('senders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('smtp_host');
            $table->integer('smtp_port');
            $table->string('smtp_username');
            $table->text('smtp_password'); // Will be encrypted
            $table->string('smtp_encryption')->default('tls');
            $table->string('from_name');
            $table->string('from_address');
            $table->timestamps();
            
            $table->index(['email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('senders');
    }
};
