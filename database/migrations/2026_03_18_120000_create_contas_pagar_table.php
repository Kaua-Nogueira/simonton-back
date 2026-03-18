<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_pagar', function (Blueprint $table) {
            $table->id();
            $table->string('descricao');
            $table->decimal('valor', 15, 2);
            $table->date('data_vencimento');
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('budget_item_id')->nullable()->constrained('budget_items')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();

            // Status
            $table->enum('status', ['pendente', 'pago', 'vencido'])->default('pendente');

            // Recorrência
            $table->boolean('recorrente')->default(false);
            $table->enum('tipo_recorrencia', ['mensal', 'anual', 'personalizado'])->nullable();
            $table->unsignedTinyInteger('dia_vencimento')->nullable()->comment('Day of month, e.g. 5 = every 5th');
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->uuid('serie_id')->nullable()->index()->comment('Groups all instances of a recurring series');
            $table->boolean('gerado_automaticamente')->default(false);

            // Metadata
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'data_vencimento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contas_pagar');
    }
};
