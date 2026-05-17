<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('carrier')->nullable()->after('status');
            $table->string('tracking_number')->nullable()->after('carrier');
            $table->string('tracking_url')->nullable()->after('tracking_number');
            $table->text('notes')->nullable()->after('tracking_url');
            $table->timestamp('shipped_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('shipped_at');
            $table->timestamp('refunded_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'carrier',
                'tracking_number',
                'tracking_url',
                'notes',
                'shipped_at',
                'cancelled_at',
                'refunded_at',
            ]);
        });
    }
};
