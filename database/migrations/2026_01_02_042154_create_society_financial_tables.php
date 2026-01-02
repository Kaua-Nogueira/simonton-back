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
        Schema::create('society_financial_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained('societies')->onDelete('cascade');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['income', 'expense']);
            $table->date('date');
            $table->string('category')->nullable(); // Dues, Offer, Material...
            $table->timestamps();
        });

        Schema::create('society_dues_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_member_id')->constrained('society_members')->onDelete('cascade');
            $table->year('year');
            $table->tinyInteger('month'); // 1-12
            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('society_dues_payments');
        Schema::dropIfExists('society_financial_movements');
    }
};
