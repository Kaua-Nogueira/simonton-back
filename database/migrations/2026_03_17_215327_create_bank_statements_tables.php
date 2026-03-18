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
        Schema::create('bank_statements', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('bank_name')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('total_entries')->default(0);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->string('description');
            $table->string('bank_ref')->nullable(); // Unique ID from bank statement
            $table->enum('status', ['pending', 'reconciled', 'ignored'])->default('pending');
            $table->timestamps();

            $table->index(['date', 'amount']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('bank_transaction_id')->nullable()->constrained('bank_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['bank_transaction_id']);
            $table->dropColumn('bank_transaction_id');
        });
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_statements');
    }
};
