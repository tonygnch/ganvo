<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('theme')->default('default');
            $table->string('logo_path')->nullable();
            $table->string('primary_color')->default('#0EA5E9');
            $table->string('secondary_color')->default('#111827');
            $table->string('font_family')->default('Inter');
            $table->string('custom_domain')->nullable()->unique();
            $table->json('theme_settings')->nullable();
            $table->boolean('is_live')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
