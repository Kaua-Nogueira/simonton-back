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
        Schema::table('space_bookings', function (Blueprint $table) {
            // Drop old FK if it exists (usually it does as it was created in 2026_01_02)
            try {
                $table->dropForeign(['location_id']);
            } catch (\Exception $e) {
                // Ignore if not exists
            }

            // Re-point to the new table
            $table->foreign('location_id')
                  ->references('id')
                  ->on('patrimony_locations')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('space_bookings', function (Blueprint $table) {
            try {
                $table->dropForeign(['location_id']);
            } catch (\Exception $e) { }

            // Re-point back to old locations table
            $table->foreign('location_id')
                  ->references('id')
                  ->on('locations')
                  ->cascadeOnDelete();
        });
    }
};
