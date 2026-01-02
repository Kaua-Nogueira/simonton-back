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
        Schema::create('societies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // UMP, SAF...
            $table->string('abbreviation')->nullable();
            $table->integer('min_age')->nullable();
            $table->integer('max_age')->nullable();
            $table->enum('gender_restriction', ['M', 'F'])->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('society_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained('societies')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->enum('status', ['active', 'cooperating', 'emeritus'])->default('active');
            $table->date('pact_date')->nullable(); // Date they signed the pact for current year
            $table->timestamps();
            
            $table->unique(['society_id', 'member_id']);
        });

        Schema::create('society_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('society_id')->constrained('societies')->onDelete('cascade');
            $table->year('year');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();
        });

        Schema::create('mandate_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mandate_id')->constrained('society_mandates')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('role_name'); // Presidente, etc.
            $table->enum('role_type', ['board', 'cause'])->default('board');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandate_roles');
        Schema::dropIfExists('society_mandates');
        Schema::dropIfExists('society_members');
        Schema::dropIfExists('societies');
    }
};
