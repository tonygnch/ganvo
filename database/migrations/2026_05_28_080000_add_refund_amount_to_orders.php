<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // refund_amount_cents tracks cumulative refunds against the
        // order — supports partial refunds (e.g. customer returned one
        // item of three). Compared against total_cents:
        //   == 0           : no refund
        //   == total_cents : fully refunded (status flips to 'refunded')
        //   between        : partially refunded (status stays paid/shipped,
        //                    UI surfaces the refunded amount separately)
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('refund_amount_cents')->default(0)->after('platform_fee_cents');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('refund_amount_cents');
        });
    }
};
