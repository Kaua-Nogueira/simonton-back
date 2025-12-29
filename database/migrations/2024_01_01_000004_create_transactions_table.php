<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->date('date');
            $table->enum('payment_method', ['pix', 'boleto', 'ted', 'cartao', 'dinheiro', 'outros'])->nullable();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_center_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['pending', 'suggested', 'confirmed', 'split'])->default('pending');
            $table->integer('suggestion_confidence')->nullable();
            $table->foreignId('parent_transaction_id')->nullable()->constrained('transactions')->cascadeOnDelete();
            $table->text('ofx_data')->nullable();
            $table->decimal('balance_before', 15, 2)->nullable();
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
            
            $table->index(['date', 'status']);
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
