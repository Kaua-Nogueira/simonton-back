<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => (function() {
        $envStateful = env('SANCTUM_STATEFUL_DOMAINS');
        $frontendUrl = env('FRONTEND_URL');
        $isProd = env('APP_ENV') === 'production';
        
        $urls = [
            'simonton.ipvinhais.com.br',
            'localhost',
            '127.0.0.1',
            '::1',
        ];
        
        if ($frontendUrl) {
            $urls[] = parse_url($frontendUrl, PHP_URL_HOST);
        }
        
        if ($envStateful) {
            $urls = array_merge($urls, explode(',', $envStateful));
        }
        
        // Add dynamic host for LAN/Local context
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null;
        if ($host) {
            $cleanHost = explode(':', $host)[0];
            $urls[] = $cleanHost;
            $urls[] = "{$cleanHost}:3000";
            $urls[] = "{$cleanHost}:3001";
            $urls[] = "{$cleanHost}:8001";
        }
        
        // Include the app's own URL host
        $appHost = parse_url(env('APP_URL', ''), PHP_URL_HOST);
        if ($appHost) $urls[] = $appHost;
        
        return array_values(array_unique(array_filter($urls)));
    })(),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    'expiration' => null,

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

];
