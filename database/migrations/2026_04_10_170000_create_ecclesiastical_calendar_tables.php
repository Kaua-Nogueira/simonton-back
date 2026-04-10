<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ecclesiastical_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['culto', 'reuniao', 'ebd', 'evento', 'ensaio', 'atendimento', 'outro'])->default('evento');
            $table->text('description')->nullable();
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->boolean('all_day')->default(false);
            $table->string('location')->nullable();
            $table->string('ministry')->nullable();
            $table->string('audience')->nullable();
            $table->enum('status', ['draft', 'published', 'cancelled', 'completed'])->default('draft');
            $table->boolean('is_recurring')->default(false);
            $table->json('recurrence_rule')->nullable();
            $table->foreignId('parent_event_id')->nullable()->constrained('ecclesiastical_events')->nullOnDelete();
            $table->foreignId('ebd_class_id')->nullable()->constrained('sunday_school_classes')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_at', 'end_at']);
            $table->index(['status', 'type']);
            $table->index(['ministry']);
        });

        Schema::create('event_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('ecclesiastical_events')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->enum('service_area', ['musica', 'recepcao', 'diaconia', 'midia', 'apoio', 'outro'])->default('apoio');
            $table->string('role_name');
            $table->enum('status', ['pending', 'accepted', 'declined', 'replaced'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->foreignId('replaced_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'member_id', 'role_name'], 'event_member_role_unique');
            $table->index(['member_id', 'status']);
            $table->index(['service_area', 'status']);
        });

        Schema::create('event_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('ecclesiastical_events')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('changes')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_change_logs');
        Schema::dropIfExists('event_assignments');
        Schema::dropIfExists('ecclesiastical_events');
    }
};
