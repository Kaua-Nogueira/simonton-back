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
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sunday_school_class_id')->constrained('sunday_school_classes')->onDelete('cascade');
            $table->date('date');
            $table->integer('present_count')->default(0);
            $table->integer('visitors_count')->default(0);
            $table->integer('bible_count')->default(0);
            $table->decimal('offering_amount', 10, 2)->default(0.00);
            $table->json('attendees')->nullable(); // Stores array of member_ids present
            $table->timestamps();

            $table->unique(['sunday_school_class_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
