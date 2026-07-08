<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Start a project" inquiries from the marketing homepage — the studio's
 * lead inbox. Unlike the waitlist (one row per email), a person may enquire
 * more than once, so there is no unique constraint: every submission is a
 * distinct lead the owner works through a status pipeline.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            // What kind of site + rough budget the visitor picked — free of a
            // strict FK; these are curated select options that may evolve.
            $table->string('project_type', 60)->nullable();
            $table->string('budget', 60)->nullable();
            $table->text('message');
            // Where the pipeline stands: new → reviewed → contacted → archived.
            $table->string('status', 20)->default('new');
            $table->string('locale', 8)->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_inquiries');
    }
};
