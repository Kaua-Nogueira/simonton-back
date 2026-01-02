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
        Schema::create('society_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained('societies')->onDelete('cascade');
            $table->string('title');
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('type'); // Plenary, Devotional...
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('society_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained('society_activities', 'id')->onDelete('cascade');
            $table->foreignId('society_member_id')->constrained('society_members')->onDelete('cascade');
            $table->enum('status', ['present', 'absent', 'justified']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('society_attendances');
        Schema::dropIfExists('society_activities');
    }
};
