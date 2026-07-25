<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-storefront HTTP Basic lock, so ONE client's site can be previewed on a
 * public subdomain while every other tenant stays open.
 *
 * The password is stored only as a bcrypt hash; nothing here ever holds the
 * plaintext. Nullable throughout so existing stores are untouched and default
 * to open.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->boolean('preview_lock')->default(false)->after('order_flow');
            $table->string('preview_user')->nullable()->after('preview_lock');
            $table->string('preview_password_hash')->nullable()->after('preview_user');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['preview_lock', 'preview_user', 'preview_password_hash']);
        });
    }
};
