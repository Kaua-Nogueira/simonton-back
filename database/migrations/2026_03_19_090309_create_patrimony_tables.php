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
        // 1. Categorias de Patrimônio (ex: Móveis, Som)
        Schema::create('patrimony_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('prefix', 10)->unique()->comment('Ex: CAD, SOM, INF');
            $table->unsignedInteger('last_counter')->default(0)->comment('Último número gerado para esta categoria');
            $table->timestamps();
        });

        // 2. Locais/Localizações (ex: Templo, Secretaria)
        Schema::create('patrimony_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // 3. Itens do Patrimônio (Ativos)
        Schema::create('patrimony_assets', function (Blueprint $table) {
            $table->id();
            $table->string('tombo')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            
            $table->foreignId('category_id')->constrained('patrimony_categories')->restrictOnDelete();
            $table->foreignId('location_id')->constrained('patrimony_locations')->restrictOnDelete();
            
            $table->enum('state', ['novo', 'bom', 'regular', 'ruim', 'inservivel'])->default('bom');
            
            $table->decimal('acquisition_value', 15, 2)->nullable();
            $table->decimal('estimated_value', 15, 2);
            $table->date('acquisition_date')->nullable();
            
            $table->string('responsible')->nullable();
            $table->text('observations')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['category_id', 'location_id', 'state', 'is_active']);
        });

        // 4. Histórico de Movimentação
        Schema::create('patrimony_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('patrimony_assets')->cascadeOnDelete();
            $table->foreignId('from_location_id')->nullable()->constrained('patrimony_locations')->nullOnDelete();
            $table->foreignId('to_location_id')->constrained('patrimony_locations')->restrictOnDelete();
            $table->dateTime('movement_date');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrimony_movements');
        Schema::dropIfExists('patrimony_assets');
        Schema::dropIfExists('patrimony_locations');
        Schema::dropIfExists('patrimony_categories');
    }
};
