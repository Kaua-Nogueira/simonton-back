<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$u = User::where('email', 'teste@membro.com')->first();
if ($u) {
    $u->role = 'Membro (Sistema)';
    $u->save();
    echo "Cargo do usuario teste atualizado com sucesso para: " . $u->role . "\n";
} else {
    echo "Usuario teste nao encontrado.\n";
}
