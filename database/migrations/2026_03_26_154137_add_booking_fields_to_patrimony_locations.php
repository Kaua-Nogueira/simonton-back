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
        Schema::table('patrimony_locations', function (Blueprint $table) {
            if (!Schema::hasColumn('patrimony_locations', 'capacity')) {
                $table->integer('capacity')->nullable()->after('description');
            }
            if (!Schema::hasColumn('patrimony_locations', 'is_bookable')) {
                $table->boolean('is_bookable')->default(true)->after('capacity');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrimony_locations', function (Blueprint $table) {
            $table->dropColumn(['capacity', 'is_bookable']);
        });
    }
};
