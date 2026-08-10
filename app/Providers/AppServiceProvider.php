<?php

namespace App\Providers;

use App\Models\CrmSetting;
use App\Services\Aopia\AopiaIcsService;
use App\Services\Crm\CrmSettingsService;
use App\Services\RingoverService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Laravel\Telescope\TelescopeServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RingoverService::class);
        $this->app->singleton(AopiaIcsService::class);
        $this->app->singleton(CrmSettingsService::class);

        // Telescope reste un paquet dev (composer require-dev) : on ne
        // l'enregistre qu'en local pour ne jamais casser
        // `composer install --no-dev` en production/CI.
        if ($this->app->environment('local')) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ReclamationP8 ne suit pas la convention Foo → FooPolicy, donc on
        // l'enregistre explicitement.
        Gate::policy(\App\Models\ReclamationP8::class, \App\Policies\ReclamationPolicy::class);

        Validator::extend('siret', function ($attribute, $value) {
            return preg_match('/^\d{14}$/', $value);
        }, 'Le SIRET doit contenir exactement 14 chiffres.');

        // Validation description P2 >= 30 chars
        Validator::extend('description_p2', function ($attribute, $value) {
            return strlen($value) >= 30;
        }, 'La description doit contenir au minimum 30 caractères.');

        CrmSetting::saved(fn() => app(CrmSettingsService::class)->forget());
        CrmSetting::deleted(fn() => app(CrmSettingsService::class)->forget());

        // Enregistrement du namespace de composants Blade pour le module Phoning.
        // Permet d'utiliser <x-phoning::queue-table>, <x-phoning::contact-panel>, etc.
        Blade::anonymousComponentPath(resource_path('views/components/phoning'), 'phoning');

        // ── Rate Limiters API ───────────────────────────────────────
        // Requêtes authentifiées : 1 000/heure par token/IP
        RateLimiter::for('api', function (Request $request) {
            $limit = config('api.rate_limiting.api_limit', 1000);

            return Limit::perHour($limit)
                ->by($request->user()?->id ?: $request->ip())
                ->response(fn ($request, array $headers) => response()->json(
                    ['message' => 'Too Many Requests'],
                    429,
                    $headers
                ));
        });

        // Tentatives de connexion : 10/minute par IP
        RateLimiter::for('login', function (Request $request) {
            $limit = config('api.rate_limiting.login_limit', 10);

            return Limit::perMinute($limit)
                ->by($request->ip())
                ->response(fn ($request, array $headers) => response()->json(
                    ['message' => 'Too Many Attempts'],
                    429,
                    $headers
                ));
        });
    }
}
