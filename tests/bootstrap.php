<?php
// Temporarily suppress tempnam() warnings during bootstrap
$originalHandler = set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    // Only suppress tempnam() warnings, let everything else through
    if (strpos($errstr, 'tempnam()') !== false || strpos($errstr, 'system\'s temporary directory') !== false) {
        return true; // Suppress these specific warnings
    }
    return false; // Pass through to Laravel's handler
}, E_WARNING | E_DEPRECATED);

// Load Composer autoloader  
require __DIR__ . '/../vendor/autoload.php';

// Restore the original error handler (Laravel's will take over)
restore_error_handler();
