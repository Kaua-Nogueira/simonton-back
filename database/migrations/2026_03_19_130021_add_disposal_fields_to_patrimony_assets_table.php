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
        Schema::table('patrimony_assets', function (Blueprint $table) {
            $table->string('disposal_reason')->nullable()->after('observations');
            $table->date('disposal_date')->nullable()->after('disposal_reason');
            $table->text('disposal_observations')->nullable()->after('disposal_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patrimony_assets', function (Blueprint $table) {
            $table->dropColumn(['disposal_reason', 'disposal_date', 'disposal_observations']);
        });
    }
};
