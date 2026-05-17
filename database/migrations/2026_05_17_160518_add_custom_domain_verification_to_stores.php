<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('custom_domain_verification_token')->nullable()->after('custom_domain');
            $table->timestamp('custom_domain_verified_at')->nullable()->after('custom_domain_verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['custom_domain_verification_token', 'custom_domain_verified_at']);
        });
    }
};
