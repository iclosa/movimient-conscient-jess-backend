<?php

return [

    /*
    | CORS per a l'API. La web pública (Astro) llegeix els endpoints /api/v1/public des del navegador.
    | Defineix CORS_ALLOWED_ORIGINS a .env amb els dominis separats per comes.
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter(array_map('trim', explode(',', (string) env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:4321,http://127.0.0.1:4321',
    )))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => false,

];
