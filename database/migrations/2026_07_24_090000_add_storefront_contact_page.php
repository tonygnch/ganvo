<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Storefront contact page.
 *
 * Two pieces: the merchant-authored page content (a JSON blob on stores,
 * matching the existing announcement / nav_menu / hero_banner chrome
 * pattern) and an inbox for what visitors send. Messages are stored
 * BEFORE the notification email is attempted, so a mail-transport failure
 * can never lose an enquiry — the merchant still sees it in the panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->json('contact')->nullable()->after('hero_banner');
        });

        Schema::create('store_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // Set when a signed-in customer sends the message; guests leave it null.
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            // Curated subjects (general / quote / order / delivery / returns) —
            // kept as a plain string so a merchant-facing list can evolve.
            $table->string('subject', 60)->nullable();
            $table->text('message');
            // new → read → replied → archived
            $table->string('status', 20)->default('new');
            $table->string('locale', 8)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_messages');
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('contact');
        });
    }
};
