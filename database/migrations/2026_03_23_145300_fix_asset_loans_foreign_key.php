<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For SQLite and MySQL compatibility, we simply drop the old table and recreate it
        // since the patrimony module was completely refactored and starting fresh.
        Schema::dropIfExists('asset_loans');

        // 2. Create new table with correct FK
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('patrimony_assets')->cascadeOnDelete();
            $table->string('requester_name');
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('checkout_date');
            $table->timestamp('expected_return_date')->nullable();
            $table->timestamp('actual_return_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_loans');
        // Recreating old structure is complex and probably not needed given the refactoring context.
    }
};
