<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Suppress tempnam() warnings in tests
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (strpos($errstr, 'tempnam()') !== false) {
                return true; // Suppress this warning
            }
            return false; // Let PHP handle other errors normally
        }, E_WARNING);
    }
}
