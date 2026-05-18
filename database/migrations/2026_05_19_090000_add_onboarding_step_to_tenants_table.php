<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Current wizard step the merchant is on. Values:
            //   business → plan → theme → customize → products → launch → done
            $table->string('onboarding_step', 20)->default('business')->after('onboarding_progress');
        });

        // Existing seeded tenants (with `onboarded_at` already set) skip the
        // wizard. Everyone else lands on the first step.
        DB::table('tenants')
            ->whereNotNull('onboarded_at')
            ->update(['onboarding_step' => 'done']);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('onboarding_step');
        });
    }
};
