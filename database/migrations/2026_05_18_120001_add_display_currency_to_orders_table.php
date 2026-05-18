<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // What currency the customer was viewing prices in at checkout.
            // May be different from `currency` (the actually-charged base currency).
            $table->string('display_currency', 3)->nullable()->after('currency');
            // The total in display currency at checkout time, so receipts
            // forever show what the customer saw — even if FX rates change later.
            $table->unsignedInteger('display_total_cents')->nullable()->after('display_currency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['display_currency', 'display_total_cents']);
        });
    }
};
