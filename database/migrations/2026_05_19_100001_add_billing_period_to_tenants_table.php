<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // 'monthly' | 'yearly' — chosen at the plan step of onboarding,
            // also editable in the SA tenant view. Defaults to monthly.
            $table->string('billing_period', 10)->default('monthly')->after('subscription_plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('billing_period');
        });
    }
};
