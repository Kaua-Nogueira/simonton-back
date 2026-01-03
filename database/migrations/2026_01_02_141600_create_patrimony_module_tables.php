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
        // 1. Locations (Used for Assets and Space Booking)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Templo, Salão, Sala 1...
            $table->text('description')->nullable();
            $table->integer('capacity')->nullable();
            $table->boolean('is_bookable')->default(true);
            $table->timestamps();
        });

        // 2. Asset Categories
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Assets (Inventory)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->unique(); // Patrimony ID / QR Code
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->enum('status', ['new', 'good', 'needs_repair', 'unusable', 'disposed'])->default('good');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_value', 15, 2)->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('supplier')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });

        // 4. Maintenance Requests (Book of Occurrences)
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->text('description');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            // Assuming 'users' table exists for system users/members login
            $table->foreignId('requester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'analyzing', 'in_repair', 'done', 'cancelled'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('cost', 15, 2)->nullable(); // Repair cost
            $table->text('notes')->nullable(); // Tech notes
            $table->timestamps();
        });

        // 5. Preventive Schedules
        Schema::create('preventive_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('asset_id')->nullable()->constrained('assets')->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('locations')->cascadeOnDelete();
            $table->integer('frequency_days'); // e.g. 90 days
            $table->date('last_performed_at')->nullable();
            $table->date('next_due_date')->nullable();
            $table->timestamps();
        });

        // 6. Asset Loans
        Schema::create('asset_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('requester_name'); // Or link to member if desired, keeping simple for external loans too
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamp('checkout_date');
            $table->timestamp('expected_return_date')->nullable();
            $table->timestamp('actual_return_date')->nullable();
            $table->enum('status', ['active', 'returned', 'overdue'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 7. Space Bookings
        Schema::create('space_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->foreignId('requester_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('event_name');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            // Prevent double booking at database level? Hard to do with timestamps ranges in SQL constraints widely supported.
            // Will handle in logic.
        });

        // 8. Consumables
        Schema::create('consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit'); // box, liter, unit
            $table->integer('current_quantity')->default(0);
            $table->integer('min_threshold')->default(5);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumables');
        Schema::dropIfExists('space_bookings');
        Schema::dropIfExists('asset_loans');
        Schema::dropIfExists('preventive_schedules');
        Schema::dropIfExists('maintenance_requests');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
        Schema::dropIfExists('locations');
    }
};
