<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // What Stripe is still waiting for on a Connect account:
            // {"due": [...requirement fields...], "failed": [...fields whose
            // verification failed...]}. stripe_connect_disabled_reason only says
            // THAT an account is restricted ('requirements.past_due'); this is
            // what the Payments page needs to tell the owner what to fix.
            // Nullable and additive, so it is safe to deploy without downtime.
            $table->json('stripe_connect_requirements')->nullable()->after('stripe_connect_disabled_reason');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('stripe_connect_requirements');
        });
    }
};
