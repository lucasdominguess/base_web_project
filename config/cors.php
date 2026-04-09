<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | SECURITY WARNING: CORS_ALLOWED_ORIGINS=* is INSECURE for production.
    | Always specify explicit allowed origins in production environment.
    |
    | For development: Set to ['*'] or specific localhost URLs
    | For production: Set specific domain URLs only (e.g., ['https://example.com'])
    |
    */

    'paths' => explode(',', env('CORS_PATHS', 'api/*')),

    'allowed_methods' => explode(',', env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,PATCH,DELETE,OPTIONS')),

    /**
     * SECURITY: In production, never use '*' for allowed_origins
     * Instead, configure specific origins in .env
     * Example: CORS_ALLOWED_ORIGINS=https://app.example.com,https://dashboard.example.com
     */
    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') === '*'
        ? (app()->environment('production') ? [] : ['*'])
        : explode(',', env('CORS_ALLOWED_ORIGINS', '')),

    'allowed_origins_patterns' => env('CORS_ALLOWED_ORIGINS_PATTERNS')
        ? explode(',', env('CORS_ALLOWED_ORIGINS_PATTERNS'))
        : [],

    'allowed_headers' => env('CORS_ALLOWED_HEADERS') === '*'
        ? ['*']
        : explode(',', env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization')),

    'exposed_headers' => explode(',', env('CORS_EXPOSED_HEADERS', '')),

    'max_age' => env('CORS_MAX_AGE', 0),

    'supports_credentials' => true,

];
