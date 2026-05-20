<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('stripe_price_id_monthly')->nullable()->after('price_yearly_cents');
            $table->string('stripe_price_id_yearly')->nullable()->after('stripe_price_id_monthly');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id_monthly', 'stripe_price_id_yearly']);
        });
    }
};
