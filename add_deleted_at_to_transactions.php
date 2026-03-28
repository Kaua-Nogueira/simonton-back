<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (!Schema::hasColumn('transactions', 'deleted_at')) {
        Schema::table('transactions', function (Blueprint $table) {
            $table->softDeletes();
        });
        echo "Coluna deleted_at adicionada com sucesso!\n";
    } else {
        echo "Coluna deleted_at já existe.\n";
    }
} catch (\Exception $e) {
    echo "Erro ao adicionar coluna: " . $e->getMessage() . "\n";
}
