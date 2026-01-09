<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // O SQLite aceita qualquer string, então não precisamos alterar a estrutura nele.
        // O MySQL é rigoroso, então nele precisamos rodar o comando.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE members MODIFY COLUMN status ENUM('active', 'inactive', 'pending') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            // Cuidado: Ao reverter, registros com 'pending' no MySQL podem dar erro se não tratados antes.
            DB::statement("ALTER TABLE members MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
        }
    }
};
