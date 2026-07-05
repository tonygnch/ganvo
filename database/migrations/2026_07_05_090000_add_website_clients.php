<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website clients — the hub side of the platform. A tenant is either a
 * 'store' (self-serve storefront, the existing flow) or a 'website'
 * (a hand-built client site hosted OUTSIDE the platform; Ganvo manages
 * the client relationship: registry, billing, status). Website tenants
 * have no Store row; their metadata lives in `websites`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('type', 20)->default('store')->after('slug')->index();
        });

        Schema::create('websites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('url')->nullable();          // live production URL
            $table->string('repo_url')->nullable();     // where the code lives
            $table->string('stack')->nullable();        // e.g. "Laravel 8 + Blade"
            $table->text('notes')->nullable();          // hosting, credentials pointers, quirks
            $table->string('last_status', 20)->nullable();   // up | down | unknown
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('websites');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
