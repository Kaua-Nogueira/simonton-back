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
        // 1. Tabela Principal de Prestação de Contas (Cabeçalho)
        Schema::create('expense_reconciliations', function (Blueprint $table) {
            $table->id();
            
            // Link para a Transação de Saída original (A saída do dinheiro do caixa/banco)
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            
            // Responsável pela prestação (quem pegou o dinheiro)
            $table->foreignId('responsible_member_id')->nullable()->constrained('members')->nullOnDelete();
            
            $table->decimal('total_advanced', 15, 2); // Valor que saiu originalmente
            $table->decimal('total_reconciled', 15, 2)->default(0); // Soma das notas cadastradas
            
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Tabela de Itens da Prestação (As notas fiscais propriamente ditas)
        Schema::create('expense_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('expense_reconciliations')->onDelete('cascade');
            
            $table->date('date');
            $table->string('description');
            $table->decimal('amount', 15, 2);
            
            // Classificação financeira para cada nota
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained('cost_centers')->nullOnDelete();
            
            $table->string('document_number')->nullable(); // Número da Nota Fiscal/Cupom
            $table->string('attachment_path')->nullable(); // Foto/PDF da nota
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_reconciliation_items');
        Schema::dropIfExists('expense_reconciliations');
    }
};
