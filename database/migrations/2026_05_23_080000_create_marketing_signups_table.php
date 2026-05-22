<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email signups from the coming-soon page. One row per unique email
 * address; signing up twice with the same email is a no-op (handled by the
 * unique constraint + idempotent controller logic).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_signups', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            // The locale the visitor was viewing the splash in when they
            // signed up — useful for sending the launch email in the right
            // language.
            $table->string('locale', 8)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_signups');
    }
};
