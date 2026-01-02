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
        Schema::table('members', function (Blueprint $table) {
            $table->integer('roll_number')->nullable()->unique()->after('id');
            $table->date('admission_date')->nullable()->after('status');
            $table->string('admission_type')->nullable()->after('admission_date'); // Profissão de Fé, Batismo, Transferência, Jurisdição
            $table->string('previous_church')->nullable()->after('admission_type');
            
            $table->date('dismissal_date')->nullable()->after('previous_church');
            $table->string('dismissal_type')->nullable()->after('dismissal_date'); // Transferência, Falecimento, Exclusão, Abandono
            $table->string('destination_church')->nullable()->after('dismissal_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn([
                'roll_number',
                'admission_date',
                'admission_type',
                'previous_church',
                'dismissal_date',
                'dismissal_type',
                'destination_church'
            ]);
        });
    }
};
