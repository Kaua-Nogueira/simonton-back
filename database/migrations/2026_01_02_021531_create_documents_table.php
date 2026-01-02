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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('path'); // File storage path
            $table->enum('type', ['recebido', 'expedido', 'aprovado']); // Received letter, Sent letter, Approved attachment
            $table->string('oficio_number')->nullable(); // For Expedido
            $table->text('description')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
