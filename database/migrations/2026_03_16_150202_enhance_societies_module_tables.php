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
        // 1. Enhance Society Financial Movements
        Schema::table('society_financial_movements', function (Blueprint $table) {
            $table->string('attachment_path')->nullable()->after('category');
            $table->boolean('is_confirmed')->default(false)->after('attachment_path');
        });

        // 2. Enhance Society Activities
        Schema::table('society_activities', function (Blueprint $table) {
            $table->decimal('estimated_cost', 10, 2)->default(0)->after('description');
            $table->decimal('estimated_revenue', 10, 2)->default(0)->after('estimated_cost');
        });

        // 3. Create Society Obligations Table
        Schema::create('society_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained('societies')->onDelete('cascade');
            $table->string('description');
            $table->date('due_date');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->foreignId('movement_id')->nullable()->constrained('society_financial_movements')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('society_obligations');

        Schema::table('society_activities', function (Blueprint $table) {
            $table->dropColumn(['estimated_cost', 'estimated_revenue']);
        });

        Schema::table('society_financial_movements', function (Blueprint $table) {
            $table->dropColumn(['attachment_path', 'is_confirmed']);
        });
    }
};
