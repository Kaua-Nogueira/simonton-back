<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Http\Controllers\Api\Acl\MenuController;

// Find an admin user
$user = User::whereHas('roles', function($q){ $q->where('name', 'Admin'); })->first();
if (!$user) $user = User::first();

auth()->login($user);

$controller = new MenuController();
$response = $controller->index();

echo json_encode($response, JSON_PRETTY_PRINT);
