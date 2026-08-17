<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class TestBootstrapProvider extends ServiceProvider
{
    public function boot()
    {
        if (! app()->runningUnitTests()) {
            return;
        }

        // Suppress tempnam() warning from Livewire's SupportFileUploads during testing
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (strpos($errstr, 'tempnam()') !== false) {
                return true;
            }
            return false;
        }, E_WARNING);
    }
}
