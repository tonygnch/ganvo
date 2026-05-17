<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            // 'guest' | 'account' | 'both'
            $table->string('checkout_mode', 16)->default('both')->after('font_family');
            $table->boolean('allow_registration')->default(true)->after('checkout_mode');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn(['checkout_mode', 'allow_registration']);
        });
    }
};
