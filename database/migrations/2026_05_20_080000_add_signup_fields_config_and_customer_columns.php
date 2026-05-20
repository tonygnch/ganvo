<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Per-store toggles for which extra fields the customer signup form
        // collects. Shape (keyed by field name):
        //   { phone:            {enabled: bool, required: bool},
        //     birthday:         {enabled: bool, required: bool},
        //     shipping_address: {enabled: bool, required: bool},
        //     marketing_optin:  {enabled: bool, required: bool} }
        Schema::table('stores', function (Blueprint $table) {
            $table->json('signup_fields')->nullable()->after('hero_banner');
        });

        // Customer-side columns. shipping_address already lives in the
        // existing default_shipping_address JSON column, so we don't add
        // a duplicate. marketing_optin_at = null means "not opted in";
        // a timestamp records the moment they consented.
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone', 32)->nullable()->after('email_verified_at');
            $table->date('birthday')->nullable()->after('phone');
            $table->timestamp('marketing_optin_at')->nullable()->after('birthday');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('signup_fields');
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['phone', 'birthday', 'marketing_optin_at']);
        });
    }
};
