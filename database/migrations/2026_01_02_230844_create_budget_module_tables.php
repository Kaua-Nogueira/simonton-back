<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Update Categories Table
        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('is_taxable')->default(false)->after('description')->comment('For 10% remittance base');
            $table->boolean('is_restricted')->default(false)->after('is_taxable')->comment('For Earmarked Funds');
        });

        // 2. Budgets Table
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->string('description');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Budget Items Table
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->decimal('initial_amount', 15, 2);
            $table->decimal('current_amount', 15, 2);
            $table->timestamps();

            $table->unique(['budget_id', 'category_id']);
        });

        // 4. Budget Movements Table (Transpositions & Supplements)
        Schema::create('budget_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_item_id')->nullable()->constrained('budget_items')->nullOnDelete();
            $table->foreignId('target_item_id')->nullable()->constrained('budget_items')->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('description')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Perfil que realizou
            $table->timestamps();
        });

        // 5. Remittances Table (Conciliar Obligations)
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->decimal('base_amount', 15, 2)->comment('Total Taxable Income');
            $table->decimal('amount', 15, 2)->comment('10% Value');
            $table->string('status')->default('pending'); // pending, paid
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete(); // Linked Expense
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittances');
        Schema::dropIfExists('budget_movements');
        Schema::dropIfExists('budget_items');
        Schema::dropIfExists('budgets');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['is_taxable', 'is_restricted']);
        });
    }
};
