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
        Schema::create('resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->onDelete('cascade');
            $table->string('topic');
            $table->text('content'); // The decision itself
            $table->json('tags')->nullable(); // ["Financeiro", "Urgente"]
            $table->enum('status', ['Pendente', 'Em Andamento', 'Cumprida', 'Recorrente'])->default('Pendente');
            
            // Link to responsible member (e.g., Treasurer, Deacon X)
            $table->foreignId('responsible_id')->nullable()->constrained('members');
            
            // Optional: Link to a financial category/cost center if relevant
            $table->foreignId('category_id')->nullable()->constrained('categories');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resolutions');
    }
};
