<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_unique(array_filter([
        env('FRONTEND_URL'),
        (env('APP_ENV') === 'production' ? 'https://simonton.ipvinhais.com.br' : null),
        'http://localhost:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://192.168.0.192:3000',
    ]))),

    // Keep wildcard local-network patterns only outside production.
    'allowed_origins_patterns' => env('APP_ENV') === 'production'
        ? []
        : ['~^https?://(localhost|127\\.0\\.0\\.1|192\\.168\\.\\d+\\.\\d+|172\\.(1[6-9]|2\\d|3[01])\\.\\d+\\.\\d+|10\\.\\d+\\.\\d+\\.\\d+)(:\\d+)?$~'],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'X-XSRF-TOKEN', 'Authorization', 'Accept', 'X-Silent-Error'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
