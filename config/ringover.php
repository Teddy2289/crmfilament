<?php

return [
    'api_token' => env('RINGOVER_API_TOKEN'),
    'auth_scheme' => env('RINGOVER_AUTH_SCHEME', 'Bearer'),
    'base_url' => env('RINGOVER_BASE_URL', 'https://public-api.ringover.com/v2'),
    'timeout' => env('RINGOVER_TIMEOUT', 10),
    'dial_url_template' => env('RINGOVER_DIAL_URL_TEMPLATE', 'tel:{phone}'),
];
