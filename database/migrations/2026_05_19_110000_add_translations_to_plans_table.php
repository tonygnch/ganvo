<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Per-locale overrides for name / tagline / features. Stored as a
            // numeric-indexed array of {locale, name, tagline, features}, which
            // is the natural shape Filament's Repeater produces. The English
            // values on the main columns are the fallback.
            $table->json('translations')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('translations');
        });
    }
};
