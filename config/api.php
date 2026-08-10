<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Token TTLs (durées de vie en minutes)
    |--------------------------------------------------------------------------
    */
    'token_ttl' => [
        'access'  => env('API_ACCESS_TOKEN_TTL',  60 * 24),       // 24 heures
        'refresh' => env('API_REFRESH_TOKEN_TTL', 60 * 24 * 30),  // 30 jours
    ],

    /*
    |--------------------------------------------------------------------------
    | GraphQL (optionnel)
    |--------------------------------------------------------------------------
    */
    'graphql_enabled' => env('API_GRAPHQL_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        // Requêtes authentifiées — par token/IP, par heure
        'api_limit'   => env('API_RATE_LIMIT', 1000),
        'api_window'  => 'hour', // 'minute' | 'hour'

        // Tentatives de connexion — par IP, par minute
        'login_limit'  => env('API_LOGIN_RATE_LIMIT', 10),
        'login_window' => 'minute',
    ],

];
