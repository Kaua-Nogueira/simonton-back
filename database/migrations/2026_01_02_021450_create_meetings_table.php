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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('time');
            $table->string('location')->default('Sala do Conselho');
            $table->enum('type', ['Ordinária', 'Extraordinária'])->default('Ordinária');
            $table->enum('status', ['Rascunho', 'Finalizada'])->default('Rascunho');
            
            // Content Sections
            $table->text('opening_prayer')->nullable();
            $table->text('previous_minutes_reading')->nullable();
            $table->text('expedient')->nullable();
            $table->text('reports')->nullable();
            $table->text('closing_prayer')->nullable();
            
            // Officers
            // Only add constraints if we are sure members table exists. It does.
            $table->foreignId('presiding_officer_id')->nullable()->constrained('members')->nullOnDelete(); 
            $table->foreignId('secretary_id')->nullable()->constrained('members')->nullOnDelete();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
