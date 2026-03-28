<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add prefix and last_counter to locations
        Schema::table('patrimony_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('patrimony_locations', 'prefix')) {
                $table->string('prefix', 10)->nullable()->comment('Prefix for tombo generation (ex: TMPL, SECR)');
            }
            if (!Schema::hasColumn('patrimony_locations', 'last_counter')) {
                $table->unsignedInteger('last_counter')->default(0)->comment('Last number generated for this location');
            }
        });

        // 2. Remove from categories
        Schema::table('patrimony_categories', function (Blueprint $table) {
            if (Schema::hasColumn('patrimony_categories', 'prefix')) {
                // SQLite constraint fix: drop unique index before dropping column
                $table->dropUnique(['prefix']);
                $table->dropColumn('prefix');
            }
            if (Schema::hasColumn('patrimony_categories', 'last_counter')) {
                $table->dropColumn('last_counter');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrimony_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('patrimony_categories', 'prefix')) {
                $table->string('prefix', 10)->nullable()->unique();
            }
            if (!Schema::hasColumn('patrimony_categories', 'last_counter')) {
                $table->unsignedInteger('last_counter')->default(0);
            }
        });

        Schema::table('patrimony_locations', function (Blueprint $table) {
            if (Schema::hasColumn('patrimony_locations', 'prefix')) {
                $table->dropColumn('prefix');
            }
            if (Schema::hasColumn('patrimony_locations', 'last_counter')) {
                $table->dropColumn('last_counter');
            }
        });
    }
};
