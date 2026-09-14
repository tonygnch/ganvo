<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How deep an office rack's desk plate is.
 *
 * The desk is a SHELF from the ordinary price list — just a deeper one than
 * the rack's own shelves, chosen by the customer in the configurator. Its
 * depth is therefore part of the rack: the same frames with a 60 cm desk
 * instead of a 40 cm one are a different rack at a different price.
 *
 * Nominal centimetres, like depth_cm. Null for every model but the office rack.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->unsignedSmallInteger('desk_depth_cm')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('rack_configurations', function (Blueprint $table) {
            $table->dropColumn('desk_depth_cm');
        });
    }
};
