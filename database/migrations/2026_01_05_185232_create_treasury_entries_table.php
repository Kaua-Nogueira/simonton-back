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
        // 1. Header Table
        Schema::create('treasury_entries', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('status', ['draft', 'pending', 'confirmed'])->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            
            // Users
            $table->foreignId('user_id')->constrained('users'); // Who created/counted
            $table->foreignId('confirmed_by')->nullable()->constrained('users'); // Treasurer
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Physical Cash Details (For the calculator)
        Schema::create('treasury_cash', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('treasury_entries')->onDelete('cascade');
            // Store denomination as string '100.00' or decimal? Decimal is safer for sorting/calc
            $table->decimal('denomination', 10, 2); 
            $table->integer('quantity');
            $table->decimal('amount', 15, 2); // Calculated: denomination * quantity
            $table->timestamps();
        });

        // 3. Envelope/Split Details (For the discrimination)
        Schema::create('treasury_splits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('treasury_entries')->onDelete('cascade');
            
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete(); 
            // Nullable because it might be "Anonymous" or "Visitor"
            
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['tithe', 'offering', 'mission', 'other'])->default('tithe');
            
            $table->boolean('is_digital')->default(false); // If PIX/TED
            $table->string('description')->nullable(); // Optional note on envelope
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasury_splits');
        Schema::dropIfExists('treasury_cash');
        Schema::dropIfExists('treasury_entries');
    }
};
