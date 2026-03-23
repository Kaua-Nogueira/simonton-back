<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$request = \Illuminate\Http\Request::create('/api/patrimony/assets', 'GET', ['per_page' => 1000]);
$response = $app->make(\App\Http\Controllers\Api\Patrimony\AssetController::class)->index($request);
echo $response->getContent();
