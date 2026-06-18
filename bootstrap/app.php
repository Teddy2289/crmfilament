<?php

use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        \App\Providers\EventServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        // ── Confiance aux proxies (Nginx, Load Balancer) ────────────
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_HOST |
                     Request::HEADER_X_FORWARDED_PORT |
                     Request::HEADER_X_FORWARDED_PROTO
        );

        // ── Middlewares web globaux ─────────────────────────────────
        // (déjà inclus par défaut dans Laravel 11, mais explicités ici)
        $middleware->web(append: [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ]);

        // ── Aliases de middleware ───────────────────────────────────
        // Utilisés dans les routes et les panels Filament
        $middleware->alias([
            'auth' => Authenticate::class,
            'auth.basic' => AuthenticateWithBasicAuth::class,
            'auth.session' => AuthenticateSession::class,
            'cache.headers' => SetCacheHeaders::class,
            'can' => Authorize::class,
            'guest' => RedirectIfAuthenticated::class,
            'signed' => ValidateSignature::class,
            'throttle' => ThrottleRequests::class,
            'verified' => EnsureEmailIsVerified::class,

            // Spatie Permissions
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'ensure.super.admin' => EnsureSuperAdmin::class,
        ]);

        // ── Redirection après auth ──────────────────────────────────
        // Filament gère ses propres redirections, mais on garde une
        // route de fallback pour les accès directs à /login
        $middleware->redirectGuestsTo(fn () => route('filament.ns-conseil.auth.login'));

        // ── Exclusions CSRF ─────────────────────────────────────────
        // Webhooks Aircall (POST sans token CSRF)
        $middleware->validateCsrfTokens(except: [
            'api/aircall/*',
            'api/webhooks/*',
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {

        // JSON pour toutes les routes API
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
        );

        // Redirection vers le bon panel selon l'URL en cas de 401
        $exceptions->renderable(function (
            AuthenticationException $e,
            Request $request
        ) {
            if ($request->is('ns-conseil/*')) {
                return redirect()->route('filament.ns-conseil.auth.login');
            }

            if ($request->is('allopro/*')) {
                return redirect()->route('filament.allopro.auth.login');
            }
        });

    })->create();
