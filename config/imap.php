<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IMAP Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration pour la connexion IMAP/SMTP
    |
    */

    'host' => env('IMAP_HOST', 'imap.gmail.com'),
    
    'port' => env('IMAP_PORT', 993),
    
    'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    
    'validate_cert' => env('IMAP_VALIDATE_CERT', true),
    
    'protocol' => env('IMAP_PROTOCOL', 'imap'),
    
    'authentication' => env('IMAP_AUTHENTICATION', null),
    
    /*
    |--------------------------------------------------------------------------
    | SMTP Configuration (pour l'envoi)
    |--------------------------------------------------------------------------
    */
    
    'smtp_host' => env('SMTP_HOST', 'smtp.gmail.com'),
    
    'smtp_port' => env('SMTP_PORT', 587),
    
    'smtp_encryption' => env('SMTP_ENCRYPTION', 'tls'),
    
    /*
    |--------------------------------------------------------------------------
    | Dossiers IMAP
    |--------------------------------------------------------------------------
    */
    
    'folders' => [
        'inbox' => 'INBOX',
        'sent' => 'Sent',
        'drafts' => 'Drafts',
        'trash' => 'Trash',
        'archive' => 'Archive',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Options de synchronisation
    |--------------------------------------------------------------------------
    */
    
    'sync_limit' => env('IMAP_SYNC_LIMIT', 50),
    
    'sync_interval' => env('IMAP_SYNC_INTERVAL', 5), // en minutes
    
    /*
    |--------------------------------------------------------------------------
    | Stockage des pièces jointes
    |--------------------------------------------------------------------------
    */
    
    'attachments_disk' => env('IMAP_ATTACHMENTS_DISK', 'local'),
    
    'attachments_path' => env('IMAP_ATTACHMENTS_PATH', 'emails'),
];
