<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prices per rack type.
 *
 * Until now the plain rack, the office rack and the wine rack shared one frame
 * table and one shelf table. The merchant prices them separately now: each
 * type has its own frames and its own boards (shelves for the plain and office
 * racks, trays for the wine rack), and offers only the sizes they are priced
 * at. The three fasteners stay shared, so they carry no type.
 *
 * Nothing already priced may go missing on the storefront, so every existing
 * frame and shelf becomes the plain rack's, and the other types start from
 * copies of those prices — the office rack from the frames and shelves, the
 * wine rack from the frames (its trays are already its own). The merchant
 * changes them from there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rack_parts', function (Blueprint $table) {
            $table->string('rack_type', 12)->nullable()->after('kind');
        });

        DB::table('rack_parts')->whereIn('kind', ['frame', 'shelf'])->update(['rack_type' => 'single']);
        DB::table('rack_parts')->where('kind', 'wine_tray')->update(['rack_type' => 'wine']);

        Schema::table('rack_parts', function (Blueprint $table) {
            $table->dropUnique('rack_parts_dims_unique');
            $table->unique(
                ['tenant_id', 'kind', 'rack_type', 'height_cm', 'depth_cm', 'width_cm'],
                'rack_parts_type_dims_unique'
            );
        });

        $now = now();
        foreach (['frame' => ['office', 'wine'], 'shelf' => ['office']] as $kind => $types) {
            $rows = DB::table('rack_parts')->where('kind', $kind)->where('rack_type', 'single')->orderBy('id')->get();

            foreach ($rows as $row) {
                foreach ($types as $type) {
                    $exists = DB::table('rack_parts')
                        ->where('tenant_id', $row->tenant_id)
                        ->where('kind', $kind)
                        ->where('rack_type', $type)
                        ->where('height_cm', $row->height_cm)
                        ->where('depth_cm', $row->depth_cm)
                        ->where('width_cm', $row->width_cm)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    [$a, $b] = $kind === 'frame' ? [$row->height_cm, $row->depth_cm] : [$row->width_cm, $row->depth_cm];

                    DB::table('rack_parts')->insert([
                        'tenant_id' => $row->tenant_id,
                        'kind' => $kind,
                        'rack_type' => $type,
                        'sku' => sprintf('%s-%s-%d-%d', strtoupper($kind), strtoupper($type), $a, $b),
                        'height_cm' => $row->height_cm,
                        'depth_cm' => $row->depth_cm,
                        'width_cm' => $row->width_cm,
                        'price_cents' => $row->price_cents,
                        'is_active' => $row->is_active,
                        'sort_order' => $row->sort_order,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        DB::table('rack_parts')->whereIn('kind', ['frame', 'shelf'])->whereIn('rack_type', ['office', 'wine'])->delete();

        Schema::table('rack_parts', function (Blueprint $table) {
            $table->dropUnique('rack_parts_type_dims_unique');
            $table->unique(['tenant_id', 'kind', 'height_cm', 'depth_cm', 'width_cm'], 'rack_parts_dims_unique');
        });

        Schema::table('rack_parts', function (Blueprint $table) {
            $table->dropColumn('rack_type');
        });
    }
};
