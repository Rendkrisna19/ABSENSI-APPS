<?php

// config/cors.php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // Pastikan paths mencakup 'api/*' dan 'storage/*' (atau '*' untuk semua)
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'storage/*', '*'], 

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'], // Izinkan semua origin (untuk dev)

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];