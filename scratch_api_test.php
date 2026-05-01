<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Auth;

$u = User::where('email', 'teste@membro.com')->first();
Auth::login($u);

// Simulate the JSON response that the frontend receives
echo json_encode($u->toArray(), JSON_PRETTY_PRINT);
