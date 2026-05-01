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
        Schema::create('product_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('ecclesiastical_events')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_campaign_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('product_campaigns')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->decimal('sale_price', 15, 2)->default(0);
            $table->integer('stock_quantity')->nullable();
            $table->timestamps();
        });

        Schema::create('product_campaign_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('product_campaigns')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('product_campaign_items')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('external_name')->nullable();
            $table->string('external_contact')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('total_amount', 15, 2);
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->enum('delivery_status', ['pending', 'delivered'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_campaign_orders');
        Schema::dropIfExists('product_campaign_items');
        Schema::dropIfExists('product_campaigns');
    }
};
