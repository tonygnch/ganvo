<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-panel branding, kept separate from storefront branding.
 *
 * `logo_path` / `primary_color` already exist on stores but drive the
 * *customer-facing storefront*. These two columns let a merchant brand
 * their own StoreAdmin (Filament) workspace — logo in the header + an
 * accent color for buttons/links/active nav — without touching how their
 * shop looks to shoppers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('admin_logo_path')->nullable()->after('logo_path');
            $table->string('admin_accent_color')->nullable()->after('admin_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['admin_logo_path', 'admin_accent_color']);
        });
    }
};
