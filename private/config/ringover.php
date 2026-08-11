<?php

return [
    'api_token' => env('RINGOVER_API_TOKEN'),
    'auth_scheme' => env('RINGOVER_AUTH_SCHEME', 'Bearer'),
    'region' => env('RINGOVER_REGION', 'europe'),
    'base_urls' => [
        'europe' => 'https://public-api.ringover.com/v2',
        'us' => 'https://public-api-us.ringover.com/v2',
    ],
    'base_url' => env('RINGOVER_BASE_URL'),
    'timeout' => env('RINGOVER_TIMEOUT', 10),
    'dial_url_template' => env('RINGOVER_DIAL_URL_TEMPLATE', 'tel:{phone}'),
    'webhook_secret' => env('RINGOVER_WEBHOOK_SECRET'),
    'monitoring_enabled' => env('RINGOVER_MONITORING_ENABLED', false),
    'status_tags' => [
        'nrp' => 'NRP',
        'fax' => 'FAX',
        'supp' => 'SUPP',
        'maj' => 'MAJ',
        'rdv' => 'RDV',
        'cse_ni' => 'CSE-NI',
        'rapl_elu' => 'RAPL-ELU',
        'rapl_std' => 'RAPL-STD',
        'bloc' => 'BLOC',
        'bloc2' => 'BLOC2',
        'ncse_50' => 'NCSE-50',
        'ncse_plus50' => 'NCSE+50',
        'cse_zone' => 'CSE-ZONE',
        'cse_hz' => 'CSE-HZ',
    ],
];
