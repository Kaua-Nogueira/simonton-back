<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/api/reconciliation/pending', 'GET');
try {
    $response = $app->handle($request);
    echo $response->getContent();
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
